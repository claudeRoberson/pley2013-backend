<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Entity\Profile;

/** ♰
 * The <kbd>ProfileSubscriptionTransaction</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Profile
 * @subpackage Entity
 */
class ProfileSubscriptionTransaction implements \Pley\Entity\Vendor\VendorPaymentEntityInterface
{
    use \Pley\Entity\Vendor\Payment\VendorPaymentSystemEntityTrait,
        \Pley\Entity\Vendor\Payment\VendorPaymentMethodEntityTrait,
        \Pley\Entity\Vendor\Payment\VendorPaymentTransactionEntityTrait;

    /** @var int */
    protected $_id;
    /** @var int */
    protected $_userId;
    /** @var int */
    protected $_profileId;
    /** @var int */
    protected $_profileSubscriptionId;
    /** @var int */
    protected $_profileSubscriptionPlanId;
    /** @var int */
    protected $_userPaymentMethodId;
    /** @var int */
    protected $_transactionType;
    /** @var int */
    protected $_amount;
    /** @var int */
    protected $_transactionAt;
    /** @var int */
    protected $_transactionOperationsUserId;
    /** @var float */
    protected $_baseAmount;
    /** @var float */
    protected $_discountAmount;
    /** @var int */
    protected $_discountType;
    /** @var int */
    protected $_discountSourceId;

    public function __construct($id, $userId, $profileId, $profileSubscriptionId,
            $profileSubscriptionPlanId, $userPaymentMethodId, $transactionType, $amount,
            $vPaymentSystemId, $vPaymentMethodId, $vPaymentTransactionId,
            $transactionAt, $transactionOperationsUserId, $baseAmount, $discountAmount, $discountType, $discountSourceId)
    {
        $this->_id                          = $id;
        $this->_userId                      = $userId;
        $this->_profileId                   = $profileId;
        $this->_profileSubscriptionId       = $profileSubscriptionId;
        $this->_profileSubscriptionPlanId   = $profileSubscriptionPlanId;
        $this->_userPaymentMethodId         = $userPaymentMethodId;
        $this->_transactionType             = $transactionType;
        $this->_amount                      = $amount;
        $this->_vPaymentSystemId            = $vPaymentSystemId;
        $this->_vPaymentMethodId            = $vPaymentMethodId;
        $this->_vPaymentTransactionId       = $vPaymentTransactionId;
        $this->_transactionAt               = $transactionAt;
        $this->_transactionOperationsUserId = $transactionOperationsUserId;
        $this->_baseAmount                  = $baseAmount;
        $this->_discountAmount              = $discountAmount;
        $this->_discountType                = $discountType;
        $this->_discountSourceId            = $discountSourceId;

    }

    /**
     * Creates a new <kbd>ProfileSubscriptionTransaction</kbd> object for addition.
     * @param int    $userId
     * @param int    $profileId
     * @param int    $profileSubsId
     * @param int    $profileSubsPlanId
     * @param int    $userPaymentMethodId
     * @param int    $transactionType
     * @param float  $amount
     * @param int    $vPaymentSystemId
     * @param string $vPaymentMethodId
     * @param string $vPaymentTransactionId
     * @param int    $transactionAt
     * @param float  $baseAmount
     * @param float  $discountAmount
     * @param int    $discountType
     * @param int    $discountSourceId
     * @return \Pley\Entity\Profile\ProfileSubscriptionTransaction
     */
    public static function withNew($userId, $profileId, $profileSubsId, $profileSubsPlanId,
            $userPaymentMethodId, $transactionType,  $amount,
            $vPaymentSystemId, $vPaymentMethodId, $vPaymentTransactionId, $transactionAt,
            $baseAmount, $discountAmount, $discountType, $discountSourceId
    )
    {
        $transactionOperationsUserId = null;

        return new static(
            null, $userId, $profileId, $profileSubsId, $profileSubsPlanId,
            $userPaymentMethodId, $transactionType, $amount,
            $vPaymentSystemId, $vPaymentMethodId, $vPaymentTransactionId, $transactionAt,
            $transactionOperationsUserId, $baseAmount, $discountAmount, $discountType, $discountSourceId
        );
    }

    /** @return int */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Sets the ID after this entity has been added into the system
     * @param type $id
     * @throws \Pley\Exception\Entity\ImmutableAttributeException
     */
    public function setId($id)
    {
        if (isset($this->_id)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_id');
        }
        $this->_id = $id;
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
    public function getProfileSubscriptionPlanId()
    {
        return $this->_profileSubscriptionPlanId;
    }

    /** @return int */
    public function getUserPaymentMethodId()
    {
        return $this->_userPaymentMethodId;
    }

    /** @return int */
    public function getTransactionType()
    {
        return $this->_transactionType;
    }

    /** @return float */
    public function getAmount()
    {
        return $this->_amount;
    }

    /** @return int Time since EPOC */
    public function getTransactionAt()
    {
        return $this->_transactionAt;
    }

    /** @return int */
    public function getTransactionOperationsUserId()
    {
        return $this->_transactionOperationsUserId;
    }

    /**
     * @return float
     */
    public function getBaseAmount()
    {
        return $this->_baseAmount;
    }

    /**
     * @param float $baseAmount
     * @return ProfileSubscriptionTransaction
     */
    public function setBaseAmount($baseAmount)
    {
        $this->_baseAmount = $baseAmount;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountAmount()
    {
        return $this->_discountAmount;
    }

    /**
     * @param float $discountAmount
     * @return ProfileSubscriptionTransaction
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->_discountAmount = $discountAmount;
        return $this;
    }

    /**
     * @return int
     */
    public function getDiscountType()
    {
        return $this->_discountType;
    }

    /**
     * @param int $discountType
     * @return ProfileSubscriptionTransaction
     */
    public function setDiscountType($discountType)
    {
        $this->_discountType = $discountType;
        return $this;
    }

    /**
     * @return int
     */
    public function getDiscountSourceId()
    {
        return $this->_discountSourceId;
    }

    /**
     * @param int $discountSourceId
     * @return ProfileSubscriptionTransaction
     */
    public function setDiscountSourceId($discountSourceId)
    {
        $this->_discountSourceId = $discountSourceId;
        return $this;
    }

    public function setVPaymentTransactionId($vTransactionId)
    {
        $this->_vPaymentTransactionId = $vTransactionId;
        return $this;
    }
}
