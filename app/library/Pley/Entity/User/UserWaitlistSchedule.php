<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\User;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>UserWaitlistSchedule</kbd> entity.
 *
 * @author Seva Yatsiuk (vsevolod.yatsiuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity.User
 * @subpackage Entity
 * @Meta\Table(name="user_waitlist_schedule")
 */
class UserWaitlistSchedule extends Entity
{
    use Entity\Timestampable;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var int
     * @Meta\Property(column="subscription_id")
     */
    protected $_subscriptionId;
    /**
     * @var int
     * @Meta\Property(column="subscription_item_id")
     */
    protected $_subscriptionItemId;
    /**
     * @var int
     * @Meta\Property(column="waitlist_from_date")
     */
    protected $_waitlistFromDate;
    /**
     * @var int
     * @Meta\Property(column="waitlist_till_date")
     */
    protected $_waitlistTillDate;
    /**
     * @var int
     * @Meta\Property(column="enabled")
     */
    protected $_enabled;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return UserWaitlistSchedule
     */
    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getSubscriptionId()
    {
        return $this->_subscriptionId;
    }

    /**
     * @param int $subscriptionId
     * @return UserWaitlistSchedule
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->_subscriptionId = $subscriptionId;
        return $this;
    }

    /**
     * @return int
     */
    public function getSubscriptionItemId()
    {
        return $this->_subscriptionItemId;
    }

    /**
     * @param int $subscriptionItemId
     * @return UserWaitlistSchedule
     */
    public function setSubscriptionItemId($subscriptionItemId)
    {
        $this->_subscriptionItemId = $subscriptionItemId;
        return $this;
    }

    /**
     * @return int
     */
    public function getWaitlistFromDate()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->_waitlistFromDate);
    }

    /**
     * @param int $waitlistFromDate
     * @return UserWaitlistSchedule
     */
    public function setWaitlistFromDate($waitlistFromDate)
    {
        $this->_waitlistFromDate = $waitlistFromDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getWaitlistTillDate()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->_waitlistTillDate);
    }

    /**
     * @param int $waitlistTillDate
     * @return UserWaitlistSchedule
     */
    public function setWaitlistTillDate($waitlistTillDate)
    {
        $this->_waitlistTillDate = $waitlistTillDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getEnabled()
    {
        return $this->_enabled;
    }

    /**
     * @param int $enabled
     * @return UserWaitlistSchedule
     */
    public function setEnabled($enabled)
    {
        $this->_enabled = $enabled;
        return $this;
    }
}
