<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Paypal;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>PaypalLog</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Paypal
 * @Meta\Table(name="paypal_api_log")
 */
class PaypalLog extends Entity
{
    use Entity\Timestampable;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;

    /**
     * @var string
     * @Meta\Property(fillable=true, column="type")
     */
    protected $_type;

    /**
     * @var string
     * @Meta\Property(fillable=true, column="request_json")
     */
    protected $_requestJson;

    /**
     * @var string
     * @Meta\Property(fillable=true, column="response_json")
     */
    protected $_responseJson;

    /**
     * @var string
     * @Meta\Property(fillable=true, column="error")
     */
    protected $_error;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return PaypalLog
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
    public function getType()
    {
        return $this->_type;
    }

    /**
     * @param string $type
     * @return PaypalLog
     */
    public function setType($type)
    {
        $this->_type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestJson()
    {
        return $this->_requestJson;
    }

    /**
     * @param string $requestJson
     * @return PaypalLog
     */
    public function setRequestJson($requestJson)
    {
        $this->_requestJson = $requestJson;
        return $this;
    }

    /**
     * @return string
     */
    public function getResponseJson()
    {
        return $this->_responseJson;
    }

    /**
     * @param string $responseJson
     * @return PaypalLog
     */
    public function setResponseJson($responseJson)
    {
        $this->_responseJson = $responseJson;
        return $this;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @param string $error
     * @return PaypalLog
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }
}