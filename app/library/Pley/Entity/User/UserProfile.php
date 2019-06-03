<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Entity\User;

use \Pley\Exception\Entity\ImmutableAttributeException;

class UserProfile
{
    protected $_id;
    protected $_userId;
    protected $_gender;
    protected $_firstName;
    protected $_lastName;
    protected $_birthDate;
    protected $_picture;
    protected $_typeShirtSizeId;
    /** @var timestamp */
    protected $_createdAt;
    /** @var timestamp */
    protected $_updatedAt;
    
    public function __construct(
            $id, $userId, $gender, $firstName, $lastName, $birthDate, $picture, $typeShirtSizeId,
            $createdAt, $updatedAt)
    {
        $this->_id              = $id;
        $this->_userId          = $userId;
        $this->_gender          = $gender;
        $this->_firstName       = $firstName;
        $this->_lastName        = $lastName;
        $this->_birthDate       = $birthDate;
        $this->_picture         = $picture;
        $this->_typeShirtSizeId = $typeShirtSizeId;
        $this->_createdAt       = $createdAt;
        $this->_updatedAt       = $updatedAt;
    }

    public static function withDummy($userId)
    {
        $id              = null;
        $gender          = null;
        $firstName       = null;
        $lastName        = null;
        $birthDate       = null;
        $picture         = null;
        $typeShirtSizeId = null;
        $createdAt       = null;
        $updatedAt       = null;
        
        return new static(
            $id, $userId, $gender, $firstName, $lastName, $birthDate, $picture, $typeShirtSizeId, 
            $createdAt, $updatedAt
        );
    }
    
    public static function withNewToUpdate($id, $userId, $gender, $firstName, 
            $lastName, $birthDate, $typeShirtSizeId)
    {
        $picture   = null;
        $createdAt = time();
        $updatedAt = null;

        return new static(
            $id, $userId, $gender, $firstName, $lastName, $birthDate, $picture, $typeShirtSizeId, 
            $createdAt, $updatedAt
        );
    }
    
    public static function withNew(
            $userId, $gender, $typeShirtSizeId, $firstName, $lastName = null, $birthDate = null)
    {
        $picture   = null;
        $createdAt = time();
        $updatedAt = null;
        
        return new static(
            null, $userId, $gender, $firstName, $lastName, $birthDate, $picture, $typeShirtSizeId, 
            $createdAt, $updatedAt
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

    public function setUserId($userId)
    {
        $this->_userId = $userId;
    }
        
    public function getUserId()
    {
        return $this->_userId;
    }


    public function setGender($gender)
    {
        $this->_gender = $gender;
    }
        
    public function getGender()
    {
        return $this->_gender;
    }

    public function setFirstName($firstName)
    {
        $this->_firstName = $firstName;
    }
        
    public function getFirstName()
    {
        return $this->_firstName;
    }

    public function setLastName($lastName)
    {
        $this->_lastName = $lastName;
    }
        
    public function getLastName()
    {
        return $this->_lastName;
    }

    public function setBirthDate($birthDate)
    {
        $this->_birthDate = $birthDate;
    }
        
    public function getBirthDate()
    {
        return $this->_birthDate;
    }

    public function setPicture($picture)
    {
        $this->_picture = $picture;
    }
        
    public function getPicture()
    {
        return $this->_picture;
    }

    public function setTypeShirtSizeId($typeShirtSizeId)
    {
        $this->_typeShirtSizeId = $typeShirtSizeId;
    }
        
    public function getTypeShirtSizeId()
    {
        return $this->_typeShirtSizeId;
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

    
    // Helper functions ----------------------------------------------------------------------------
    
    public function isDummy()
    {
        return empty($this->_firstName);
    }
}
