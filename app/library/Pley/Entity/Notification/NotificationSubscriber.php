<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Notification;

/**
 * The <kbd>NotificationSubscriber</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity.Notification
 * @subpackage NotificationSubscriber
 * @Meta\Table(name="notification_subscriber")
 */

use Pley\DataMap\Entity;
use Pley\DataMap\Entity\Timestampable;
use Pley\DataMap\Annotations\Meta;

class NotificationSubscriber extends Entity
{
    use Timestampable;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="email")
     */
    protected $_email;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="type")
     */
    protected $_type;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return NotificationSubscriber
     */
    public function setId($id)
    {
        if (isset($this->_id)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_id');
        }
        $this->_id = $id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * @param string $email
     * @return NotificationSubscriber
     */
    public function setEmail($email)
    {
        $this->_email = $email;
        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param int $type
     * @return NotificationSubscriber
     */
    public function setType($type)
    {
        $this->_type = $type;
        return $this;
    }

}

