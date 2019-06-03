<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace api\v1\User;

use Pley\Enum\PaymentSystemEnum;
use Pley\Enum\Shipping\ShipmentStatusEnum;
use Pley\Util\Time\DateTime;

class UserAccountController extends \api\v1\BaseAuthController
{
    /** @var \Pley\User\UserManager */
    protected $_userManager;
    /** @var \Pley\Dao\User\UserProfileDao **/
    protected $_userProfileDao;
    /** @var \Pley\Dao\User\UserAddressDao */
    protected $_userAddressDao;
    /** @var \Pley\Dao\Payment\UserPaymentMethodDao **/
    protected $_userPymtMethodDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao **/
    protected $_profileSubsDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionPlanDao **/
    protected $_profileSubsPlanDao;
    /** @var \Pley\Dao\User\UserIncompleteRegistrationDao */
    protected $_userIncompleteRegDao;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_paymentPlanDao;
    /** @var \Pley\Dao\Gift\GiftDao */
    protected $_giftDao;
    /** @var \Pley\Dao\Gift\GiftPriceDao */
    protected $_giftPriceDao;
    /** @var \Pley\Subscription\SubscriptionManager **/
    protected $_subscriptionMgr;
    /** @var \Pley\Payment\PaymentManagerFactory */
    protected $_paymentManagerFactory;
    /** @var \Pley\Coupon\CouponManager */
    protected $_couponManager;
    /** @var \Pley\Referral\RewardManager */
    protected $_rewardManager;
    /** @var \Pley\Shipping\AbstractShipmentManager */
    protected $_shipmentMgr;
    
    public function __construct(
            \Pley\User\UserManager $userManager,
            \Pley\Dao\User\UserProfileDao $userProfileDao,
            \Pley\Dao\User\UserAddressDao $userAddressDao,
            \Pley\Dao\Payment\UserPaymentMethodDao $userPaymentMethodDao,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubscriptionDao,
            \Pley\Dao\Profile\ProfileSubscriptionPlanDao $profileSubscriptionPlanDao,
            \Pley\Dao\User\UserIncompleteRegistrationDao $userIncompleteRegDao,
            \Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
            \Pley\Dao\Gift\GiftDao $giftDao,
            \Pley\Dao\Gift\GiftPriceDao $giftPriceDao,
            \Pley\Subscription\SubscriptionManager $subscriptionMgr,
            \Pley\Payment\PaymentManagerFactory $paymentManagerFactory,
            \Pley\Coupon\CouponManager $couponManager,
            \Pley\Referral\RewardManager $rewardManager,
            \Pley\Shipping\Impl\EasyPost\ShipmentManager $shipmentMgr
    )
    {   
        parent::__construct();
        
        $this->_userManager          = $userManager;
        
        $this->_userProfileDao       = $userProfileDao;
        $this->_userAddressDao       = $userAddressDao;
        $this->_userPymtMethodDao    = $userPaymentMethodDao;
        $this->_profileSubsDao       = $profileSubscriptionDao;
        $this->_profileSubsPlanDao   = $profileSubscriptionPlanDao;
        $this->_userIncompleteRegDao = $userIncompleteRegDao;
        $this->_paymentPlanDao       = $paymentPlanDao;
        $this->_giftDao              = $giftDao;
        $this->_giftPriceDao         = $giftPriceDao;

        $this->_subscriptionMgr       = $subscriptionMgr;
        $this->_paymentManagerFactory = $paymentManagerFactory;

        $this->_couponManager = $couponManager;
        $this->_rewardManager = $rewardManager;
        $this->_shipmentMgr   = $shipmentMgr;
    }

