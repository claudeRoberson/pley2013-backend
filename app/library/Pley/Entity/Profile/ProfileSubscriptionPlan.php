<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Entity\Profile;

/** ♰
 * The <kbd>ProfileSubscriptionPlan</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Profile
 * @subpackage Entity
 */
class ProfileSubscriptionPlan implements \Pley\Entity\Vendor\VendorPaymentEntityInterface
{
    use \Pley\Entity\Vendor\Payment\VendorPaymentSystemEntityTrait,
        \Pley\Entity\Vendor\Payment\VendorPaymentPlanEntityTrait,
        \Pley\Entity\Vendor\Payment\VendorPaymentSubscriptionEntityTrait;
    
    /** @var int */
    protected $_id;
    /** @var int */
    protected $_userId;
    /** @var int */
    protected $_profileId;
    /** @var int */
    protected $_profileSubscriptionId;
    /** @var int */
    protected $_paymentPlanId;
    /** @var int */
    protected $_status;
    /** @var int */
    protected $_isAutoRenew;
    /** @var int */
    protected $_autoRenewStopAt;
    /** @var int */
    protected $_cancelAt;
    /** @var int */
    protected $_cancelSource;
    /** @var int */
    protected $_cancelOperationsUserId;
    /** @var int */
    protected $_createdAt;
    
    public function __construct($id, $userId, $profileId, $profileSubscriptionId, $paymentPlanId, 
            $status, $isAutoRenew, $vPaymentSystemId, $vPaymentPlanId, $vPaymentSubscriptionId, 
            $autoRenewStopAt, $cancelAt, $cancelSource, $cancelOperationsUserId, $createdAt)
    {
        $this->_id                     = $id;
        $this->_userId                 = $userId;
        $this->_profileId              = $profileId;
        $this->_profileSubscriptionId  = $profileSubscriptionId;
        $this->_paymentPlanId          = $paymentPlanId;
        $this->_status                 = $status;
        $this->_isAutoRenew            = $isAutoRenew;
        $this->_vPaymentSystemId       = $vPaymentSystemId;
        $this->_vPaymentPlanId         = $vPaymentPlanId;
        $this->_vPaymentSubscriptionId = $vPaymentSubscriptionId;
        $this->_autoRenewStopAt        = $autoRenewStopAt;
        $this->_cancelAt               = $cancelAt;
        $this->_cancelSource           = $cancelSource;
        $this->_cancelOperationsUserId = $cancelOperationsUserId;
        $this->_createdAt              = $createdAt;
    }
    
    public static function withNew(
            $userId, $profileId, $profileSubscriptionId, $paymentPlanId, $status, $isAutoRenew)
    {        
        $vPaymentSystemId       = null;
        $vPaymentPlanId         = null;
        $vPaymentSubscriptionId = null;
        $autoRenewStopAt        = null;
        $cancelAt               = null;
        $cancelSource           = null;
        $cancelOperationsUserId = null;
        $createdAt              = time();

        return new static(
            null, $userId, $profileId, $profileSubscriptionId, $paymentPlanId, $status,
            $isAutoRenew, $vPaymentSystemId, $vPaymentPlanId, $vPaymentSubscriptionId,
            $autoRenewStopAt, $cancelAt, $cancelSource, $cancelOperationsUserId, $createdAt
        );
    }
            
    /** @param int id */
    public function setId($id)
    {
        if (isset($this->_id)) {
            throw new ImmutableAttributeException(static::class, '_id');
        }
        $this->_id = $id;
    }    

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
    public function getProfileId()
    {
        return $this->_profileId;
    }

    /** @return int */
    public function getProfileSubscriptionId()
    {
        return $this->_profileSubscriptionId;
    }

    /** @return int */
    public function getPaymentPlanId()
    {
        return $this->_paymentPlanId;
    }

    /** @return int */
    public function getStatus()
    {
        return $this->_status;
    }

    /** @param $status int A value from <kbd>\Pley\Enum\SubscriptionStatusEnum</kbd> */
    public function setStatus($status)
    {
        \Pley\Enum\SubscriptionStatusEnum::validate($status);
        
        $this->_status = $status;
    }
    
    /** @return boolean */
    public function isAutoRenew()
    {
        return $this->_isAutoRenew;
    }

