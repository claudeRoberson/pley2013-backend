<?php

namespace operations\v1\Payment;

use Pley\Price\PriceManager;
use Pley\Subscription\SubscriptionManager;
use Pley;

/** @copyright Pley (c) 2017, All Rights Reserved */

/**
 * The <kbd>PaymentPlanController</kbd>
 *
 * @author Vsevolod Yatsiuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class PaymentPlanController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Coupon\CouponManager */
    protected $_priceManager;

    /** @var SubscriptionManager */
    protected $_subscriptionManager;

    /**
     * @var Pley\Dao\Payment\PaymentPlanDao
     */
    protected $_paymentPlanDao;

    /**
     * @var Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao
     */
    protected $_vendorPaymentPlanDao;


    /**
     * PaypalController constructor.
     * @param SubscriptionManager $subscriptionManager
     * @param PriceManager $priceManager
     * @param Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao
     * @param Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao
     */
    public function __construct(
        SubscriptionManager $subscriptionManager,
        PriceManager $priceManager,
        Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
        Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao
    )
    {
        $this->_subscriptionManager = $subscriptionManager;
        $this->_priceManager = $priceManager;
        $this->_paymentPlanDao = $paymentPlanDao;
        $this->_vendorPaymentPlanDao = $vendorPaymentPlanDao;
    }

    // GET /payment/plans

    /**
     * @return mixed
     */
    public function listPaymentPlans()
    {
        \RequestHelper::checkGetRequest();
        $subscriptions = $this->_subscriptionManager->getAllSubscriptions();

        $response = [];

        foreach ($subscriptions as $subscription){
            $paymentPlanIds = $subscription->getSignupPaymentPlanIdList();
            $response['subscriptions'][$subscription->getId()] = [
                'id'=> $subscription->getId(),
                'name' => $subscription->getName()
            ];
            foreach ($paymentPlanIds as $paymentPlanId){
                $paymentPlan = $this->_paymentPlanDao->find($paymentPlanId);
                $response['subscriptions'][$subscription->getId()]['plans'][$paymentPlan->getId()] = [
                    'id' => $paymentPlan->getId(),
                    'title' => $paymentPlan->getTitle(),
                    'description' => $paymentPlan->getDescription(),
                    'period' => $paymentPlan->getPeriod(),
                    'periodUnit' => Pley\Enum\PeriodUnitEnum::toString($paymentPlan->getPeriodUnit())
                ];
                $vendorPlans = $this->_vendorPaymentPlanDao->findAllByPaymentPlan($paymentPlan->getId());
                foreach ($vendorPlans as $vendorPlan) {
                    $response['subscriptions'][$subscription->getId()]['plans'][$paymentPlan->getId()]['associatedVendorPlans'][] = [
                        'id' => $vendorPlan->getId(),
                        'basePrice' => $vendorPlan->getBasePrice(),
                        'unitPrice' => $vendorPlan->getUnitPrice(),
                        'shippingPrice' => $vendorPlan->getShippingPrice(),
                        'total' => $vendorPlan->getTotal(),
                        'vendorPlanId' => $vendorPlan->getVPaymentPlanId(),
                        'vendorSystemId' => $vendorPlan->getVPaymentSystemId(),
                        'vendorSystemName' => Pley\Enum\PaymentSystemEnum::toString($vendorPlan->getVPaymentSystemId())
                    ];
                }
            }
        }
        return \Response::json($response);
    }
}