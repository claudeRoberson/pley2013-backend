<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\User;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>UserWaitlistShared</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.User
 * @subpackage Entity
 * @Meta\Table(name="user_waitlist_shared")
 */
class UserWaitlistShared extends Entity
{
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var int
     * @Meta\Property(column="user_id")
     */
    protected $_userId;
    /**
     * @var int
     * @Meta\Property(column="created_at")
     */
    protected $_createdAt;
    
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
    public function getCreatedAt()
    {
        return $this->_createdAt;
    }

    /** 
     * @param int $id
     * @return \Pley\Entity\User\UserWaitlistShared
     */
    public function setId($id)
    {
        $this->_checkImmutableChange('_id');
        $this->_id = $id;
        return $this;
    }

    /**
     * @param int $userId
     * @return \Pley\Entity\User\UserWaitlistShared
     */
    public function setUserId($userId)
    {
        $this->_checkImmutableChange('_userId');
        $this->_userId = $userId;
        return $this;
    }


}
