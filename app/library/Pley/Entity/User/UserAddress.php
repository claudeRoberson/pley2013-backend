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
class UserAddress
{
    /** @var int */
    protected $_id;
    /** @var int */
    protected $_userId;
    /** @var string */
    protected $_street1;
    /** @var string */
    protected $_street2;
    /** @var string */
    protected $_phone;
    /** @var string */
    protected $_city;
    /** @var string */
    protected $_state;
    /** @var string */
    protected $_country;
    /** @var string */
    protected $_zipCode;
    /** @var int|null * */
    protected $_shippingZoneId;
    /** @var int|null * */
    protected $_uspsShippingZoneId;
    /** @var int */
    protected $_valid;
    /** @var int */
    protected $_createdAt;
    /** @var int */
    protected $_updatedAt;

    const COUNTRY_CODE_UNITED_STATES = 'US';

    /**
     * Static constructor that allows to create a new Address for Verification.
     * <p>User ID is not required for address verificaiton.</p>
     * @param string $street1
     * @param string $street2
     * @param string $phone
     * @param string $city
     * @param string $state
     * @param string $country
     * @param string $zipCode
     * @return \Pley\Entity\User\UserAddress
     */
    public static function forVerification($street1, $street2, $phone, $city, $state, $country, $zipCode)
    {
        $userId = null;
        $uspsShippingZoneId = null;
        $shippingZoneId = null;
        $createdAt = time();
        $updatedAt = null;

        return new static(null, $userId, $street1, $street2, $phone, $city, $state, $country, $zipCode, $shippingZoneId,
            $uspsShippingZoneId, false, $createdAt, $updatedAt);
    }

    public function __construct(
        $id,
        $userId,
        $street1,
        $street2,
        $phone,
        $city,
        $state,
        $country,
        $zipCode,
        $shippingZoneId,
        $uspsShippingZoneId,
        $valid,
        $createdAt,
        $updatedAt
    ) {
        $this->_id = $id;
        $this->_userId = $userId;
        $this->_street1 = $street1;
        $this->_street2 = empty($street2) ? null : $street2;
        $this->_phone = empty($phone) ? null : $phone;
        $this->_city = $city;
        $this->_state = $state;
        $this->_country = $country;
        $this->_zipCode = $zipCode;
        $this->_shippingZoneId = $shippingZoneId;
        $this->_uspsShippingZoneId = $uspsShippingZoneId;
        $this->_valid = $valid;
        $this->_createdAt = $createdAt;
        $this->_updatedAt = $updatedAt;
    }

    /** @return int */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Sets the ID for a newly added Address.
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

    /** @return int */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
     * Sets the User ID for this Address.
     * @param int $userId
     * @throws \Pley\Exception\Entity\ImmutableAttributeException
     */
    public function setUserId($userId)
    {
        if (isset($this->_userId)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_userId');
        }
        $this->_userId = $userId;
    }

    /** @return string */
    public function getStreet1()
    {
        return $this->_street1;
    }

    /** @return string */
    public function getStreet2()
    {
        return $this->_street2;
    }

    /** @return string */
    public function getCity()
    {
        return $this->_city;
    }

    /** @return string */
    public function getState()
    {
        return $this->_state;
    }

    /** @return string */
    public function getCountry()
    {
        return $this->_country;
    }

    /** @return string */
    public function getZipCode()
    {
        return $this->_zipCode;
    }

    /** @return int */
    public function getShippingZoneId()
    {
        return $this->_shippingZoneId;
    }

    /**
     * @param $shippingZoneId
     * @return $this
     */
    public function setShippingZoneId($shippingZoneId)
    {
        $this->_shippingZoneId = $shippingZoneId;
        return $this;
    }

    /** @return int */
    public function getUspsShippingZoneId()
    {
        return $this->_uspsShippingZoneId;
    }

    /** @param int $uspsShippingZoneId */
    public function setUspsShippingZoneId($uspsShippingZoneId)
    {
        $this->_uspsShippingZoneId = $uspsShippingZoneId;
    }

    public function isUsAddress(){
        return $this->_country == self::COUNTRY_CODE_UNITED_STATES;
    }

    public function setIsValid($valid)
    {
        return $this->_valid = $valid;
    }

    public function getIsValid()
    {
        return (int)$this->_valid;
    }

    public function isValid()
    {
        return (bool)$this->getIsValid();
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->_phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->_phone = $phone;
        return $this;
    }

    /** @return int Time in seconds from EPOC */
    public function getCreatedAt()
    {
        return $this->_createdAt;
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