    // GET /user/account
    public function account()
    {
        \RequestHelper::checkGetRequest();
        
        $userAddressMap       = $this->_userManager->getAddressMap($this->_user);
        $userProfileMap       = $this->_userManager->getProfileMap($this->_user);
        $userPaymentMethodMap = $this->_userManager->getPaymentMethodMap($this->_user);
        $userSubscriptionMap  = $this->_userManager->getSubscriptionMap($this->_user);
        $userCouponMap        = $this->_couponManager->getCouponsUsedByUser($this->_user);
        $userWaitlistMap      = $this->_userManager->getWaitlistMap($this->_user);

        $arrayResponse = [];
        $arrayResponse['user']                 = $this->_parseUser($this->_user);
        $arrayResponse['userAddressMap']       = $this->_parseMap($userAddressMap, '_parseUserAddress');
        $arrayResponse['userProfileMap']       = $this->_parseMap($userProfileMap, '_parseUserProfile');
        $arrayResponse['userWaitlistMap']      = $this->_parseMap($userWaitlistMap, '_parseUserWaitlist');
        $arrayResponse['userPaymentMethodMap'] = $this->_parseMap($userPaymentMethodMap, '_parseUserPaymentMethod');
        $arrayResponse['userSubscriptionMap']  = $this->_parseMap($userSubscriptionMap, '_parseProfileSubscription');
        $arrayResponse['userCouponMap']        = $this->_parseMap($userCouponMap, '_parseCoupon');
        $arrayResponse['userCreditMap']        = $this->_parseUserPaymentCredits($this->_user);

        // Collecting the subscription IDs the user is subscribed to so we can retrieve the Subscription
        // Metadata info and then adding it to the response array
        $subscriptionMap = [];
        foreach ($userSubscriptionMap as $userSubscription) {
            $subsId = $userSubscription->getSubscriptionId();
            if (!isset($subscriptionMap[$subsId])) {
                $subscriptionMap[$subsId] = $this->_subscriptionMgr->getSubscription($subsId);
            }
        }
        $arrayResponse['subscriptionMap'] = $this->_parseMap($subscriptionMap, '_parseSubscription');
        
        $this->_setRegistrationMeta($arrayResponse, $userSubscriptionMap);
        
        return \Response::json($arrayResponse);
    }
    
    // GET /user/account/for-subscription
    // This is a much reduced version of the `account()` method, as frontend only seeks to retrieve
    // information to present the user with options to add a new subscription, so no need to add
    // overhead of retrieving extra data.
    public function forNewSubscription()
    {
        \RequestHelper::checkGetRequest();
        
        $userAddressMap       = $this->_userManager->getAddressMap($this->_user);
        $userProfileMap       = $this->_userManager->getProfileMap($this->_user);
        $defaultPaymentMethod = $this->_userManager->getDefaultPaymentMethod($this->_user);
        
        $arrayResponse = [];
        $arrayResponse['userAddressMap']       = $this->_parseMap($userAddressMap, '_parseUserAddress');
        $arrayResponse['userProfileMap']       = $this->_parseMap($userProfileMap, '_parseUserProfile');
        $arrayResponse['defaultPaymentMethod'] = isset($defaultPaymentMethod) ? 
                $this->_parseUserPaymentMethod($defaultPaymentMethod) : null;
        
        return \Response::json($arrayResponse);
    }
    
    protected function _parseUser(\Pley\Entity\User\User $user)
    {
        return [
            'id'                     => $user->getId(),
            'firstName'              => $user->getFirstName(),
            'lastName'               => $user->getLastName(),
            'email'                  => $user->getEmail(),
            'createdAt'              => $user->getCreatedAt(),
            'defaultPaymentMethodId' => $user->getDefaultPaymentMethodId(),
        ];
    }
    
    protected function _parseUserAddress(\Pley\Entity\User\UserAddress $address)
    {
        return [
            'id'        => $address->getId(),
            'street1'   => $address->getStreet1(),
            'street2'   => $address->getStreet2(),
            'phone'     => $address->getPhone(),
            'city'      => $address->getCity(),
            'state'     => $address->getState(),
            'zipCode'   => $address->getZipCode(),
            'createdAt' => $address->getCreatedAt(),
            'updatedAt' => $address->getUpdatedAt(),
        ];
    }
    
