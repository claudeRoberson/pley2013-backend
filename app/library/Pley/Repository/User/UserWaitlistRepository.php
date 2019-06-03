<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\User;

/**
 * The <kbd>UserWaitlistRepository</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package
 * @subpackage
 */
class UserWaitlistRepository
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\User\UserWaitlistDao */
    protected $_userWaitlistDao;
    /** @var \Pley\Dao\User\UserWaitlistSharedDao */
    protected $_userWaitlistSharedDao;

    public function __construct(
        \Pley\Db\AbstractDatabaseManager $dbManager,
        \Pley\Dao\User\UserWaitlistDao $userWaitlistDao,
        \Pley\Dao\User\UserWaitlistSharedDao $userWaitlistSharedDao)
    {
        $this->_dbManager = $dbManager;
        $this->_userWaitlistDao = $userWaitlistDao;
        $this->_userWaitlistSharedDao = $userWaitlistSharedDao;
    }

    /**
     * Get a specific UserWaitlist entity
     * @param int $id
     * @return \Pley\Entity\User\UserWaitlist
     */
    public function findWaitlist($id)
    {
        return $this->_userWaitlistDao->find($id);
    }

    /**
     * Get a list of all waitlist entries for the specified user.
     * @param int $userId
     * @return \Pley\Entity\User\UserWaitlist[]
     */
    public function findWaitlistByUser($userId)
    {
        return $this->_userWaitlistDao->findByUserId($userId);
    }

    /**
     * Get a list of all waitlist entries which failed a given number of days ago.
     * @param int $daysAgo
     * @return \Pley\Entity\User\UserWaitlist[]
     */
    public function findWaitlistFailedByDaysAgo($daysAgo)
    {
        return $this->_userWaitlistDao->findFailedByDaysAgo($daysAgo);
    }

    /**
     * Get a list of all waitlist entries for the specified subscription.
     * @param int $subscriptionId
     * @return \Pley\Entity\User\UserWaitlist[]
     */
    public function findWaitlistBySubscription($subscriptionId)
    {
        return $this->_userWaitlistDao->findBySubscriptionId($subscriptionId);
    }

    /**
     * Get a list of all waitlist entries for the specified gift id.
     * @param int $$giftId
     * @return \Pley\Entity\User\UserWaitlist[]
     */
    public function findWaitlistByGift($giftId)
    {
        return $this->_userWaitlistDao->findByGiftId($giftId);
    }

    /**
     * Saves a User Waitlist object into the storage
     * @param \Pley\Entity\User\UserWaitlist $userWaitlist
     * @return \Pley\Entity\User\UserWaitlist
     */
    public function saveWaitlist(\Pley\Entity\User\UserWaitlist $userWaitlist)
    {
        return $this->_userWaitlistDao->save($userWaitlist);
    }

    /**
     * Releases supplied User waitlist entry from the queue and adds a record indicating they shared
     * @param \Pley\Entity\User\UserWaitlist $userWaitlist
     */
    public function releaseWaitlist(\Pley\Entity\User\UserWaitlist $userWaitlist)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        // If there is no flag that the user shared already, then add the entry,
        // if there is one already, then ignore, we know they shared already
        $waitlistShared = $this->findWaitlistSharedByUser($userWaitlist->getUserId());
        if (empty($waitlistShared)) {
            $waitlistShared = new \Pley\Entity\User\UserWaitlistShared();
            $waitlistShared->setUserId($userWaitlist->getUserId());

            $this->_userWaitlistSharedDao->save($waitlistShared);
        }
        $userWaitlist->setStatus(\Pley\Enum\WaitlistStatusEnum::RELEASED)
            ->setPaymentAttemptAt(\Pley\Util\DateTime::date(time()));
        $this->_userWaitlistDao->save($userWaitlist);
    }

    /**
     * Cancels supplied User waitlist entry from the queue
     * @param \Pley\Entity\User\UserWaitlist $userWaitlist
     */
    public function cancelWaitlist(\Pley\Entity\User\UserWaitlist $userWaitlist)
    {
        $userWaitlist->setStatus(\Pley\Enum\WaitlistStatusEnum::CANCELLED);
        $this->_userWaitlistDao->save($userWaitlist);
    }

    /**
     * Get a UserWaitilistShared entity for the supplied user if it exists
     * @param int $userId
     * @return \Pley\Entity\User\UserWaitlistShared
     */
    public function findWaitlistSharedByUser($userId)
    {
        return $this->_userWaitlistSharedDao->findByUserId($userId);
    }

    /**
     * Stores the supplied entity into the storage
     * @param \Pley\Entity\User\UserWaitlistShared $userWaitlistShared
     * @return \Pley\Entity\User\UserWaitlistShared
     */
    public function saveWaitlistShared(\Pley\Entity\User\UserWaitlistShared $userWaitlistShared)
    {
        return $this->_userWaitlistSharedDao->save($userWaitlistShared);
    }
}
