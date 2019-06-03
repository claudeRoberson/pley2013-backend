<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Profile;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>ProfileSubscriptionStatusLog</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Stripe
 * @Meta\Table(name="profile_subscription_status_log")
 */
class ProfileSubscriptionStatusLog extends Entity
{
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;

    /**
     * @var int
     * @Meta\Property(fillable=true, column="profile_subscription_id")
     */
    protected $_profileSubscriptionId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="old_status")
     */
    protected $_oldStatus;

    /**
     * @var int
     * @Meta\Property(fillable=true, column="new_status")
     */

    protected $_newStatus;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="created_at")
     */
    protected $_createdAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return ProfileSubscriptionStatusLog
     */
    public function setId($id)
    {
        if (isset($this->_id)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_id');
        }
        $this->_id = $id;
    }

    /**
     * @return int
     */
    public function getProfileSubscriptionId()
    {
        return $this->_profileSubscriptionId;
    }

    /**
     * @param int $profileSubscriptionId
     * @return ProfileSubscriptionStatusLog
     */
    public function setProfileSubscriptionId($profileSubscriptionId)
    {
        $this->_profileSubscriptionId = $profileSubscriptionId;
        return $this;
    }

    /**
     * @return int
     */
    public function getOldStatus()
    {
        return $this->_oldStatus;
    }

    /**
     * @param int $oldStatus
     * @return ProfileSubscriptionStatusLog
     */
    public function setOldStatus($oldStatus)
    {
        $this->_oldStatus = $oldStatus;
        return $this;
    }

    /**
     * @return int
     */
    public function getNewStatus()
    {
        return $this->_newStatus;
    }

    /**
     * @param int $newStatus
     * @return ProfileSubscriptionStatusLog
     */
    public function setNewStatus($newStatus)
    {
        $this->_newStatus = $newStatus;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreatedAt()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->_createdAt);
    }
}