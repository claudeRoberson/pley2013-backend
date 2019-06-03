<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

/**
 * Class, which parse and represents Stripe events JSON as an object
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */

namespace Pley\Stripe;


class Event
{
    /** Stripe event type code */
    const TYPE_INVOICE_PAYMENT_SUCCEEDED = 'invoice.payment_succeeded';
    /** Stripe event type code */
    const TYPE_INVOICE_PAYMENT_FAILED    = 'invoice.payment_failed';
    /** Stripe event type code */
    const TYPE_SUBSCRIPTION_CANCEL       = 'customer.subscription.deleted';

    /**
     * @var string
     */
    protected $_json;

    /**
     * @var string
     */
    protected $_type;

    /**
     * @var array
     */
    protected $_meta;

    /**
     * @var array
     */
    protected $_object;

    /**
     * @param $stripeEventJson
     * @return $this
     */
    public function hydrate($stripeEventJson)
    {
        $payload = json_decode($stripeEventJson, true);
        $this->_json = $stripeEventJson;
        $this->_meta = $payload;
        $this->_type = $payload['type'];
        $this->_object = $payload['data']['object'];
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param $name
     * @return string | null
     */
    public function getMetaData($name)
    {
        return (isset($this->_meta[$name])) ? $this->_meta[$name] : null;
    }

    /**
     * @param $name
     * @return string | null
     */
    public function getObjectData($name)
    {
        return (isset($this->_object[$name])) ? $this->_object[$name] : null;
    }
}