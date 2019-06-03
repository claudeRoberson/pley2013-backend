<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Entity\User;

use \Pley\Entity\Exception\ImmutableAttributeException;

/**
 * The <kbd>User</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.User
 * @subpackage Entity
 */
class UserCreditCard
{
    /** @var int */
    protected $_id;
    /** @var int */
    protected $_userId;
    /** @var string */
    protected $_ccNumber;
    /** @var string */
    protected $_cvv;
    /** @var string */
    protected $_expMonth;
    /** @var string */
    protected $_expYear;

    public function __construct(
            $id, $userId, $ccNumber, $cvv, $expMonth, $expYear)
    {
        $this->_id               = $id;        
        $this->_userId           = $userId;
        $this->_ccNumber         = $ccNumber;
        $this->_cvv              = $cvv;
        $this->_expMonth         = $expMonth;
        $this->_expYear          = $expYear;
    }
    
    public static function withNew($userId, $ccNumber, $cvv, $expMonth, $expYear)
    {
        return new static(
            null, $userId, $ccNumber, $cvv, $expMonth, $expYear
        );
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

    /** @return int */
    public function getUserId()
    {
        return $this->_userId;
    }

    /** @return string */
    public function getCcNumber()
    {
        return $this->_ccNumber;
    }

    /** @return string */
    public function getExpMonth()
    {
        return $this->_expMonth;
    }

    /** @return string */
    public function getExpYear()
    {
        return $this->_expYear;
    }

    /** @return string */
    public function getCvv()
    {
        return $this->_cvv;
    }

    
}
