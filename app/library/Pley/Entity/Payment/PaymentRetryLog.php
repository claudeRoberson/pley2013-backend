<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Payment;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>PaymentRetryLog</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Stripe
 * @Meta\Table(name="payment_retry_log")
 */
class PaymentRetryLog extends Entity
{

    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 2;
    const SENT_TO_VINDICIA = 3;
    
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;

    /**
     * @var int
     * @Meta\Property(fillable=true, column="profile_subscription_id")
     */
    protected $_profileSubscriptionId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="status")
     */
    protected $_status;

    /**
     * @var string
     * @Meta\Property(fillable=true, column="response_message")
     */
    protected $_responseMessage;
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
     * @return PaymentRetryLog
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
    public function getProfileSubscriptionId()
    {
        return $this->_profileSubscriptionId;
    }

    /**
     * @param int $profileSubscriptionId
     * @return PaymentRetryLog
     */
    public function setProfileSubscriptionId($profileSubscriptionId)
    {
        $this->_profileSubscriptionId = $profileSubscriptionId;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @param int $status
     * @return PaymentRetryLog
     */
    public function setStatus($status)
    {
        $this->_status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getResponseMessage()
    {
        return $this->_responseMessage;
    }

    /**
     * @param string $responseMessage
     * @return PaymentRetryLog
     */
    public function setResponseMessage($responseMessage)
    {
        $this->_responseMessage = $responseMessage;
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