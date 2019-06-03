<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Entity\User;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

/** ♰
 * The <kbd>UserIncompleteRegistration</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.2
 * @package Pley.Entity.User
 * @subpackage Entity
 * @Meta\Table(name="user_incomplete_registration")
 */
class UserIncompleteRegistration extends Entity
{
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
     * @Meta\Property(column="subscription_id")
     */
    protected $_subscriptionId;
    /**
     * @var int
     * @Meta\Property(column="payment_plan_id")
     */
    protected $_paymentPlanId;
    /**
     * @var string
     * @Meta\Property(column="profile_name")
     */
    protected $_profileName;
    /**
     * @var string
     * @Meta\Property(column="profile_gender")
     */
    protected $_profileGender;
    /**
     * @var int
     * @Meta\Property(column="profile_type_shirt_size_id")
     */
    protected $_profileShirtSize;
    
    /**
     * @var int
     * @Meta\Property(fillable=false, column="created_at")
     */
    protected $_createdAt;

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

    /** @return string */
    public function getProfileName()
    {
        return $this->_profileName;
    }
    
    /** @return string */
    public function getProfileGender()
    {
        return $this->_profileGender;
    }

    /** @return int */
    public function getProfileShirtSize()
    {
        return $this->_profileShirtSize;
    }
    
    /**
     * @return int
     */
    public function getCreatedAt()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->_createdAt);
    }
    
    /** ♰
     * @param int $id
     * @throws \Pley\Exception\Entity\ImmutableAttributeException
     */
    public function setId($id)
    {
        $this->_checkImmutableChange('_id');
        $this->_id = $id;
        return $this;
    }

    public function setUserId($userId)
    {
        $this->_userId = $userId;
        return $this;
    }

    public function setSubscriptionId($subscriptionId)
    {
        $this->_subscriptionId = $subscriptionId;
        return $this;
    }

    public function setPaymentPlanId($paymentPlanId)
    {
        $this->_paymentPlanId = $paymentPlanId;
        return $this;
    }

    public function setProfileName($profileName)
    {
        $this->_profileName = $profileName;
        return $this;
    }

    public function setProfileGender($profileGender)
    {
        $this->_profileGender = $profileGender;
        return $this;
    }

    public function setProfileShirtSize($profileShirtSize)
    {
        $this->_profileShirtSize = $profileShirtSize;
        return $this;
    }

}
