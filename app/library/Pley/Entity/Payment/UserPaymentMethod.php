<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Entity\Payment;

use \Pley\Exception\Entity\ImmutableAttributeException;

/**
 * The <kbd>User</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.User
 * @subpackage Entity
 */
class UserPaymentMethod implements \Pley\Entity\Vendor\VendorPaymentEntityInterface
{
    use \Pley\Entity\Vendor\Payment\VendorPaymentSystemEntityTrait,
        \Pley\Entity\Vendor\Payment\VendorPaymentMethodEntityTrait;
    
    /** @var int */
    protected $_id;
    /** @var int */
    protected $_userId;
    /** @var boolean */
    protected $_isVisible;
    /** @var timestamp */
    protected $_createdAt;
    /** @var timestamp */
    protected $_updatedAt;
    
    /**
     * Creates a new <kbd>UserPaymentMethod</kbd> object for addition.
     * @param int    $userId
     * @param int    $vPaymentSystemId
     * @param string $vPaymentMethodId
     * @return \Pley\Entity\Payment\UserPaymentMethod
     */
    public static function withNew($userId, $vPaymentSystemId, $vPaymentMethodId)
    {
        $isVisible = true;
        $createdAt = time();
        $updatedAt = null;
        
        return new static(
            null, $userId, $vPaymentSystemId, $vPaymentMethodId, $isVisible, $createdAt, $updatedAt
        );
    }
    
    public function __construct(
            $id, $userId, $vPaymentSystemId, $vPaymentMethodId, $isVisible, $createdAt, $updatedAt)
    {
        $this->_id               = $id;
        $this->_userId           = $userId;
        $this->_vPaymentSystemId = $vPaymentSystemId;
        $this->_vPaymentMethodId = $vPaymentMethodId;
        $this->_isVisible        = (boolean)$isVisible;
        $this->_createdAt        = $createdAt;
        $this->_updatedAt        = $updatedAt;
    }

    /** @return int */
    public function getId()
    {
        return $this->_id;
    }

    /** @param int id */
    public function setId($id)
    {
        if (isset($this->_id)) {
            throw new ImmutableAttributeException(static::class, '_id');
        }
        $this->_id = $id;
    }
        
    /** @return string */
    public function getUserId()
    {
        return $this->_userId;
    }
    
    /** @return int Time in seconds from EPOC */
    public function getCreatedAt()
    {
        return $this->_createdAt;
    }

    /** @return boolean */
    public function isVisible()
    {
        return $this->_isVisible;
    }

    /** @param boolean $isVisible */
    public function setIsVisible($isVisible)
    {
        $this->_isVisible = (boolean)$isVisible;
    }
        
    /** @return int Time in seconds from EPOC */
    public function getUpdatedAt()
    {
        return $this->_updatedAt;
    }

    /** @param int $updatedAt Time in seconds from EPOC */
    public function setUpdatedAt($updatedAt)
    {
        $this->_updatedAt = $updatedAt;
    }

}
