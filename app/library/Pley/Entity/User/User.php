<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Entity\User;

/**
 * The <kbd>User</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.User
 * @subpackage Entity
 */
class User implements \Pley\Entity\Vendor\VendorPaymentEntityInterface
{
    use \Pley\Entity\Vendor\Payment\VendorPaymentSystemEntityTrait,
        \Pley\Entity\Vendor\Payment\VendorPaymentAccountEntityTrait;
    
    /** @var \Pley\Entity\User\User */
    private static $_dummyUser;
    
    /** @var int */
    protected $_id;
    /** @var string */
    protected $_firstName;
    /** @var string */
    protected $_lastName;
    /** @var string */
    protected $_email;
    /** @var string */
    protected $_country;
    /** @var string */
    protected $_password;
    /** @var string */
    protected $_fbToken;
    /** @var boolean */
    protected $_isVerified;
    /** @var int */
    protected $_defaultPaymentMethodId;
    /** @var boolean */
    protected $_isReceiveNewsletter;
    /** @var string */
    protected $_referrer;
    /** @var int timestamp */
    protected $_createdAt;    
    
    /**
     * Creates a new User for addition.
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $password
     * @return \Pley\Entity\User\User
     */
    public static function withNew($firstName, $lastName, $email, $password)
    {
        $country                = null;
        $fbToken                = null;
        $isVerified             = false;
        $vPaymentSystemId       = null;
        $vPaymentAccountId      = null;
        $defaultPaymentMethodId = null;
        $isReceiveNewsletter    = 0;
        $referrer               = null;
        $createdAt              = time();
        
        return new static(
            null, $firstName, $lastName, $email, $country, $password, $fbToken, $isVerified,
            $vPaymentSystemId, $vPaymentAccountId, $defaultPaymentMethodId, $isReceiveNewsletter, $referrer,
            $createdAt
        );
    }
    
    /**
     * Creates a dummy User object.
     * <p>This is mainly used for Gift purchases or any events which API requires a User object, but
     * the nature of the event doesn't really have a User related to it.</p>
     * @return \Pley\Entity\User\User
     */
    public static function dummy()
    {
        if (!isset(self::$_dummyUser)) {
            $firstName              = null;
            $lastName               = null;
            $email                  = null;
            $country                = null;
            $password               = null;
            $fbToken                = null;
            $isVerified             = false;
            $vPaymentSystemId       = null;
            $vPaymentAccountId      = null;
            $defaultPaymentMethodId = null;
            $isReceiveNewsletter    = 0;
            $referrer               = 0;
            $createdAt              = time();

            self::$_dummyUser = new static(
                0, $firstName, $lastName, $email, $country, $password, $fbToken, $isVerified,
                $vPaymentSystemId, $vPaymentAccountId, $defaultPaymentMethodId, $isReceiveNewsletter, $referrer,
                $createdAt
            );
        }
        
        return self::$_dummyUser;
    }

    public function __construct(
            $id, $firstName, $lastName, $email, $country, $password, $fbToken, $isVerified,
            $vPaymentSystemId, $vPaymentAccountId, $defaultPaymentMethodId, $isReceiveNewsletter, $referrer,
            $createdAt)
    {
        $this->_id                     = $id;
        $this->_firstName              = $firstName;
        $this->_lastName               = $lastName;
        $this->_email                  = $email;
        $this->_country                = $country;
        $this->_password               = $password;
        $this->_fbToken                = $fbToken;
        $this->_isVerified             = $isVerified;
        $this->_vPaymentSystemId       = $vPaymentSystemId;
        $this->_vPaymentAccountId      = $vPaymentAccountId;
        $this->_defaultPaymentMethodId = $defaultPaymentMethodId;
        $this->_isReceiveNewsletter    = $isReceiveNewsletter;
        $this->_referrer               = $referrer;
        $this->_createdAt              = $createdAt;
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
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_id');
        }
        $this->_id = $id;
    }

    public function setFirstName($firstName)
    {
        $this->_firstName = $firstName;
    }
    
    public function setLastName($lastName)
    {
        $this->_lastName = $lastName;
    }
        
    /** @return string */
    public function getFirstName()
    {
        return $this->_firstName;
    }

    /** @return string */
    public function getLastName()
    {
        return $this->_lastName;
    }
    
    public function setEmail($email)
    {
        $this->_email = $email;
    }
    
    /** @return string */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->_country;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->_country = $country;
    }

    /** @param string $fbToken */
    public function setFbToken($fbToken)
    {
        $this->_fbToken = $fbToken;
    }
    
    /** @return string */
    public function getFbToken()
    {
        return $this->_fbToken;
    }

    /** @return boolean */
    public function isVerified()
    {
        return $this->_isVerified;
    }    
    
    /** @return string */
    public function getPassword()
    {
        return $this->_password;
    }

    /** @param string The hashed password */
    public function setPassword($password)
    {
        $this->_password = $password;
    }

    /** @return int */
    public function getDefaultPaymentMethodId()
    {
        return $this->_defaultPaymentMethodId;
    }

    /** @param int */
    public function setDefaultPaymentMethodId($defaultPaymentMethodId)
    {
        $this->_defaultPaymentMethodId = $defaultPaymentMethodId;
    }
    
    /** @return boolean */
    public function isReceiveNewsletter()
    {
        return $this->_isReceiveNewsletter;
    }

    /** @param boolean $isReceiveNewsletter */
    public function setIsReceiveNewsletter($isReceiveNewsletter)
    {
        $this->_isReceiveNewsletter = (boolean)$isReceiveNewsletter;
    }

    /**
     * @return string
     */
    public function getReferrer()
    {
        return $this->_referrer;
    }

    /**
     * @param string $referrer
     * @return User
     */
    public function setReferrer($referrer)
    {
        $this->_referrer = $referrer;
        return $this;
    }

    /** @return int Time in seconds from EPOC */
    public function getCreatedAt()
    {
        return $this->_createdAt;
    }

}
