<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\User;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>UserWaitlist</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.User
 * @subpackage Entity
 * @Meta\Table(name="user_waitlist")
 */
class UserWaitlist extends Entity
{
    use Entity\Timestampable;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var int
     * @Meta\Property(column="user_id")
     */
    protected $_userId;
    /**
     * @var int
     * @Meta\Property(column="status")
     */
    protected $_status;
    /**
     * @var int
     * @Meta\Property(column="user_profile_id")
     */
    protected $_userProfileId;
    /**
     * @var int
     * @Meta\Property(column="user_address_id")
     */
    protected $_userAddressId;
    /**
     * @var int
     * @Meta\Property(column="subscription_id")
     */
    protected $_subscriptionId;
    /**
     * @var int
     * @Meta\Property(column="payment_plan_id")
     */
    protected $_paymentPlanId;
    /**
     * @var int
     * @Meta\Property(column="gift_id")
     */
    protected $_giftId;
    /**
     * @var int
     * @Meta\Property(column="coupon_id")
     */
    protected $_couponId;
    /**
     * @var string
     * @Meta\Property(column="referral_token")
     */
    protected $_referralToken;
    /**
     * @var int
     * @Meta\Property(column="notification_count")
     */
    protected $_notificationCount = 0;
    /**
     * @var int
     * @Meta\Property(column="release_attempts")
     */
    protected $_releaseAttempts = 0;
    /**
     * @var int
     * @Meta\Property(column="payment_attempt_at")
     */
    protected $_paymentAttemptAt;

    /**
     * @var \Pley\Entity\Subscription\Subscription
     */
    public $subscription;

    /**
     * @var \Pley\Entity\Payment\UserPaymentMethod
     */
    public $paymentMethod;

    /**
     * @var \Pley\Entity\User\UserAddress
     */
    public $userAddress;

    
    /** @return int */
    public function getId()
    {
        return $this->_id;
    }

    /** @return int */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @param int $status
     * @return UserWaitlist
     */
    public function setStatus($status)
    {
        $this->_status = $status;
        return $this;
    }

    /** @return int */
    public function getUserProfileId()
    {
        return $this->_userProfileId;
    }

    /** @return int */
    public function getUserAddressId()
    {
        return $this->_userAddressId;
    }

    /** @return int */
    public function getSubscriptionId()
    {
        return $this->_subscriptionId;
    }

    /** @return int */
    public function getPaymentPlanId()
    {
        return $this->_paymentPlanId;
    }

    /** @return int */
    public function getGiftId()
    {
        return $this->_giftId;
    }

    /** @return int */
    public function getCouponId()
    {
        return $this->_couponId;
    }

    /** @return string */
    public function getReferralToken()
    {
        return $this->_referralToken;
    }
    
    /** @return int */
    public function getNotificationCount()
    {
        return $this->_notificationCount;
    }

    /** 
     * @param int $id
     * @return \Pley\Entity\User\UserWaitlist
     */
    public function setId($id)
    {
        $this->_checkImmutableChange('_id');
        $this->_id = $id;
        return $this;
    }

    /**
     * @param int $userId
     * @return \Pley\Entity\User\UserWaitlist
     */
    public function setUserId($userId)
    {
        $this->_checkImmutableChange('_userId');
        $this->_userId = $userId;
        return $this;
    }

    /**
     * @param int $userProfileId
     * @return \Pley\Entity\User\UserWaitlist
     */
    public function setUserProfileId($userProfileId)
    {
        $this->_userProfileId = $userProfileId;
        return $this;
    }

    /**
     * @param int $userAddressId
     * @return \Pley\Entity\User\UserWaitlist
     */
    public function setUserAddressId($userAddressId)
    {
        $this->_checkImmutableChange('_userAddressId');
        $this->_userAddressId = $userAddressId;
        return $this;
    }

    /**
     * @param int $subscriptionId
     * @return \Pley\Entity\User\UserWaitlist
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->_checkImmutableChange('_subscriptionId');
        $this->_subscriptionId = $subscriptionId;
        return $this;
    }

    /**
     * @param int $paymentPlanId
     * @return \Pley\Entity\User\UserWaitlist
     */
    public function setPaymentPlanId($paymentPlanId)
    {
        $this->_checkImmutableChange('_paymentPlanId');
        $this->_paymentPlanId = $paymentPlanId;
        return $this;
    }

    /**
     * @param int $giftId
     * @return \Pley\Entity\User\UserWaitlist
     */
    public function setGiftId($giftId)
    {
        $this->_checkImmutableChange('_giftId');
        $this->_giftId = $giftId;
        return $this;
    }

    /**
     * @param int $couponId
     * @return \Pley\Entity\User\UserWaitlist
     */
    public function setCouponId($couponId)
    {
        $this->_checkImmutableChange('_couponId');
        $this->_couponId = $couponId;
        return $this;
    }
    
    /**
     * @param string $referralToken
     * @return \Pley\Entity\User\UserWaitlist
     */
    public function setReferralToken($referralToken)
    {
        $this->_checkImmutableChange('_referralToken');
        $this->_referralToken = $referralToken;
        return $this;
    }

    /**
     * @param int $notificationCount
     * @return \Pley\Entity\User\UserWaitlist
     */
    public function setNotificationCount($notificationCount)
    {
        $this->_notificationCount = $notificationCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getReleaseAttempts()
    {
        return $this->_releaseAttempts;
    }

    /**
     * @param int $releaseAttempts
     * @return UserWaitlist
     */
    public function setReleaseAttempts($releaseAttempts)
    {
        $this->_releaseAttempts = $releaseAttempts;
        return $this;
    }

    /**
     * @return int
     */
    public function getPaymentAttemptAt()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->_paymentAttemptAt);
    }

    /**
     * @param int $paymentAttemptAt
     * @return UserWaitlist
     */
    public function setPaymentAttemptAt($paymentAttemptAt)
    {
        $this->_paymentAttemptAt = $paymentAttemptAt;
        return $this;
    }

}
