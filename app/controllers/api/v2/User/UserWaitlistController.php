<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace api\v2\User;

/**
 * The <kbd>UserWaitlistController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package 
 * @subpackage
 */
class UserWaitlistController extends \api\shared\AbstractBaseAuthController
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\User\UserProfileDao */
    protected $_userProfileDao;
    /** @var \Pley\Dao\User\UserAddressDao */
    protected $_userAddressDao;
    /** @var \Pley\Dao\Payment\UserPaymentMethodDao */
    protected $_userPaymentMethodDao;
    /** @var \Pley\Dao\Gift\GiftDao */
    protected $_giftDao;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionMgr;
    /** @var \Pley\User\UserSubscriptionManager */
    protected $_userSubscriptionMgr;
    /** @var \Pley\Coupon\CouponManager */
    protected $_couponMgr;
    /** @var \Pley\Repository\User\UserWaitlistRepository */
    protected $_userWaitlistRepo;
    
    public function __construct(
            \Pley\Db\AbstractDatabaseManager $dbManager,
            \Pley\Dao\User\UserProfileDao $userProfileDao,
            \Pley\Dao\User\UserAddressDao $userAddressDao,
            \Pley\Dao\Payment\UserPaymentMethodDao $userPaymentMethodDao,
            \Pley\Dao\Gift\GiftDao $giftDao,
            \Pley\Subscription\SubscriptionManager $subscriptionMgr,
            \Pley\User\UserSubscriptionManager $userSubscriptionMgr,
            \Pley\Coupon\CouponManager $couponMgr,
            \Pley\Repository\User\UserWaitlistRepository $userWaitlistRepo)
    {
        parent::__construct();
        
        $this->_dbManager = $dbManager;

        $this->_userProfileDao       = $userProfileDao;
        $this->_userAddressDao       = $userAddressDao;
        $this->_userPaymentMethodDao = $userPaymentMethodDao;
        $this->_giftDao              = $giftDao;

        $this->_subscriptionMgr     = $subscriptionMgr;
        $this->_userSubscriptionMgr = $userSubscriptionMgr;
        $this->_couponMgr           = $couponMgr;
        
        $this->_userWaitlistRepo = $userWaitlistRepo;
    }
    
    // GET /user/waitlist/inventory-for-release
    public function isInventoryForRelease()
    {
        \RequestHelper::checkGetRequest();
        \RequestHelper::checkJsonRequest();
        
        $userWaitList = $this->_userWaitlistRepo->findWaitlistByUser($this->_user->getId());
        
        // Check that at least one of the user's subscriptions have an available inventory, otherwise
        // an exception is thrown so Frontend can notify the customer.
        $isAnyInventoryAvailable = $this->_isReleaseInventoryAvailable($userWaitList);
        
        return \Response::json([
            'success'              => true,
            'isInventoryAvailable' => $isAnyInventoryAvailable,
        ]);
    }
    
    public function shareRelease()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        
        $json = \Input::json()->all();
        \ValidationHelper::validate($json, ['shareToken' => 'required']);
        
        $userWaitList = $this->_userWaitlistRepo->findWaitlistByUser($this->_user->getId());
        
        // Check that at least one of the user's subscriptions have an available inventory, otherwise
        // an exception is thrown so Frontend can notify the customer.
        $isAnyInventoryAvailable = $this->_isReleaseInventoryAvailable($userWaitList);
        
        // If there is no inventory available, we need to fail the call and notify
        if (!$isAnyInventoryAvailable) {
            $subscriptionIdList = $this->_getSubscriptionIdList($userWaitList);
            throw new \Pley\Exception\Waitlist\NoAvailableInventoryException($this->_user, $subscriptionIdList);
        }
        
        
        // At this point we know that at least one of the subscriptions of the whitelist has
        // inventory and thus can be released
        $releasedMap          = [];
        $waitlistRemainingMap = [];
        foreach ($userWaitList as $userWait) {
            $isReleased = $this->_processWaitlistEntry($userWait);
            
            if ($isReleased) {
                if (!isset($releasedMap[$userWait->getSubscriptionId()])) {
                    $releasedMap[$userWait->getSubscriptionId()] = 0;
                }
                $releasedMap[$userWait->getSubscriptionId()]++;
                
            } else {
                if (!isset($waitlistRemainingMap[$userWait->getSubscriptionId()])) {
                    $waitlistRemainingMap[$userWait->getSubscriptionId()] = 0;
                }
                $waitlistRemainingMap[$userWait->getSubscriptionId()]++;
            }
        }
        
        return \Response::json([
            'success'              => true,
            'userId'               => $this->_user->getId(),
            'releasedMap'          => $releasedMap,
            'waitlistRemainingMap' => $waitlistRemainingMap,
        ]);
    }
    
    /**
     * @param \Pley\Entity\User\UserWaitlist[] $userWaitList
     * @throws \Pley\Exception\Waitlist\NoAvailableInventoryException
     */
    protected function _isReleaseInventoryAvailable($userWaitList)
    {   
        $subscriptionIdList = $this->_getSubscriptionIdList($userWaitList);
        
        // Check if there is at least one inventory available from any possible waitlist entries for the user
        $isAnyInventoryAvailable = false;
        foreach ($subscriptionIdList as $subscriptionId) {
            $isAnyInventoryAvailable |= $this->_isInventoryAvailable($subscriptionId);
            if ($isAnyInventoryAvailable) {
                break;
            }
        }
        
        return (boolean)$isAnyInventoryAvailable;
    }
    
    /**
     * Returns if there is available inventory for the supplied subscription
     * @param int $subscriptionId
     * @return boolean
     */
    protected function _isInventoryAvailable($subscriptionId)
    {
        $subscription = $this->_subscriptionMgr->getSubscription($subscriptionId);
        $itemSequence = $this->_subscriptionMgr->getFullItemSequence($subscription);
        
        // We first assume the subscription is IN-ORDER
        $sequenceItem = $itemSequence[0];
        // But we need to check the pull type to retrieve the correct sequence item if not the default
        if ($subscription->getItemPullType() == \Pley\Enum\SubscriptionItemPullEnum::BY_SCHEDULE) {
            $schedIdx = $this->_subscriptionMgr->getActivePeriodIndex($subscription);
            $sequenceItem = $itemSequence[$schedIdx];
        }
        
        return $sequenceItem->hasAvailableSubscriptionUnits();
    }
    
    protected function _processWaitlistEntry(\Pley\Entity\User\UserWaitlist $userWait)
    {
        // If there is no more available inventory for the subscription, then we can't release this
        // Waitlist entry
        if (!$this->_isInventoryAvailable($userWait->getSubscriptionId())) {
            return false;
        }
        
        $that = $this;
        $newSubsResult = $this->_dbManager->transaction(function() use ($that, $userWait) {
            if (!empty($userWait->getGiftId())) {
                $newSubsResult = $that->_processWaitlistGift($userWait);

            } else {
                $newSubsResult = $that->_processWaitlistBilling($userWait);
            }
            
            $that->_userWaitlistRepo->releaseWaitlist($userWait);
            
            return $newSubsResult;
        });
        
        
        $this->_triggerNewSubscriptionEvent($newSubsResult, $userWait->getReferralToken());
        
        return true;
    }
    
    /**
     * @param \Pley\Entity\User\UserWaitlist[] $userWaitList
     * @return int[]
     */
    private function _getSubscriptionIdList($userWaitList)
    {
        $subscriptionIdList = [];
        foreach ($userWaitList as $userWait) {
            $subscriptionIdList[$userWait->getSubscriptionId()] = $userWait->getSubscriptionId();
        }
        
        return $subscriptionIdList;
    }
    
    private function _processWaitlistBilling(\Pley\Entity\User\UserWaitlist $userWait)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $userProfile = $this->_getUserProfileForWaitlist($userWait);
        $userAddress = $this->_getUserAddressForWaitlist($userWait);
        
        $paymentMethod = $this->_userPaymentMethodDao->find($this->_user->getDefaultPaymentMethodId());
        $subscriptionId = $userWait->getSubscriptionId();
        $paymentPlanId = $userWait->getPaymentPlanId();
        
        $coupon = null;
        if (!empty($userWait->getCouponId())) {
            $coupon = $this->_couponMgr->getCoupon($userWait->getCouponId());
        }

        //TODO: respect referral-token here - it's missing

        $newSubsResult = $this->_userSubscriptionMgr->addPaidSubscription(
            $this->_user, $userProfile, $paymentMethod, $subscriptionId, $paymentPlanId, $userAddress, $coupon
        );
        
        return $newSubsResult;
    }
    
    private function _processWaitlistGift(\Pley\Entity\User\UserWaitlist $userWait)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        $userProfile = $this->_getUserProfileForWaitlist($userWait);
        $userAddress = $this->_getUserAddressForWaitlist($userWait);
        
        $gift = $this->_giftDao->find($userWait->getGiftId());

        // Creating the Paid subscription and getting the list of Items added as a result
        $newSubsResult = $this->_userSubscriptionMgr->addGiftSubscription(
            $this->_user, $userProfile, $gift, $userAddress
        );

        return $newSubsResult;
    }
    
    /**
     * Retruns the UserProfile object to use for the supplied user waitlist object
     * @param \Pley\Entity\User\UserWaitlist $userWait
     * @return \Pley\Entity\User\UserProfile
     */
    private function _getUserProfileForWaitlist(\Pley\Entity\User\UserWaitlist $userWait)
    {
        if (!empty($userWait->getUserProfileId())) {
            $userProfile = $this->_userProfileDao->find($userWait->getUserProfileId());
            
        } else {
            // Retrieving the first and virtually only profile stored so far (two things can happen
            // if full account is registered before billing, we'll have a profile, but if account is
            // incomplete before billing, we may not have a user profile)
            $userProfileList = $this->_userProfileDao->findByUser($this->_user->getId());
            if (empty($userProfileList)) {
                $userProfile = \Pley\Entity\User\UserProfile::withDummy($this->_user->getId());
                $this->_userProfileDao->save($userProfile);
            } else {
                $userProfile = $userProfileList[0];
            }
        }
        
        return $userProfile;
        
    }
    
    /**
     * Retruns the UserAddress object to use for the supplied user waitlist object
     * @param \Pley\Entity\User\UserWaitlist $userWait
     * @return \Pley\Entity\User\UserAddress|null
     */
    private function _getUserAddressForWaitlist(\Pley\Entity\User\UserWaitlist $userWait)
    {
        if (!empty($userWait->getUserAddressId())) {
            $userAddress = $this->_userAddressDao->find($userWait->getUserAddressId());
            
        } else {
            // Retrieving the first and virtually only profile stored so far (two things can happen
            // if full account is registered before billing, we'll have a profile, but if account is
            // incomplete before billing, we may not have a user profile)
            $userAddressList = $this->_userAddressDao->findByUser($this->_user->getId());
            
            $userAddress = empty($userAddressList)? null : $userAddressList[0];
        }
        
        return $userAddress;
        
    }
    
    /**
     * Triggers the Event related to creating a new subscription
     * @param \Pley\User\NewSubscriptionResult $newSubsResult
     */
    private function _triggerNewSubscriptionEvent(
            \Pley\User\NewSubscriptionResult $newSubsResult, $referralToken = null)
    {
        $eventDataMap = [
            'user'                  => $this->_user,
            'newSubscriptionResult' => $newSubsResult,
        ];
        if (isset($referralToken)) {
            $eventDataMap['referralToken'] = $referralToken;
        }
        
        \Event::fire(\Pley\Enum\EventEnum::SUBSCRIPTION_CREATE, $eventDataMap);
    }
}
