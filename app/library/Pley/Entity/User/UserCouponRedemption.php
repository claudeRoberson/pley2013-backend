<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\User;

use Pley\Util\Time\DateTime;

/**
 * The <kbd>UserCouponRedemption</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity.User
 * @subpackage Entity
 */
class UserCouponRedemption
{
    /** @var int */
    protected $_id;
    /** @var int */
    protected $_userId;
    /** @var int */
    protected $_couponId;
    /** @var int */
    protected $_transactionId;
    /** @var int */
    protected $_profileSubscriptionId;
    /** @var DateTime */
    protected $_redeemedAt;

    public function __construct(
        $id,
        $userId,
        $couponId,
        $transactionId,
        $profileSubscriptionId,
        $redeemedAt
    )
    {
        $this->_id                    = $id;
        $this->_userId                = $userId;
        $this->_couponId              = $couponId;
        $this->_transactionId         = $transactionId;
        $this->_profileSubscriptionId = $profileSubscriptionId;
        $this->_redeemedAt            = $redeemedAt;
    }

    public function getId()
    {
        return $this->_id;
    }

    /** @param int id */
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
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
     * @param int $userId
     * @return UserCouponRedemption
     */
    public function setUserId($userId)
    {
        $this->_userId = $userId;
        return $this;
    }

    /**
     * @return int
     */
    public function getCouponId()
    {
        return $this->_couponId;
    }

    /**
     * @param int $couponId
     * @return UserCouponRedemption
     */
    public function setCouponId($couponId)
    {
        $this->_couponId = $couponId;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getRedeemedAt()
    {
        return $this->_redeemedAt;
    }

    /**
     * @param DateTime $redeemedAt
     * @return UserCouponRedemption
     */
    public function setRedeemedAt($redeemedAt)
    {
        $this->_redeemedAt = $redeemedAt;
        return $this;
    }

    /**
     * @return int
     */
    public function getTransactionId()
    {
        return $this->_transactionId;
    }

    /**
     * @param int $transactionId
     * @return UserCouponRedemption
     */
    public function setTransactionId($transactionId)
    {
        $this->_transactionId = $transactionId;
        return $this;
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
     * @return UserCouponRedemption
     */
    public function setProfileSubscriptionId($profileSubscriptionId)
    {
        $this->_profileSubscriptionId = $profileSubscriptionId;
        return $this;
    }
}