    protected function _parseUserProfile(\Pley\Entity\User\UserProfile $profile)
    {
        return [
            'id'        => $profile->getId(),
            'firstName' => $profile->getFirstName(),
            'lastName'  => $profile->getLastName(),
            'gender'    => $profile->getGender(),
            'birthDate' => $profile->getBirthDate(),
            'shirtSize' => $profile->getTypeShirtSizeId(),
            'createdAt' => $profile->getCreatedAt(),
            'updatedAt' => $profile->getUpdatedAt(),
        ];
    }

    protected function _parseUserWaitlist(\Pley\Entity\User\UserWaitlist $userWaitlist)
    {
        return [
            'id' => $userWaitlist->getId(),
            'subscriptionId' => $userWaitlist->getSubscriptionId(),
            'userProfileId' => $userWaitlist->getUserProfileId(),
            'paymentPlanId' => $userWaitlist->getPaymentPlanId(),
            'createdAt' => $userWaitlist->getCreatedAt(),
            'addressId' => $userWaitlist->userAddress->getId(),
            'paymentMethodId' => ($userWaitlist->paymentMethod) ? $userWaitlist->paymentMethod->getId() : null,
            'subscription' => [
                'id' => $userWaitlist->subscription->getId(),
                'name' => $userWaitlist->subscription->getName(),
                'period' => $userWaitlist->subscription->getPeriod(),
                'periodUnit' => $userWaitlist->subscription->getPeriodUnit(),
            ]
        ];
    }
    
    protected function _parseUserPaymentMethod(\Pley\Entity\Payment\UserPaymentMethod $paymentMethod)
    {
        $paymentManager = $this->_paymentManagerFactory->getManager($paymentMethod->getVPaymentSystemId());
        $card           = $paymentManager->getCard($this->_user, $paymentMethod);

        return [
            'id'        => $paymentMethod->getId(),
            'brand'     => $card->getBrand(),
            'last4'     => $card->getNumber(),
            'expMonth'  => $card->getExpirationMonth(),
            'expYear'   => $card->getExpirationYear(),
            'type'      => $card->getType(),
            'createdAt' => $paymentMethod->getCreatedAt(),
            'updatedAt' => $paymentMethod->getUpdatedAt(),
        ];
    }

    protected function _parseUserPaymentCredits(\Pley\Entity\User\User $user)
    {
        $arrayResponse = [];

        if(!$user->getVPaymentSystemId() || $user->getVPaymentSystemId() === PaymentSystemEnum::PAYPAL){
            return $arrayResponse;
        }
        
        $paymentManager = $this->_paymentManagerFactory->getManager($user->getVPaymentSystemId());
        $credits = $paymentManager->getCreditInfo($user);

        foreach ($credits as $credit){
            $arrayResponse[] = $credit->toArray();
        }
        return $arrayResponse;
    }
    
