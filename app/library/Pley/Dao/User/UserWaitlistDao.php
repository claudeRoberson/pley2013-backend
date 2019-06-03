<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Dao\User;

use Pley\Db\AbstractDatabaseManager as DatabaseManager;
use Pley\Enum\WaitlistStatusEnum;

/**
 * The <kbd>UserWaitlistDao</kbd> DAO.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.User
 * @subpackage Dao
 */
class UserWaitlistDao extends \Pley\DataMap\Dao
{
    public function __construct(DatabaseManager $databaseManager)
    {
        parent::__construct($databaseManager);

        $this->setEntityClass(\Pley\Entity\User\UserWaitlist::class);
    }

    /**
     * Get a list of all waitlist entries for the specified user.
     * @return \Pley\Entity\User\UserWaitlist[]
     */
    public function findByUserId($userId)
    {
        $waitlistCollection = $this->where('`user_id` = ? AND (`status` = ? OR `status` = ?)',
            [$userId, WaitlistStatusEnum::ACTIVE, WaitlistStatusEnum::PAYMENT_ATTEMPT_FAILED]);
        $this->_sortInOrder($waitlistCollection);

        return $waitlistCollection;
    }

    /**
     * Get a list of all waitlist entries with failed payments on a given number of days ago.
     * @return \Pley\Entity\User\UserWaitlist[]
     */
    public function findFailedByDaysAgo($daysAgo)
    {
        $paymentAttemptDate = date("Y-m-d", strtotime("-" . $daysAgo . " days"));

        $waitlistCollection = $this->where('`status` = ? AND 
        DATE(payment_attempt_at) = ?',
            [
                WaitlistStatusEnum::PAYMENT_ATTEMPT_FAILED,
                $paymentAttemptDate
            ]);
        $this->_sortInOrder($waitlistCollection);

        return $waitlistCollection;
    }

    /**
     * Get a list of all waitlist entries for the specified subscription.
     * @return \Pley\Entity\User\UserWaitlist[]
     */
    public function findBySubscriptionId($subscriptionId)
    {
        $waitlistCollection = $this->where('`subscription_id` = ? AND (`status` = ? OR `status` = ?)',
            [$subscriptionId, WaitlistStatusEnum::ACTIVE, WaitlistStatusEnum::PAYMENT_ATTEMPT_FAILED]);
        $this->_sortInOrder($waitlistCollection);

        return $waitlistCollection;
    }

    /**
     * Get a list of all waitlist entries for the specified gift.
     * @return \Pley\Entity\User\UserWaitlist[]
     */
    public function findByGiftId($giftId)
    {
        $waitlistCollection = $this->where('`gift_id` = ?',
            [$giftId]);
        $this->_sortInOrder($waitlistCollection);

        return $waitlistCollection;
    }

    /**
     * Removes a given Entity from a database
     * @return void
     */
    public function remove(\Pley\DataMap\Entity $entity)
    {
        $prepSql = 'DELETE FROM `' . $this->_tableName . '` '
            . 'WHERE `id` = ?';
        $bindings = [$entity->getId()];

        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }

    private function _sortInOrder(&$waitlistCollection)
    {
        usort($waitlistCollection, function ($waitlistA, $waitlistB) {
            return $waitlistA->getId() - $waitlistB->getId();
        });
    }
}