    /** @return int Time from EPOC */
    public function getAutoRenewStopAt()
    {
        return $this->_autoRenewStopAt;
    }

    /** @return int Time from EPOC */
    public function getCancelAt()
    {
        return $this->_cancelAt;
    }

    /** @return int */
    public function getCancelSource()
    {
        return $this->_cancelSource;
    }

    /** @param $cancelSource int A value from <kbd>\Pley\Enum\SubscriptionCancelSourceEnum</kbd> */
    public function setCancelSource($cancelSource)
    {
        \Pley\Enum\SubscriptionCancelSourceEnum::validate($cancelSource);
        
        $this->_cancelSource = $cancelSource;
    }

    /** @return int */
    public function getCancelOperationsUserId()
    {
        return $this->_cancelOperationsUserId;
    }

    /**
     * Operation to flag the current subscription plan to not auto-renew at the end of the billing period.
     * @param int $cancelSource A value from <kbd>\Pley\Enum\SubscriptionCancelSourceEnum</kbd>
     * @param int $cancelAt     The date when the Subscription will stop the auto-renewal
     * @param int $opUserId     (Optional)<br/>If the cancellation was done by a Customer Service user
     */
    public function stopAutoRenewal($cancelSource, $cancelAt, $opUserId = null)
    {
        $optionsMap = [];
        if (isset($opUserId)) {
            $optionsMap['opUserId'] = $opUserId;
        }
        
        $this->_cancelDelegate($cancelSource, $cancelAt, $optionsMap);
    }
    
    /**
     * Flag this subscription plan as cancelled.
     * <p>This operation is to be performed only by CustomerService, not by the customer.</p>
     * @param int $cancelSource A value from <kbd>\Pley\Enum\SubscriptionCancelSourceEnum</kbd>
     * @param int $cancelAt     The date when the Subscription will stop the auto-renewal
     * @param int $opUserId     The Customer Service user that performend the cancellation
     */
    public function cancel($cancelSource, $cancelAt, $opUserId)
    {
        $optionsMap = [
            'opUserId' => $opUserId,
            'status'   => \Pley\Enum\SubscriptionStatusEnum::CANCELLED,
        ];
        
        $this->_cancelDelegate($cancelSource, $cancelAt, $optionsMap);
    }
    
    /** @return int */
    public function getCreatedAt()
    {
        return $this->_createdAt;
    }
    
    /**
     * Setter to flag the subscription plan as cancelled from a Billing Vendor Event.
     * @param int $cancelAt Timestamp from EPOC
     */
    public function setEventCancel($cancelAt)
    {
        $this->_isAutoRenew = false;
        $this->_cancelAt    = $cancelAt;
        $this->_status      = \Pley\Enum\SubscriptionStatusEnum::CANCELLED;
    }
    
    /**
     * Flag this subscription back Active and with AutoRenewal.
     */
    public function resetAutoRenewal()
    {
        $this->_isAutoRenew            = true;
        $this->_autoRenewStopAt        = null;
        $this->_cancelSource           = null;
        $this->_cancelAt               = null;
        $this->_status                 = \Pley\Enum\SubscriptionStatusEnum::ACTIVE;
        $this->_cancelOperationsUserId = null;
    }
    
    /**
     * Helper method to concentrate common behavior between Cancelation and Auto-Renew Stop.
     * @param int $cancelSource A value from <kbd>\Pley\Enum\SubscriptionCancelSourceEnum</kbd>
     * @param int $cancelAt     The date when the Subscription will stop the auto-renewal
     * @param array $optionMap  A map containing optional values related to cancellation
     */
    private function _cancelDelegate($cancelSource, $cancelAt, array $optionMap)
    {
        \Pley\Enum\SubscriptionCancelSourceEnum::validate($cancelSource);
       
        $this->_isAutoRenew     = false;
        $this->_autoRenewStopAt = time();
        $this->_cancelSource    = $cancelSource;
        $this->_cancelAt        = $cancelAt;

        $this->_setOption($optionMap, 'opUserId', '_cancelOperationsUserId');
        $this->_setOption($optionMap, 'status', '_status');
    }
    
    private function _setOption(array $optionMap, $key, $attribute)
    {
        if (isset($optionMap[$key])) {
            $this->$attribute = $optionMap[$key];
        }
    }
}
