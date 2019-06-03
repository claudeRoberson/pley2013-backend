<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace api\v1\Subscription;

use \Pley\Config\ConfigInterface as Config;

/**
 * The <kbd>SubscriptionController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @author Anurag Phadke (anuragp@pley.com)
 * @version 1.0
 */
class SubscriptionController extends \api\v1\BaseController
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionMgr;
    /** @var \Pley\Dao\Payment\PaymentPlanDao * */
    protected $_paymentPlanDao;
    /** @var \Pley\Price\PriceManager */
    protected $_priceManager;
    /** @var \Pley\Coupon\CouponManager */
    protected $_couponManager;

    public function __construct(
        Config $config,
        \Pley\Subscription\SubscriptionManager $subscriptionMgr,
        \Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
        \Pley\Price\PriceManager $priceManager,
        \Pley\Coupon\CouponManager $couponManager
    )
    {
        $this->_config = $config;
        $this->_subscriptionMgr = $subscriptionMgr;
        $this->_paymentPlanDao = $paymentPlanDao;
        $this->_priceManager = $priceManager;
        $this->_couponManager = $couponManager;
    }

    // GET /subscription/{id}/signup
    public function infoForSignup($subscriptionId, $countryCode = null, $stateCode = null)
    {
        \RequestHelper::checkGetRequest();

        $couponCode = null;
        if (\Input::has('couponCode'))
        {
            $couponCode = \Input::get('couponCode');
        }

        $subscription = $this->_subscriptionMgr->getSubscription($subscriptionId);
        \ValidationHelper::entityExist($subscription, \Pley\Entity\Subscription\Subscription::class);

        $activePeriodIdx = $this->_subscriptionMgr->getActivePeriodIndex($subscription);
        $itemSequence = $this->_subscriptionMgr->getItemSequence($subscription);

        $isItemAvailable = !empty($itemSequence);

        $responseStructure = [
            'subscription' => [
                'name' => $subscription->getName(),
                'description' => $subscription->getDescription(),
                'period' => $subscription->getPeriod(),
                'periodUnit' => $subscription->getPeriodUnit(),
            ],
            'allowedCountries' => $this->_config->get('shipping.allowedCountries'),
            'isItemAvailable' => $isItemAvailable,
            'scheduleList' => [],
            'paymentPlanList' => [],
        ];

        // if every single future item has been sold out/reserved, then just return the info
        // No need to caclculate schedules and show payment plans, as users can't subscribe
        if (!$isItemAvailable) {
            return \Response::json($responseStructure);
        }

        $paymentPlanList = $this->_subscriptionMgr->getSubscriptionPaymentPlanList($subscription);
        /* @var $paymentPlan \Pley\Entity\Payment\PaymentPlan */
        foreach ($paymentPlanList as $key => $paymentPlan) {
            $vendorPaymentPlan = $this->_subscriptionMgr->getPlanPriceForCountry($paymentPlan, $countryCode, $stateCode);
            if (!$vendorPaymentPlan) {
                throw new \Exception('No payment plan found for the specified country.');
            }
            $responseStructure['paymentPlanList'][$key] = $this->_formatPaymentPlan($paymentPlan);

            $responseStructure['paymentPlanList'][$key]['price'] = [
                'base' => $this->_priceManager->toCountryCurrency($vendorPaymentPlan->getBasePrice(), $countryCode),
                'unit' => $this->_priceManager->toCountryCurrency($vendorPaymentPlan->getUnitPrice(), $countryCode),
                'shipping' => $this->_priceManager->toCountryCurrency($vendorPaymentPlan->getShippingPrice(), $countryCode),
                'total' => $this->_priceManager->toCountryCurrency($vendorPaymentPlan->getTotal(), $countryCode),
                'currencyCode' => $this->_priceManager->getCountryCurrencyCode($countryCode),
                'currencySign' => $this->_priceManager->getCountryCurrencySign($countryCode),
                'coupon' => $this->_formatCouponData($subscription, $vendorPaymentPlan, $couponCode)
            ];
            $responseStructure['paymentPlanList'][$key]['base_currency_price'] = [
                'base' => $this->_priceManager->toBaseCurrency($vendorPaymentPlan->getBasePrice()),
                'unit' => $this->_priceManager->toBaseCurrency($vendorPaymentPlan->getUnitPrice()),
                'shipping' => $this->_priceManager->toBaseCurrency($vendorPaymentPlan->getShippingPrice()),
                'total' => $this->_priceManager->toBaseCurrency($vendorPaymentPlan->getTotal()),
                'currencyCode' => $this->_priceManager->getBaseCurrencyCode(),
                'currencySign' => $this->_priceManager->getBaseCurrencySign(),
                'coupon' => $this->_formatCouponData($subscription, $vendorPaymentPlan, $couponCode)
            ];
        }

        $firstAvailableItem = $itemSequence[0];
        // If the first available item index is after the current period, that means that we are
        // sold out of all current and previous items that could be shipped for the current period.
        // as such we need to inform this and provide information about the next available one.
        if ($firstAvailableItem->getSequenceIndex() > $activePeriodIdx) {
            $responseStructure['scheduleList'][] = $this->_formatSequenceItem(null);
        }
        $responseStructure['scheduleList'][] = $this->_formatSequenceItem($firstAvailableItem);

        return \Response::json($responseStructure);
    }

    // GET /subscription/{id}/signup/{countryCode}
    public function signupInfoByCountry($subscriptionId, $countryCode)
    {
        return $this->infoForSignup($subscriptionId, $countryCode);
    }

    // GET /subscription/{id}/signup/{countryCode}/{stateCode}
    public function signupInfoByCountryState($subscriptionId, $countryCode, $stateCode)
    {
        return $this->infoForSignup($subscriptionId, $countryCode, $stateCode);
    }

    private function _formatPaymentPlan(\Pley\Entity\Payment\PaymentPlan $paymentPlan)
    {
        return [
            'id' => $paymentPlan->getId(),
            'title' => $paymentPlan->getTitle(),
            'description' => $paymentPlan->getDescription(),
            'period' => $paymentPlan->getPeriod(),
            'periodUnit' => $paymentPlan->getPeriodUnit(),
            'sortOrder' => $paymentPlan->getSortOrder(),
            'isFeatured' => $paymentPlan->getIsFeatured()
        ];
    }

    private function _formatCouponData(\Pley\Entity\Subscription\Subscription $subscription, \Pley\Entity\Payment\VendorPaymentPlan $vendorPaymentPlan, $couponCode = null)
    {
        $couponData = [
            'applied' => false,
            'couponCode' => null,
            'couponTitle' => null,
            'couponSubtitle' => null,
            'discountAmount' => 0
        ];
        if ($couponCode) {
            try {
                $coupon = $this->_couponManager->validateCouponCode(
                    $couponCode,
                    \Pley\Entity\User\User::dummy(),
                    $subscription->getId(),
                    $vendorPaymentPlan->getPaymentPlanId()
                );
                $discountAmount = $this->_couponManager->calculateDiscount(
                    $vendorPaymentPlan,
                    $vendorPaymentPlan->getTotal(),
                    $coupon
                );
                $couponData = [
                    'applied' => true,
                    'couponCode' => $coupon->getCode(),
                    'couponTitle' => $coupon->getTitle(),
                    'couponSubtitle' => $coupon->getSubtitle(),
                    'couponLabelUrl' => $coupon->getLabelUrl(),
                    'discountAmount' => $discountAmount
                ];

            } catch (\Exception $e) {
                return $couponData;
            }
        }
        return $couponData;
    }

    private function _formatSequenceItem(\Pley\Entity\Subscription\SequenceItem $seqItem = null)
    {
        if ($seqItem == null) {
            return [
                'id' => null,
                'isSoldOut' => true,
                'unitCount' => 0,
                'deadlineTime' => null,
                'deliveryStartTime' => null,
                'deliveryEndTime' => null,
            ];
        }

        $unitsLeft = $seqItem->getSubscriptionUnitsAvailable();
        // However, because we want to show some sense of limitation, the frontend will always
        // display "Less than 100" when the units left is greater than 99, otherwise it will
        // show the real value.
        // One thing to note, is that we want to match this from the backend, to avoid a
        // person sniffing the calls to get the actual number and post it online, so we reset
        // it to NULL if the amount is >= 100 to indicate the Frontend to show the right message
        if ($unitsLeft >= 100) {
            $unitsLeft = null;
        }

        return [
            'id' => $seqItem->getId(),
            'isSoldOut' => false,
            'unitCount' => $unitsLeft,
            'deadlineTime' => $seqItem->getDeadlineTime(),
            'deliveryStartTime' => $seqItem->getDeliveryStartTime(),
            'deliveryEndTime' => $seqItem->getDeliveryEndTime(),
        ];
    }
}
