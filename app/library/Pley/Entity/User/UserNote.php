<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\User;

/**
 * The <kbd>UserNote</kbd> entity.
 *
 * @author Sebastian Maldonado(seba@pley.com)
 * @version 1.0
 * @package Pley.Entity.User
 * @subpackage UserNote
 * @Meta\Table(name="user_note")
 */

use Pley\DataMap\Entity;
use Pley\DataMap\Entity\Timestampable;
use Pley\DataMap\Annotations\Meta;

class UserNote extends Entity
{
    use Timestampable;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="user_id")
     */
    protected $_userId;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="body")
     */
    protected $_body;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return UserNote
     */
    public function setId($id)
    {
        if (isset($this->_id)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_id');
        }
        $this->_id = $id;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
     * @param int $userId
     * @return UserNote
     */
    public function setUserId($userId)
    {
        $this->_userId = $userId;
        return $this;
    }

    /**
     * @return int
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * @param int $body
     * @return UserNote
     */
    public function setBody($body)
    {
        $this->_body = $body;
    }
}

