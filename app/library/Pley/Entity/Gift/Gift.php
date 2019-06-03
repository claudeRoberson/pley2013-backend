<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Entity\Gift;

/**
 * The <kbd>Gift</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Gift
 * @subpackage Entity
 */
class Gift implements \Pley\Entity\Vendor\VendorPaymentEntityInterface
{
    use \Pley\Entity\Vendor\Payment\VendorPaymentSystemEntityTrait,
        \Pley\Entity\Vendor\Payment\VendorPaymentTransactionEntityTrait;
    
    /** @var int */
    protected $_id;
    /** @var string */
    protected $_token;
    /** @var boolean */
    protected $_isRedeemed;
    /** @var int */
    protected $_subscriptionId;
    /** @var int */
    protected $_giftPriceId;
    /** @var string */
    protected $_fromFirstName;
    /** @var string */
    protected $_fromLastName;
    /** @var string */
    protected $_fromEmail;
    /** @var string */
    protected $_toFirstName;
    /** @var string */
    protected $_toLastName;
    /** @var string */
    protected $_toEmail;
    /** @var string */
    protected $_message;
    /** @var boolean */
    protected $_isEmailSent;
    /** @var int */
    protected $_notifyDate;
    /** @var int */
    protected $_redeemedAt;
    /** @var int */
    protected $_redeemUserId;
    /** @var int */
    protected $_createdAt;
    
    public static function withNew($subscriptionId, $giftPriceId, $fromFirstName, $fromLastName, $fromEmail, 
            $toFirstName, $toLastName, $toEmail, $message, $notifyDate)
    {
        $token                 = null;
        $isRedeemed            = false;
        $vPaymentSystemId      = null;
        $vPaymentTransactionId = null;
        $isEmailSent           = false;
        $redeemedAt            = null;
        $redeemUserId          = null;
        $createdAt             = time();

        return new static(null, $token, $isRedeemed, $subscriptionId, $giftPriceId, 
            $vPaymentSystemId, $vPaymentTransactionId, $fromFirstName, $fromLastName, $fromEmail, 
            $toFirstName, $toLastName, $toEmail, $message, $isEmailSent, $notifyDate, 
            $redeemedAt, $redeemUserId, $createdAt);
    }
    
    public function __construct($id, $token, $isRedeemed, $subscriptionId, $giftPriceId, 
            $vPaymentSystemId, $vPaymentTransactionId, $fromFirstName, $fromLastName, $fromEmail, 
            $toFirstName, $toLastName, $toEmail, $message, $isEmailSent, $notifyDate, 
            $redeemedAt, $redeemUserId, $createdAt)
    {
        $this->_id                    = $id;
        $this->_token                 = $token;
        $this->_isRedeemed            = $isRedeemed;
        $this->_subscriptionId        = $subscriptionId;
        $this->_giftPriceId           = $giftPriceId;
        $this->_vPaymentSystemId      = $vPaymentSystemId;
        $this->_vPaymentTransactionId = $vPaymentTransactionId;
        $this->_fromFirstName         = $fromFirstName;
        $this->_fromLastName          = $fromLastName;
        $this->_fromEmail             = $fromEmail;
        $this->_toFirstName           = $toFirstName;
        $this->_toLastName            = $toLastName;
        $this->_toEmail               = $toEmail;
        $this->_message               = $message;
        $this->_isEmailSent           = $isEmailSent;
        $this->_notifyDate            = $notifyDate;
        $this->_redeemedAt            = $redeemedAt;
        $this->_redeemUserId          = $redeemUserId;
        $this->_createdAt             = $createdAt;
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
    
    /** @return string */
    public function getToken()
    {
        return $this->_token;
    }
    
    /** @param string $token */
    public function setToken($token)
    {
        // Token can only be updated as long as the Object ID is not set
        // Needs to be updatable in case of collision at inseriton time
        if (isset($this->_id)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_token');
        }
        $this->_token = $token;
    }

    public function isRedeemed()
    {
        return $this->_isRedeemed;
    }

    public function getSubscriptionId()
    {
        return $this->_subscriptionId;
    }

    public function getGiftPriceId()
    {
        return $this->_giftPriceId;
    }

    public function getFromFirstName()
    {
        return $this->_fromFirstName;
    }

    public function getFromLastName()
    {
        return $this->_fromLastName;
    }

    public function getFromEmail()
    {
        return $this->_fromEmail;
    }

    public function getToFirstName()
    {
        return $this->_toFirstName;
    }

    public function getToLastName()
    {
        return $this->_toLastName;
    }

    public function getToEmail()
    {
        return $this->_toEmail;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function isEmailSent()
    {
        return $this->_isEmailSent;
    }

    public function setIsEmailSent()
    {
        $this->_isEmailSent = true;
    }
    
    public function getNotifyDate()
    {
        return $this->_notifyDate;
    }

    public function getRedeemedAt()
    {
        return $this->_redeemedAt;
    }

    public function getRedeemUserId()
    {
        return $this->_redeemUserId;
    }

    public function setRedeemed($userId)
    {
        if ($this->_isRedeemed) {
            throw new \Exception('Gift Redeemed');
        }
        
        $this->_isRedeemed   = true;
        $this->_redeemUserId = $userId;
        $this->_redeemedAt   = time();
    }

    public function getCreatedAt()
    {
        return $this->_createdAt;
    }
}
