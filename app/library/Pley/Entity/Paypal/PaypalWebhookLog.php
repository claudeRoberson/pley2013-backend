<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Paypal;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>PaypalWebhookLog</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Stripe
 * @Meta\Table(name="paypal_webhook_log")
 */
class PaypalWebhookLog extends Entity
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
     * @var string
     * @Meta\Property(fillable=true, column="event_payload")
     */
    protected $_eventPayload;
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
     * @return PaypalWebhookLog
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
     * @return PaypalWebhookLog
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
     * @return PaypalWebhookLog
     */
    public function setEventType($eventType)
    {
        $this->_eventType = $eventType;
        return $this;
    }

    /**
     * @return string
     */
    public function getEventPayload()
    {
        return $this->_eventPayload;
    }

    /**
     * @param string $eventPayload
     * @return PaypalWebhookLog
     */
    public function setEventPayload($eventPayload)
    {
        $this->_eventPayload = $eventPayload;
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