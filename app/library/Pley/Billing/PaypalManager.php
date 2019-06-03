<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Billing;

use Pley;
use PayPal\Rest\ApiContext;
use PayPal\Api\ChargeModel;
use PayPal\Api\Currency;
use PayPal\Api\WebhookEvent;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\VerifyWebhookSignature;
use PayPal\Api\AgreementStateDescriptor;
use PayPal\Common\PayPalModel;
use PayPal\Api\Agreement;
use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Sale;
use PayPal\Api\RefundRequest;
use Pley\Entity\Payment\PaymentPlan;
use Pley\Entity\Payment\VendorPaymentPlan;

/**
 * Class description goes here
 *
 * @author Vsevolod Yatsiuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class PaypalManager
{
    /**
     * @var \PayPal\Rest\ApiContext
     */
    protected $_apiContext;
    /**
     * @var Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao
     */
    protected $_vendorPaymentPlanDao;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionMgr;

    /** @var \Pley\User\UserSubscriptionManager */
    protected $_userSubscriptionMgr;

    /** @var \Pley\Coupon\CouponManager */
    protected $_couponManager;

    /** @var Pley\Referral\RewardManager */
    protected $_rewardManager;

    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_paymentPlanDao;

    /** @var \Pley\Repository\Paypal\PaypalLogRepository */
    protected $_paypalLogRepository;


    /**
     * PaypalManager constructor.
     * @param ApiContext $apiContext
     * @param Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao
     * @param Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao
     * @param Pley\Subscription\SubscriptionManager $subscriptionMgr
     * @param Pley\Coupon\CouponManager $couponManager
     * @param Pley\Referral\RewardManager $rewardManager
     * @param Pley\Repository\Paypal\PaypalLogRepository $paypalLogRepository
     */

    public function __construct(
        ApiContext $apiContext,
        Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
        Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao,
        Pley\Subscription\SubscriptionManager $subscriptionMgr,
        Pley\Coupon\CouponManager $couponManager,
        Pley\Referral\RewardManager $rewardManager,
        Pley\Repository\Paypal\PaypalLogRepository $paypalLogRepository
    )
    {
        $this->_apiContext = $apiContext;
        $this->_paymentPlanDao = $paymentPlanDao;
        $this->_vendorPaymentPlanDao = $vendorPaymentPlanDao;
        $this->_subscriptionMgr = $subscriptionMgr;
        $this->_couponManager = $couponManager;
        $this->_rewardManager = $rewardManager;
        $this->_paypalLogRepository = $paypalLogRepository;
    }

    /**
     * @param $planData
     * @param bool $activate
     * @return Plan
     * @throws \Exception
     */
    public function createBillingPlan($planData, $activate = true)
    {

        $plan = new Plan();
        $plan->setName($planData['name'])
            ->setDescription($planData['description'])
            ->setType('INFINITE');

        $paymentDefinition = new PaymentDefinition();
        $paymentDefinition->setName('Regular Payments')
            ->setType('REGULAR')
            ->setFrequency($planData['frequency'])
            ->setFrequencyInterval($planData['frequencyInterval'])
            ->setCycles("0")
            ->setAmount(new Currency(array('value' => $planData['total'], 'currency' => 'USD')));

        $chargeModel = new ChargeModel();
        $chargeModel->setType('SHIPPING')
            ->setAmount(new Currency(array('value' => 0, 'currency' => 'USD')));

        $paymentDefinition->setChargeModels(array($chargeModel));

        $plan->setPaymentDefinitions(array($paymentDefinition));
        $plan->setMerchantPreferences($this->getMerchantPreferences());

        try {
            $plan->create($this->_apiContext);
            if ($activate) {
                $this->activateBillingPlan($plan->getId());
            }
        } catch (\Exception $ex) {
            throw $ex;
        }
        return $plan;
    }

    /**
     * @param $billingPlanId
     * @return Plan
     */
    public function activateBillingPlan($billingPlanId)
    {
        $plan = $this->getBillingPlan($billingPlanId);
        $patch = new Patch();
        $value = new PayPalModel('{
	       "state":"ACTIVE"
	     }');
        $patch->setOp('replace')
            ->setPath('/')
            ->setValue($value);
        $patchRequest = new PatchRequest();
        $patchRequest->addPatch($patch);
        $plan->update($patchRequest, $this->_apiContext);
        return $plan;
    }


    /**
     * @param $billingPlanId
     * @param $subscriptionId
     * @param $planId
     * @param null $coupon
     * @return Agreement
     */

    public function createBillingAgreement($billingPlanId, $subscriptionId, $planId, $coupon = null)
    {
        $subscription = $this->_subscriptionMgr->getSubscription($subscriptionId);
        $paymentPlan = $this->_paymentPlanDao->find($planId);

        $setupChargeAmount = $this->_calculateSetupChargeAmount($subscriptionId, $planId, $coupon);
        $firstRecurringChargeDate = $this->_calculateRecurringPaymentStartDate($subscriptionId, $planId);

        $plan = new Plan();
        $plan->setId($billingPlanId);
        $agreement = new Agreement();
        $agreement->setName($subscription->getName())
            ->setDescription($subscription->getName() . ' / ' . $paymentPlan->getTitle())
            ->setStartDate(date('Y-m-d\TH:i:s\Z', $firstRecurringChargeDate));
        $agreement->setPlan($plan);


        $agreement->setOverrideMerchantPreferences($this->getMerchantPreferences($setupChargeAmount));

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $agreement->setPayer($payer);

        $logEntry = $this->_logRequestPayload(
            \Pley\Enum\Paypal\ApiRequestTypeEnum::AGREEMENT_CREATE,
            $agreement->toJSON());

        $agreement = $agreement->create($this->_apiContext);

        $this->_logResponsePayload(
            $logEntry,
            $agreement->toJSON()
        );

        return $agreement;
    }

    /**
     * @param $paymentToken
     * @return Agreement
     * @throws \Exception
     */
    public function executeBillingAgreement($paymentToken)
    {
        $agreement = new Agreement();
        try {
            $logEntry = $this->_logRequestPayload(
                \Pley\Enum\Paypal\ApiRequestTypeEnum::AGREEMENT_EXECUTE,
                $paymentToken);
            $agreement->execute($paymentToken, $this->_apiContext);
            $this->_logResponsePayload($logEntry, $agreement->toJSON());
            return $agreement;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @param $billingAgreementId
     * @return Agreement
     */
    public function getBillingAgreement($billingAgreementId)
    {
        return Agreement::get($billingAgreementId, $this->_apiContext);
    }

    /**
     * @param string $billingAgreementId
     * @return \PayPal\Api\AgreementTransactions
     */
    public function listBillingAgreementTransactions($billingAgreementId)
    {
        $params = array('start_date' => date('Y-m-d', strtotime('-15 years')), 'end_date' => date('Y-m-d', strtotime('+5 days')));
        $agreementTransactions = Agreement::searchTransactions($billingAgreementId, $params, $this->_apiContext);
        return $agreementTransactions;
    }

    /**
     * @param string $transactionId
     * @param float $amount
     * @param string $comment
     * @return \PayPal\Api\DetailedRefund
     */
    public function refundTransaction($transactionId, $amount, $comment)
    {
        $amt = new Amount();
        $amt->setCurrency('USD')
            ->setTotal($amount);
        $refundRequest = new RefundRequest();
        $refundRequest->setAmount($amt);
        $refundRequest->setReason($comment);
        $sale = new Sale();
        $sale->setId($transactionId);
        $refundTransaction = $sale->refundSale($refundRequest, $this->_apiContext);
        return $refundTransaction;
    }

    /**
     * @param $billingAgreementId
     * @param $data
     * @return Agreement
     * @throws \Exception
     */
    public function updateBillingAgreement($billingAgreementId, $data)
    {
        $agreement = $this->getBillingAgreement($billingAgreementId);
        $patch = new Patch();

        $value = new PayPalModel($data);

        $patch->setOp('replace')
            ->setPath('/')
            ->setValue($value);

        $patchRequest = new PatchRequest();
        $patchRequest->addPatch($patch);
        try {
            $agreement->update($patchRequest, $this->_apiContext);
        } catch (\Exception $e) {
            throw $e;
        }
        return $this->getBillingAgreement($billingAgreementId);
    }

    public function suspendBillingAgreement($billingAgreementId)
    {
        $agreement = $this->getBillingAgreement($billingAgreementId);

        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor->setNote("Suspended billing agreement");
        try {
            $agreement->suspend($agreementStateDescriptor, $this->_apiContext);
        } catch (\Exception $e) {
            throw $e;
        }
        return $this->getBillingAgreement($billingAgreementId);
    }

    public function reactivateBillingAgreement($billingAgreementId)
    {
        $agreement = $this->getBillingAgreement($billingAgreementId);

        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor->setNote("Reactivated billing agreement");
        try {
            $agreement->reActivate($agreementStateDescriptor, $this->_apiContext);
        } catch (\Exception $e) {
            throw $e;
        }
        return $this->getBillingAgreement($billingAgreementId);
    }

    public function cancelBillingAgreement($billingAgreementId)
    {
        $agreement = $this->getBillingAgreement($billingAgreementId);

        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor->setNote("Cancelled billing agreement");
        try {
            $agreement->cancel($agreementStateDescriptor, $this->_apiContext);
        } catch (\Exception $e) {
            throw $e;
        }
        return $this->getBillingAgreement($billingAgreementId);
    }

    /**
     * @param string $status
     * @param int $pageSize
     * @return \PayPal\Api\PlanList
     */
    public function listBillingPlans($status = 'ACTIVE', $pageSize = 20)
    {
        $planList = Plan::all([
            'page_size' => $pageSize, 'status' => $status
        ], $this->_apiContext);

        return $planList;
    }

    /**
     * @param $billingPlanId
     * @return Plan
     */
    public function getBillingPlan($billingPlanId)
    {
        return Plan::get($billingPlanId, $this->_apiContext);
    }

    /**
     * @return \PayPal\Api\WebhookList
     */
    public function getAllWebhooks()
    {
        return \PayPal\Api\Webhook::getAllWithParams($this->_apiContext);
    }

    /**
     * @param $headers
     * @param $json
     * @return WebhookEvent
     */
    public function getWebhookEvent($headers, $json)
    {
        $data = json_decode($json, true);
        //$this->validateWebhookEvent($headers, $json);
        return WebhookEvent::get($data['id'], $this->_apiContext);
    }

    /**
     * @param $headers
     * @param $body
     * @return bool
     * @throws \Exception
     */
    public function validateWebhookEvent($headers, $body)
    {
        $paypalWebhookId = \Config::get('paypal.webhookId');
        $headers = array_change_key_case($headers, CASE_UPPER);

        foreach ($headers as $headerKey => $headerValue) {
            $headers[$headerKey] = $headerValue[0];
        }

        $signatureVerification = new VerifyWebhookSignature();

        $signatureVerification->setWebhookId($paypalWebhookId);

        $signatureVerification->setAuthAlgo($headers['PAYPAL-AUTH-ALGO']);
        $signatureVerification->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID']);
        $signatureVerification->setCertUrl($headers['PAYPAL-CERT-URL']);
        $signatureVerification->setTransmissionSig($headers['PAYPAL-TRANSMISSION-SIG']);
        $signatureVerification->setTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME']);
        $signatureVerification->setRequestBody($body);

        $output = $signatureVerification->post($this->_apiContext);
        if ($output->getVerificationStatus() === 'FAILURE') {
            throw new \Exception('PayPal webhook event validation failed');
        }
        return true;
    }

    /**
     * @param $planData
     * @param PaymentPlan $paymentPlan
     * @param Plan $paypalBillingPlan
     */
    public function upsertVendorPaymentPlan($planData, PaymentPlan $paymentPlan, Plan $paypalBillingPlan)
    {
        $vendorPaymentPlan = $this->_vendorPaymentPlanDao->findByPaymentPlan(
            $paymentPlan->getId(),
            Pley\Enum\Shipping\ShippingZoneEnum::DEFAULT_ZONE_ID,
            Pley\Enum\PaymentSystemEnum::PAYPAL
        );
        if (!$vendorPaymentPlan) {
            $vendorPaymentPlan = new VendorPaymentPlan(
                null,
                $paymentPlan->getId(),
                Pley\Enum\Shipping\ShippingZoneEnum::DEFAULT_ZONE_ID,
                $planData['basePrice'],
                $planData['unitPrice'],
                $planData['shippingPrice'],
                $planData['total'],
                Pley\Enum\PaymentSystemEnum::PAYPAL,
                $paypalBillingPlan->getId()
            );
        } else {
            $vendorPaymentPlan->setVPaymentPlan(Pley\Enum\PaymentSystemEnum::PAYPAL, $paypalBillingPlan->getId());
        }
        $this->_vendorPaymentPlanDao->save($vendorPaymentPlan);
    }

    /**
     * @param int $setupFeeAmount
     * @return MerchantPreferences
     */
    protected function getMerchantPreferences($setupFeeAmount = 0)
    {
        $baseUrl = $this->_apiContext->get('returnBaseUrl');
        $merchantPreferences = new MerchantPreferences();
        $merchantPreferences->setReturnUrl("$baseUrl/success?success=true")
            ->setCancelUrl("$baseUrl/cancel?success=false")
            ->setAutoBillAmount("yes")
            ->setInitialFailAmountAction("CANCEL")
            ->setMaxFailAttempts("5")
            ->setSetupFee(new Currency(array('value' => $setupFeeAmount, 'currency' => 'USD')));

        return $merchantPreferences;
    }

    /**
     * @param $subscriptionId
     * @param $planId
     * @return mixed
     */
    protected function _calculateRecurringPaymentStartDate($subscriptionId, $planId)
    {
        $subscription = $this->_subscriptionMgr->getSubscription($subscriptionId);
        $paymentPlan = $this->_paymentPlanDao->find($planId);
        $itemSequence = $this->_subscriptionMgr->getItemSequence($subscription);
        $userSubsManager = app('\Pley\User\UserSubscriptionManager');
        $nextChargeTime = $userSubsManager->getFirstRecurringChargeDate($subscription, $paymentPlan, $itemSequence);

        return $nextChargeTime;
    }

    /**
     * @param $subscriptionId
     * @param $planId
     * @param null $coupon
     * @return float
     */
    protected function _calculateSetupChargeAmount($subscriptionId, $planId, $coupon = null)
    {
        $vendorPaymentPlan = $this->_vendorPaymentPlanDao->findByPaymentPlan($planId,
            Pley\Enum\Shipping\ShippingZoneEnum::DEFAULT_ZONE_ID,
            Pley\Enum\PaymentSystemEnum::PAYPAL);
        $baseAmount = $vendorPaymentPlan->getTotal();
        $discountAmount = 0;
        $rewardDiscountAmount = $this->_rewardManager->getLoggedInUserReferralRewardAmount();

        if ($coupon) {
            $discountAmount = $this->_couponManager->calculateDiscount($vendorPaymentPlan, $baseAmount, $coupon);
        }
        $grandTotal = $baseAmount - $discountAmount - $rewardDiscountAmount;
        return ($grandTotal <= 0) ? 0 : $grandTotal;
    }

    /**
     * @param $type
     * @param $json
     * @return Pley\Entity\Paypal\PaypalLog
     */
    protected function _logRequestPayload($type, $json)
    {
        $logEntry = new Pley\Entity\Paypal\PaypalLog();
        $logEntry->setType($type)
            ->setRequestJson($json);
        $this->_paypalLogRepository->save($logEntry);
        return $logEntry;
    }

    /**
     * @param Pley\Entity\Paypal\PaypalLog $logEntry
     * @param $json
     * @return Pley\Entity\Paypal\PaypalLog
     */
    protected function _logResponsePayload(Pley\Entity\Paypal\PaypalLog $logEntry, $json)
    {
        $logEntry->setResponseJson($json);
        $this->_paypalLogRepository->save($logEntry);
        return $logEntry;
    }
}