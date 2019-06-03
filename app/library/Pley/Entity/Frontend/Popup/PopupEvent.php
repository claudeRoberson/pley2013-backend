<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\Frontend\Popup;

use Pley\DataMap\Entity;
use Pley\DataMap\Entity\Timestampable;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>PopupEvent</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Frontend.Popup
 * @subpackage Entity
 * @Meta\Table(name="popup_event")
 */
class PopupEvent extends Entity
{
    use Timestampable;
    
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var int
     * @Meta\Property(column="index")
     */
    protected $_index;
    /**
     * @var int
     * @Meta\Property(column="is_enabled")
     */
    protected $_isEnabled;
    /**
     * @var int
     * @Meta\Property(column="type_popup_event_id")
     */
    protected $_eventType;
    /**
     * @var string
     * @Meta\Property(column="title")
     */
    protected $_title;
    /**
     * @var string
     * @Meta\Property(column="body")
     */
    protected $_body;
    /**
     * @var int
     * @Meta\Property(column="sec_delay")
     */
    protected $_secDelay;
    /**
     * @var int
     * @Meta\Property(column="coupon_id")
     */
    protected $_couponId;
    /**
     * @var int
     * @Meta\Property(column="type_popup_action_id")
     */
    protected $_actionType;
    /**
     * @var string
     * @Meta\Property(column="popup_action_params_json")
     */
    protected $_paramJson;
    
    public function getId()
    {
        return $this->_id;
    }

    public function getIndex()
    {
        return $this->_index;
    }

    public function getIsEnabled()
    {
        return $this->isEnabled();
    }

    public function isEnabled()
    {
        return (boolean)$this->_isEnabled;
    }

    public function getEventType()
    {
        return $this->_eventType;
    }
    
    public function getTitle()
    {
        return $this->_title;
    }

    public function getBody()
    {
        return $this->_body;
    }

    public function getSecDelay()
    {
        return $this->_secDelay;
    }

    public function getCouponId()
    {
        return $this->_couponId;
    }

    public function getActionType()
    {
        return $this->_actionType;
    }

    public function getParamJson()
    {
        $paramMap = empty($this->_paramJson)? null : json_decode($this->_paramJson, true);
        return $paramMap;
    }

    public function setId($id)
    {
        $this->_checkImmutableChange('_id');
        $this->_id = $id;
        return $this;
    }

    public function setIndex($index)
    {
        $this->_index = $index;
        return $this;
    }

    public function setIsEnabled($isEnabled)
    {
        $this->_isEnabled = (int)$isEnabled;
        return $this;
    }

    public function setEventType($eventType)
    {
        $this->_eventType = $eventType;
    }
    
    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    public function setBody($body)
    {
        $this->_body = $body;
        return $this;
    }

    public function setSecDelay($secDelay)
    {
        $this->_secDelay = $secDelay;
        return $this;
    }

    public function setCouponId($couponId)
    {
        $this->_couponId = $couponId;
        return $this;
    }

    public function setActionType($actionType)
    {
        $this->_actionType = $actionType;
        return $this;
    }

    public function setParamJson($paramJson)
    {
        $this->_paramJson = $paramJson;
        return $this;
    }

    public function setParamMap($paramMap)
    {
        $this->_paramJson = json_encode($paramMap);
        return $this;
    }
    
}
