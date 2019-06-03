<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Entity\Operations;

use \Pley\Entity\Exception\ImmutableAttributeException;

/**
 * The <kbd>OperationsUser</kbd> entity.
 *
 * @author Igor Shvartsev(igor.shvartsev@gmail.com)
 * @version 1.0
 * @package Pley.Entity.BackendUser
 * @subpackage Entity
 */
class OperationsUser
{
    /** @var int */
    protected $_id;
     /** @var string */
    protected $_username;
    /** @var string */
    protected $_firstName;
    /** @var string */
    protected $_lastName;
    /** @var string */
    protected $_email;
    /** @var string */
    protected $_password;
    /** @var boolean */
    protected $_isActivated;
    /** @var boolean */
    protected $_isBanned;
    /*@var int*/
    protected $_role;
    /*@var int*/
    protected $_warehouseId;
    /** @var int timestamp */
    protected $_createdAt;

    
    public static function withNew($username, $firstName, $lastName, $email, $password, $role, $warehouseId)
    {   
        $isBanned    = false;
        $isActivated = false;
        $createdAt   = date('Y-m-d H:i:s');

        return new static(
            null, $username, $firstName, $lastName, $email, $password, $role, $warehouseId, 
            $isActivated ,$isBanned, $createdAt
        );
    }
    
    public function __construct(
            $id, $username, $firstName, $lastName, $email, $password, $role, $warehouseId,
            $isActivated ,$isBanned, $createdAt)
    {
        $this->_id          = $id;
        $this->_username    = $username;
        $this->_firstName   = $firstName;
        $this->_lastName    = $lastName;
        $this->_email       = $email;
        $this->_password    = $password;
        $this->_role        = $role;
        $this->_isActivated = $isActivated;
        $this->_isBanned    = $isBanned;
        $this->_warehouseId = $warehouseId;
        $this->_createdAt   = $createdAt;
    }

    /** @return int */
    public function getId()
    {
        return $this->_id;
    }
    
    /**
     * Set the Id 
     * <p>If the entity has been assigned an ID already, an exception will be thrown for the object
     * is considered immutable.</p>
     * 
     * @param int $id
     * @throws ImmutableAttributeException If the ID has already been set.
     */
    public function setId($id)
    {
        if (isset($this->_id)) {
            throw new ImmutableAttributeException(static::class, '_id');
        }
        
        $this->_id = $id;
    }
    
    /** @return string */
    public function getUsername()
    {
        return $this->_username;
    }
    
    /** @param string  */
    public function setUsername($username)
    {
        $this->_username = $username;
    }

    /** @return string */
    public function getFirstName()
    {
        return $this->_firstName;
    }
    
     /** @param string  */
    public function setFirstName($firstName)
    {
        $this->_firstName = $firstName;
    }

    /** @return string */
    public function getLastName()
    {
        return $this->_lastName;
    }
    
    /** @param string  */
    public function setLastName($lastName)
    {
        $this->_lastName = $lastName;
    }

    /** @return string */
    public function getEmail()
    {
        return $this->_email;
    }
    
    /** @param string  */
    public function setEmail($email)
    {
        $this->_email = $email;
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
    public function getRole()
    {
        return $this->_role;
    }

    public function setRole($role)
    {
        $this->_role = $role;
    }

    /** @return boolean */
    public function isActivated()
    {
        return $this->_isActivated;
    }

    /** @return boolean */
    public function isBanned()
    {
        return $this->_isBanned;
    }

    /** @return int */
    public function getWarehouseId()
    {
        return $this->_warehouseId;
    }
    
    /** @param int $warehouseId */
    public function setWarehouseId($warehouseId)
    {
        $this->_warehouseId = $warehouseId;
    }

    /** @return int Time in seconds from EPOC */
    public function getCreatedAt()
    {
        return $this->_createdAt;
    }

}