    protected function _parseProfileSubscription(\Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        // Retrieving the subscription payment plans (they would be empty if this is a Gift subscrition)
        $subsPlanMap = $this->_userManager->getSubscriptionPlanMap($profileSubs);
        $currentPlan = $this->_userManager->getRecentSubcriptionPlan($profileSubs);
        $subscription = $this->_subscriptionMgr->getSubscription($profileSubs->getSubscriptionId());
        $endOfBillingPeriodDate = null;

        // If the subscription is still valid, retrieve the end of the billing period
        $inactiveSubsStatusList = [
            \Pley\Enum\SubscriptionStatusEnum::CANCELLED, \Pley\Enum\SubscriptionStatusEnum::GIFT
        ];
        if (isset($currentPlan) && !in_array($currentPlan->getStatus(), $inactiveSubsStatusList)) {
            $paymentMgr  = \Pley\Payment\PaymentManagerFactory::getManager($currentPlan->getVPaymentSystemId());
            $paymentSubs = $paymentMgr->getSubscription($this->_user, $currentPlan);
            
            $endOfBillingPeriodDate = $paymentSubs->getPeriodDateEnd();
        }
        
        // Retrieving all Billing transactions and Shipments
        $transactionList    = $this->_userManager->getSubscriptionTransactionList($profileSubs);
        $shipmentCollection = $this->_userManager->getSubscriptionShipmentCollection($profileSubs);
        $canSkipABox        = $this->_subscriptionMgr->canPauseProfileSubscription($profileSubs);

        $subscriptionActivePeriod = $this->_subscriptionMgr->getSubscriptionDates($subscription);
        $nextDeliveryStartDate = $subscriptionActivePeriod->getDeliveryStartPeriodDef()->getTimestamp();

        if($nextDeliveryStartDate < time()){
            $subscriptionNextPeriod = $this->_subscriptionMgr->getSubscriptionDates($subscription, $subscriptionActivePeriod->getIndex() + 1);
            $nextDeliveryStartDate = $subscriptionNextPeriod->getDeliveryStartPeriodDef()->getTimestamp();
        }
        // Retrieving the gift info if there is any
        $gift = null;
        if (!empty($profileSubs->getGiftId())) {
            $gift = $this->_giftDao->find($profileSubs->getGiftId());
        }
        
        $profileSubsData = [
            'id'                     => $profileSubs->getId(),
            'subscriptionId'         => $profileSubs->getSubscriptionId(),
            'profileId'              => $profileSubs->getUserProfileId(),
            'addressId'              => $profileSubs->getUserAddressId(),
            'paymentMethodId'        => $profileSubs->getUserPaymentMethodId(),
            'endOfBillingPeriodDate' => $endOfBillingPeriodDate,
            'status'                 => $profileSubs->getStatus(),
            'canSkipBox'             => $canSkipABox,
            'isAutoRenew'            => $profileSubs->isAutoRenew(),
            'isShowNatGeoLink'       => false, // By default false, and below check if we can enable
            'giftId'                 => $profileSubs->getGiftId(),
            'createdAt'              => $profileSubs->getCreatedAt(),
            'giftMap'                => $this->_parseGift($gift),
            'planMap'                => $this->_parseMap($subsPlanMap, '_parseProfileSubsPlan'),
            'transactionList'        => $this->_parseMap($transactionList, '_parseProfileSubsTransac'),
            'nextBoxDeliveryStart'   => $nextDeliveryStartDate,
            'shipmentMap'            => [
                'current'       => $this->_parseProfileSubsShipment($shipmentCollection->getCurrent(), ['isShowItem' => true]),
                'pendingList'   => $this->_parseMap($shipmentCollection->getPendingList(), '_parseProfileSubsShipment', ['isShowItem' => false]),
                'deliveredList' => $this->_parseMap($shipmentCollection->getDeliveredList(), '_parseProfileSubsShipment', ['isShowItem' => true]),
            ],
        ];
        
        if ($profileSubs->getSubscriptionId() == \Pley\Enum\SubscriptionEnum::NATIONAL_GEOGRAPHIC) {
            $shipmentCollection = $this->_userManager->getSubscriptionShipmentCollection($profileSubs);
            
            // If there is at least one box delivered or the current box is in transit, then we can
            // show the NatGeo link
            $hasDelivered       = !empty($shipmentCollection->getDeliveredList());
            $isCurrentInTransit = !empty($shipmentCollection->getCurrent()) 
                               && $shipmentCollection->getCurrent()->getStatus() == \Pley\Enum\Shipping\ShipmentStatusEnum::IN_TRANSIT;
                    
            $isAnyShipped = $hasDelivered || $isCurrentInTransit;
            $profileSubsData['isShowNatGeoLink'] = $isAnyShipped;
        }
        
        return $profileSubsData;
    }
    
