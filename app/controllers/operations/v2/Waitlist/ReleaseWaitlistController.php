<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace operations\v2\Waitlist;

use Pley\Entity\Subscription\SequenceItem;
use Pley\Enum\WaitlistStatusEnum;

/**
 * The <kbd>ReleaseWaitlistController</kbd>
 *
 * @author Seva Yatsiuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package
 * @subpackage
 */
class ReleaseWaitlistController extends \operations\v1\BaseAuthController
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
    /** @var \Pley\Dao\User\UserDao */
    protected $_userDao;
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
        \Pley\Dao\User\UserDao $userDao,
        \Pley\Subscription\SubscriptionManager $subscriptionMgr,
        \Pley\User\UserSubscriptionManager $userSubscriptionMgr,
        \Pley\Coupon\CouponManager $couponMgr,
        \Pley\Repository\User\UserWaitlistRepository $userWaitlistRepo)
    {
        parent::__construct();

        $this->_dbManager = $dbManager;

        $this->_userProfileDao = $userProfileDao;
        $this->_userAddressDao = $userAddressDao;
        $this->_userPaymentMethodDao = $userPaymentMethodDao;
        $this->_giftDao = $giftDao;
        $this->_userDao = $userDao;

        $this->_subscriptionMgr = $subscriptionMgr;
        $this->_userSubscriptionMgr = $userSubscriptionMgr;
        $this->_couponMgr = $couponMgr;

        $this->_userWaitlistRepo = $userWaitlistRepo;
    }

    // GET /waitlist/items/subscription/{subscriptionId}

    public function getWaitlistItemsForSubscription($subscriptionId)
    {
        \RequestHelper::checkGetRequest();

        $response = [
            'waitlistReleaseBox' => [],
            'waitlistItems' => []
        ];
        $currentReleaseBox = $this->_getCurrentReleaseBox($subscriptionId);

        if(!$currentReleaseBox){
            throw new \Pley\Exception\Waitlist\NoCurrentBoxSetException($subscriptionId);
        }

        $response['waitlistReleaseBox'] = $currentReleaseBox->toArray();
        $response['waitlistReleaseBox']['inventoryAvailable'] = $this->_getInventoryAvailable($subscriptionId);

        $waitlistItems = $this->_userWaitlistRepo->findWaitlistBySubscription($subscriptionId);
        foreach ($waitlistItems as $waitlistItem) {
            $user = $this->_userDao->find($waitlistItem->getUserId());
            $waitlistData = $waitlistItem->toArray();
            $waitlistData['user']['id'] = $user->getId();
            $waitlistData['user']['email'] = $user->getEmail();
            $response['waitlistItems'][] = $waitlistData;
        }
        return \Response::json($response);
    }


    // POST /waitlist/release/subscription/{subscriptionId}
    public function releaseWaitlistItems($subscriptionId)
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();
        \ValidationHelper::validate($json, ['waitlistItems' => 'required']);

        $releasedMap[$subscriptionId] = 0;
        $waitlistRemainingMap[$subscriptionId] = 0;

        foreach ($json['waitlistItems'] as $waitlistItem) {

            $userWait = $this->_userWaitlistRepo->findWaitlist($waitlistItem['id']);
            $user = $this->_userDao->find($userWait->getUserId());
            $isAnyInventoryAvailable = $this->_isReleaseInventoryAvailable($userWait);

            // If there is no inventory available, we need to fail the call and notify
            if (!$isAnyInventoryAvailable) {
                $subscriptionIdList = $this->_getSubscriptionIdList($userWait);
                throw new \Pley\Exception\Waitlist\NoAvailableInventoryException($user, $subscriptionIdList);
            }

            try {
                $this->_processWaitlistEntry($userWait);
                $releasedMap[$userWait->getSubscriptionId()]++;
            } catch (\Pley\Exception\Payment\PaymentMethodDeclinedException $e) {

                $userWait->setPaymentAttemptAt(\Pley\Util\DateTime::date(time()))
                    ->setReleaseAttempts($userWait->getReleaseAttempts() + 1)
                    ->setStatus(WaitlistStatusEnum::PAYMENT_ATTEMPT_FAILED);
                $this->_userWaitlistRepo->saveWaitlist($userWait);

                \Event::fire(\Pley\Enum\EventEnum::WAITLIST_PAYMENT_FAILED, ['userWaitlist' => $userWait]);
                $waitlistRemainingMap[$userWait->getSubscriptionId()]++;
                continue;
            }
        }
        return \Response::json([
            'success' => true,
            'releasedMap' => $releasedMap,
            'remainingMap' => $waitlistRemainingMap
        ]);
    }

    /**
     * @param \Pley\Entity\User\UserWaitlist $userWaitList
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
     * Returns the current subscription sequence item
     * @param int $subscriptionId
     * @return \Pley\Entity\Subscription\SequenceItem
     */
    protected function _getWaitlistSequenceItem($subscriptionId)
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
        return $sequenceItem;
    }

    /**
     * Returns the number of available inventory for the supplied subscription
     * @param int $subscriptionId
     * @return int
     */
    protected function _getInventoryAvailable($subscriptionId)
    {
        $sequenceItem = $this->_getWaitlistSequenceItem($subscriptionId);
        return $sequenceItem->getSubscriptionUnitsAvailable();
    }

    /**
     * Returns if there is available inventory for the supplied subscription
     * @param int $subscriptionId
     * @return boolean
     */
    protected function _isInventoryAvailable($subscriptionId)
    {
        $sequenceItem = $this->_getWaitlistSequenceItem($subscriptionId);
        return $sequenceItem->hasAvailableSubscriptionUnits();
    }

    protected function _processWaitlistEntry(\Pley\Entity\User\UserWaitlist $userWait)
    {
        // If there is no more available inventory for the subscription, then we can't release this
        // Waitlist entry
        if (!$this->_isInventoryAvailable($userWait->getSubscriptionId())) {
            return false;
        }
        $user = $this->_userDao->find($userWait->getUserId());

        $that = $this;
        $newSubsResult = $this->_dbManager->transaction(function () use ($that, $userWait) {
            if (!empty($userWait->getGiftId())) {
                $newSubsResult = $that->_processWaitlistGift($userWait);

            } else {
                $newSubsResult = $that->_processWaitlistBilling($userWait);
            }

            $that->_userWaitlistRepo->releaseWaitlist($userWait);

            return $newSubsResult;
        });


        $this->_triggerNewSubscriptionEvent($newSubsResult, $user);

        return true;
    }

    /**
     * @param int $subscriptionId
     * @return \Pley\Entity\Subscription\Item
     */
    protected function _getCurrentReleaseBox($subscriptionId)
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
        return $this->_subscriptionMgr->getItem($sequenceItem->getItemId());
    }

    /**
     * @param \Pley\Entity\User\UserWaitlist $userWaitList
     * @return int[]
     */
    private function _getSubscriptionIdList($userWaitList)
    {
        $subscriptionIdList = [];
        $subscriptionIdList[$userWaitList->getSubscriptionId()] = $userWaitList->getSubscriptionId();

        return $subscriptionIdList;
    }

    private function _processWaitlistBilling(\Pley\Entity\User\UserWaitlist $userWait)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        $user = $this->_userDao->find($userWait->getUserId());

        $userProfile = $this->_getUserProfileForWaitlist($userWait);
        $userAddress = $this->_getUserAddressForWaitlist($userWait);

        $paymentMethod = $this->_userPaymentMethodDao->find($user->getDefaultPaymentMethodId());
        $subscriptionId = $userWait->getSubscriptionId();
        $paymentPlanId = $userWait->getPaymentPlanId();

        $coupon = null;
        if (!empty($userWait->getCouponId())) {
            $coupon = $this->_couponMgr->getCoupon($userWait->getCouponId());
        }
        $newSubsResult = $this->_userSubscriptionMgr->addPaidSubscription(
            $user, $userProfile, $paymentMethod, $subscriptionId, $paymentPlanId, $userAddress, $coupon
        );

        return $newSubsResult;
    }

    private function _processWaitlistGift(\Pley\Entity\User\UserWaitlist $userWait)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        $user = $this->_userDao->find($userWait->getUserId());

        $userProfile = $this->_getUserProfileForWaitlist($userWait);
        $userAddress = $this->_getUserAddressForWaitlist($userWait);

        $gift = $this->_giftDao->find($userWait->getGiftId());

        // Creating the Paid subscription and getting the list of Items added as a result
        $newSubsResult = $this->_userSubscriptionMgr->addGiftSubscription(
            $user, $userProfile, $gift, $userAddress
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
        $user = $this->_userDao->find($userWait->getUserId());

        if (!empty($userWait->getUserProfileId())) {
            $userProfile = $this->_userProfileDao->find($userWait->getUserProfileId());

        } else {
            // Retrieving the first and virtually only profile stored so far (two things can happen
            // if full account is registered before billing, we'll have a profile, but if account is
            // incomplete before billing, we may not have a user profile)
            $userProfileList = $this->_userProfileDao->findByUser($user->getId());
            if (empty($userProfileList)) {
                $userProfile = \Pley\Entity\User\UserProfile::withDummy($user->getId());
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
        $user = $this->_userDao->find($userWait->getUserId());

        if (!empty($userWait->getUserAddressId())) {
            $userAddress = $this->_userAddressDao->find($userWait->getUserAddressId());

        } else {
            // Retrieving the first and virtually only profile stored so far (two things can happen
            // if full account is registered before billing, we'll have a profile, but if account is
            // incomplete before billing, we may not have a user profile)
            $userAddressList = $this->_userAddressDao->findByUser($user->getId());

            $userAddress = empty($userAddressList) ? null : $userAddressList[0];
        }

        return $userAddress;

    }

    /**
     * Triggers the Event related to creating a new subscription
     * @param \Pley\User\NewSubscriptionResult $newSubsResult
     * @param \Pley\Entity\User\User $user
     */
    private function _triggerNewSubscriptionEvent(
        \Pley\User\NewSubscriptionResult $newSubsResult, \Pley\Entity\User\User $user)
    {
        $eventDataMap = [
            'user' => $user,
            'newSubscriptionResult' => $newSubsResult,
        ];
        if (isset($referralToken)) {
            $eventDataMap['referralToken'] = $referralToken;
        }

        \Event::fire(\Pley\Enum\EventEnum::SUBSCRIPTION_CREATE, $eventDataMap);
    }
}
