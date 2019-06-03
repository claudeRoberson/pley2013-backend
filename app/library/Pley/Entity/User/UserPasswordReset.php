<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\User;

/**
 * The <kbd>UserPasswordReset</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.User
 * @subpackage Entity
 */
class UserPasswordReset
{
    /** @var int */
    protected $_id;
    /** @var string */
    protected $_token;
    /** @var boolean */
    protected $_isRedeemed;
    /** @var int */
    protected $_userId;
    /** @var int */
    protected $_requestCount;
    
    /**
     * Static constructor that allows to create a new UserPasswordReset for insertion.
     * @param string $token
     * @param int    $userId
     * @return \Pley\Entity\User\UserPasswordReset
     */
    public static function withNew($token, $userId)
    {
        $isRedeemed   = false;
        $requestCount = 1;

        return new static(null, $token, $isRedeemed, $userId, $requestCount);
    }
    
    public function __construct($id, $token, $isRedeemed, $userId, $requestCount)
    {
        $this->_id           = $id;
        $this->_token        = $token;
        $this->_isRedeemed   = $isRedeemed;
        $this->_userId       = $userId;
        $this->_requestCount = $requestCount;
    }

    /** @return int */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Sets the ID for a newly added PasswordReset.
     * @param int id
     * @throws \Pley\Exception\Entity\ImmutableAttributeException
     */
    public function setId($id)
    {
        if (isset($this->_id)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_id');
        }
        $this->_id = $id;
    }

    public function getToken()
    {
        return $this->_token;
    }

    /** @return boolean */
    public function isRedeemed()
    {
        return $this->_isRedeemed;
    }

    /** @param boolean $isRedeemed */
    public function setIsRedeemed()
    {
        if ($this->_isRedeemed) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_isRedeemed');
        }
        
        $this->_isRedeemed = true;
    }

    /** @return int */
    public function getUserId()
    {
        return $this->_userId;
    }

    /** @return int */
    public function getRequestCount()
    {
        return $this->_requestCount;
    }

    public function increaseRequestCount()
    {
        $this->_requestCount++;
    }
}