    protected function _parseProfileSubsPlan(\Pley\Entity\Profile\ProfileSubscriptionPlan $profileSubsPlan)
    {
        $paymentPlan = $this->_paymentPlanDao->find($profileSubsPlan->getPaymentPlanId());

        return [
            'id'              => $profileSubsPlan->getId(),
            'status'          => $profileSubsPlan->getStatus(),
            'isAutoRenew'     => $profileSubsPlan->isAutoRenew(),
            'autoRenewStopAt' => $profileSubsPlan->getAutoRenewStopAt(),
            'cancelAt'        => $profileSubsPlan->getCancelAt(),
            'createdAt'       => $profileSubsPlan->getCreatedAt(),
            'paymentPeriod'   => $paymentPlan->getPeriod(),
        ];
    }
    
    protected function _parseProfileSubsTransac(\Pley\Entity\Profile\ProfileSubscriptionTransaction $transaction)
    {
        return [
            'id'                 => $transaction->getId(),
            'subscriptionPlanId' => $transaction->getProfileSubscriptionPlanId(),
            'transactionType'    => $transaction->getTransactionType(),
            'transactionAt'      => $transaction->getTransactionAt(),
            'baseAmount'         => $transaction->getBaseAmount(),
            'amount'             => $transaction->getAmount(),
            'discountAmount'     => $transaction->getDiscountAmount(),
            'discountType'       => $transaction->getDiscountType(),
            'discountSourceId'   => $transaction->getDiscountSourceId()
        ];
    }
    
    protected function _parseProfileSubsShipment($shipment, $parseFlagMap)
    {
        if (empty($shipment)) {
            return null;
        }
        
        // We want to do type checking, but to allow the object to be NULL on the parameter would mean
        // that the first parameter is optional while the second one is required, which is an immediate
        // PHP Warning, but a feature/deficiency of how Class type check works
        // So to prevent the PHP Warning but do the type check we do the check here.
        if (!$shipment instanceof \Pley\Entity\Profile\ProfileSubscriptionShipment) {
            $message = 'Argument 1 passed to _parseProfileSubsShipment() '
                     . 'must be an instance of \Pley\Entity\Profile\ProfileSubscriptionShipment, '
                     . 'instance of ' . get_class($shipment) . ' given';
            trigger_error($message, E_USER_ERROR);
        }
        
        $isShowItem = (boolean)$parseFlagMap['isShowItem'];
        
        $sequenceItem = $this->_subscriptionMgr->getScheduledItem($shipment);
        $item         = $isShowItem && !empty($sequenceItem->getItemId())?
                $this->_subscriptionMgr->getItem($sequenceItem->getItemId()) : null;
        
        return [
            'id'          => $shipment->getId(),
            'status'      => $shipment->getStatus(),
            'source'      => [
                'type' => $shipment->getShipmentSourceType(),
                'id'   => $shipment->getShipmentSourceId(),
            ],
            'trackingInfo' => [
                'hasTracking' => ($shipment->getTrackingNo()) ? true : false,
                'status' => ShipmentStatusEnum::asString($shipment->getStatus()),
                'carrier' => ($shipment->getCarrierId()) ? \Pley\Shipping\Impl\EasyPost\CarrierMapper::$carrierMap[$shipment->getCarrierId()] : null,
                'trackingUrl' => ($shipment->getTrackingNo()) ? $this->_shipmentMgr->getTrackingUrl($shipment->getCarrierId(),
                    $shipment->getTrackingNo()) : null,
                'trackingNumber' => ($shipment->getTrackingNo()) ? $shipment->getTrackingNo() : null,
            ],
            'shirtSize'   => $shipment->getShirtSize(),
            'item'        => empty($item) ? null : $item->getName(),
            'scheduleMap' => [
                'deliveryTimeStart' => $sequenceItem->getDeliveryStartTime(),
                'deliveryTimeEnd'   => $sequenceItem->getDeliveryEndTime(),
            ],
            'addressMap'  => empty($shipment->getStreet1()) ? null : [
                'street1' => $shipment->getStreet1(),
                'street2' => $shipment->getStreet2(),
                'city'    => $shipment->getCity(),
                'state'   => $shipment->getState(),
                'zip'     => $shipment->getZip(),
                'country' => $shipment->getCountry(),
                    ],
            'label'       => empty($shipment->getLabelUrl()) ? null : [
                'carrierId'   => $shipment->getCarrierId(),
                'serviceId'   => $shipment->getCarrierServiceId(),
                'trackingNo'  => $shipment->getTrackingNo(),
            ],
            'shippedAt'   => $shipment->getShippedAt(),
            'deliveredAt' => $shipment->getDeliveredAt(),
        ];
    }
    
