<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Stripe;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>WebhookLog</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Stripe
 * @Meta\Table(name="stripe_webhook_log")
 */
class WebhookLog extends Entity
{
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="event_id")
     */
    protected $_eventId;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="event_type")
     */
    protected $_eventType;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="response_status_sent")
     */
    protected $_responseStatusSent;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="created_at")
     */
    protected $_createdAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return WebhookLog
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
    public function getEventId()
    {
        return $this->_eventId;
    }

    /**
     * @param string $eventId
     * @return WebhookLog
     */
    public function setEventId($eventId)
    {
        $this->_eventId = $eventId;
        return $this;
    }

    /**
     * @return string
     */
    public function getEventType()
    {
        return $this->_eventType;
    }

    /**
     * @param string $eventType
     * @return WebhookLog
     */
    public function setEventType($eventType)
    {
        $this->_eventType = $eventType;
        return $this;
    }

    /**
     * @return string
     */
    public function getResponseStatusSent()
    {
        return $this->_responseStatusSent;
    }

    /**
     * @param string $responseStatusSent
     * @return WebhookLog
     */
    public function setResponseStatusSent($responseStatusSent)
    {
        $this->_responseStatusSent = $responseStatusSent;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreatedAt()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->_createdAt);
    }
}