    protected function _parseGift(\Pley\Entity\Gift\Gift $gift = null)
    {
        if (empty($gift)) {
            return null;
        }
        
        $giftPrice   = $this->_giftPriceDao->find($gift->getGiftPriceId());
        $paymentPlan = $this->_paymentPlanDao->find($giftPrice->getEquivalentPaymentPlanId());
        
        return [
            'id'            => $gift->getId(),
            'fromFirstName' => $gift->getFromFirstName(),
            'fromLastName'  => $gift->getFromLastName(),
            'paymentPeriod' => $paymentPlan->getPeriod(),
        ];
    }
    
    protected function _parseSubscription(\Pley\Entity\Subscription\Subscription $subscription)
    {
        return [
            'id'         => $subscription->getId(),
            'name'       => $subscription->getName(),
            'period'     => $subscription->getPeriod(),
            'periodUnit' => $subscription->getPeriodUnit(),
        ];
    }

    protected function _parseCoupon(\Pley\Entity\Coupon\Coupon $coupon)
    {
        return [
            'id'             => $coupon->getId(),
            'code'           => $coupon->getCode(),
            'type'           => $coupon->getType(),
            'discountAmount' => $coupon->getDiscountAmount(),
        ];
    }
    
    /**
     * Helper method to parse a map of objects into their array representation.
     * <p>This is used to reduce lines of code and make the parse methods more atomic handling only
     * one entity parsing, and this method the loop around that parsing.</p>
     * @param object[] $objectMap
     * @param string   $parseMethod
     * @param array    $parseFlagMap (Optional)<br/>used to supplie additional info to the parse method
     * @return array
     */
    private function _parseMap($objectMap, $parseMethod, $parseFlagMap = null)
    {
        $arrayMap = [];
        foreach ($objectMap as $key => $object) {
            if (isset($parseFlagMap)) {
                $arrayMap[$key] = $this->$parseMethod($object, $parseFlagMap);
                
            } else {
                $arrayMap[$key] = $this->$parseMethod($object);
            }         
        }
        
        return $arrayMap;
    }
    
    /**
     * Helper method to add the registration information in case user didn't complete the process.
     * @param array $responseMap
     * @param \Pley\Entity\Profile\ProfileSubscription[] $profileSubsMap
     */
    private function _setRegistrationMeta(&$responseMap, $profileSubsMap)
    {
        $addressList = $this->_userAddressDao->findByUser($this->_user->getId());
        $responseMap['hasAddress'] = !empty($addressList);
        
        $profileList = $this->_userProfileDao->findByUser($this->_user->getId());
        if (empty($profileList)) { $responseMap['hasProfile'] = false; }
        else { $responseMap['hasProfile'] = !$profileList[0]->isDummy(); }
        
        $responseMap['registrationInit'] = true;
        if (empty($profileSubsMap)) {
            $responseMap['registrationInit'] = false;
            
            $incompleteUserReg = $this->_userIncompleteRegDao->findByUser($this->_user->getId());
            if (!empty($incompleteUserReg)) {
                $responseMap['registrationInit'] = [
                    'subscriptionId' => $incompleteUserReg->getSubscriptionId(),
                    'paymentPlanId'  => $incompleteUserReg->getPaymentPlanId(),
                ];
            }
            
        }
        
        $acquisitionCoupon = $this->_couponManager->getAcquisitionCoupon(null);
        $responseMap['inviteInfoMap'] = [
            'inviteRewardAmount'     => $this->_rewardManager->getAcquisitionRewardAmount(null),
            'referralDiscountAmount' => $acquisitionCoupon->getDiscountAmount(),
        ];
    }
    
}

