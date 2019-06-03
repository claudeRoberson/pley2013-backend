<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\Subscription;

use \Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>SequenceItem</kbd> entity
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Subscription
 * @subpackage Entity
 * @Meta\Table(name="subscription_item_sequence")
 */
class SequenceItem extends \Pley\DataMap\Entity
{   
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var int
     * @Meta\Property(column="type_brand_id")
     */
    protected $_brandId;
    /**
     * @var int
     * @Meta\Property(column="subscription_id")
     */
    protected $_subscriptionId;
    /**
     * @var int
     * @Meta\Property(column="sequence_index")
     */
    protected $_sequenceIndex;
    /**
     * @var int
     * @Meta\Property(column="item_id")
     */
    protected $_itemId;
    /**
     * @var int
     * @Meta\Property(column="units_programmed")
     */
    protected $_unitsProgrammed;
    /**
     * @var int
     * @Meta\Property(column="store_units_reserved")
     */
    protected $_storeUnitsReserved;
    /**
     * @var int
     * @Meta\Property(column="influencer_units_reserved")
     */
    protected $_influencerUnitsReserved;
    /**
     * @var int
     * @Meta\Property(column="subscription_units_programmed")
     */
    protected $_subscriptionUnitsProgrammed;
    /**
     * @var int
     * @Meta\Property(column="subscription_units_purchased")
     */
    protected $_subscriptionUnitsPurchased;
    /**
     * @var int
     * @Meta\Property(column="subscription_units_reserved")
     */
    protected $_subscriptionUnitsReserved;
    
    // Additional helper variables -----------------------------------------------------------------
    /** @var int */
    private $_periodIndex;
    /** @var int */
    private $_deadlineTime;
    /** @var int */
    private $_chargeTime;
    /** @var int */
    private $_deliveryStartTime;
    /** @var int */
    private $_deliveryEndTime;
    
    
    
    public function getId()
    {
        return $this->_id;
    }

    public function getBrandId()
    {
        return $this->_brandId;
    }

    public function getSubscriptionId()
    {
        return $this->_subscriptionId;
    }

    public function getSequenceIndex()
    {
        return $this->_sequenceIndex;
    }

    public function getItemId()
    {
        return $this->_itemId;
    }

    public function getUnitsProgrammed()
    {
        return $this->_unitsProgrammed;
    }

    public function getStoreUnitsReserved()
    {
        return $this->_storeUnitsReserved;
    }

    public function getInfluencerUnitsReserved()
    {
        return $this->_influencerUnitsReserved;
    }

    public function getSubscriptionUnitsProgrammed()
    {
        return $this->_subscriptionUnitsProgrammed;
    }

    public function getSubscriptionUnitsPurchased()
    {
        return $this->_subscriptionUnitsPurchased;
    }

    public function getSubscriptionUnitsReserved()
    {
        return $this->_subscriptionUnitsReserved;
    }

    public function setId($id)
    {
        $this->_checkImmutableChange('_id');
        $this->_id = $id;
    }

    public function setBrandId($brandId)
    {
        $this->_checkImmutableChange('_brandId');
        $this->_brandId = $brandId;
    }

    public function setSubscriptionId($subscriptionId)
    {
        $this->_checkImmutableChange('_subscriptionId');
        $this->_subscriptionId = $subscriptionId;
    }

    public function setSequenceIndex($sequenceIndex)
    {
        $this->_sequenceIndex = $sequenceIndex;
    }

    public function setItemId($itemId)
    {
        $this->_itemId = $itemId;
    }

    public function setUnitsProgrammed($unitsProgrammed)
    {
        $this->_unitsProgrammed = $unitsProgrammed;
    }

    public function setStoreUnitsReserved($storeUnitsReserved)
    {
        $this->_storeUnitsReserved = $storeUnitsReserved;
    }

    public function setInfluencerUnitsReserved($influencerUnitsReserved)
    {
        $this->_influencerUnitsReserved = $influencerUnitsReserved;
    }

    public function setSubscriptionUnitsProgrammed($subscriptionUnitsProgrammed)
    {
        $this->_subscriptionUnitsProgrammed = $subscriptionUnitsProgrammed;
    }

    public function setSubscriptionUnitsPurchased($subscriptionUnitsPurchased)
    {
        $this->_subscriptionUnitsPurchased = $subscriptionUnitsPurchased;
    }

    public function setSubscriptionUnitsReserved($subscriptionUnitsReserved)
    {
        $this->_subscriptionUnitsReserved = $subscriptionUnitsReserved;
    }

    // ---------------------------------------------------------------------------------------------
    // Additional methods for extra functionality
    
    /**
     * Returns the number of units locked from subscriptions (Purchased + Reserved).
     * @return int
     */
    public function getSubscriptionUnitsLocked()
    {
        return $this->_subscriptionUnitsPurchased + $this->_subscriptionUnitsReserved;
    }
    
    /**
     * Returns the number of units available from new subscriptions.
     * @return int
     */
    public function getSubscriptionUnitsAvailable()
    {
        return $this->_subscriptionUnitsProgrammed - $this->getSubscriptionUnitsLocked();
    }
    
    /**
     * Returns whether there are any available units from new subscriptions.
     * @return boolean
     */
    public function hasAvailableSubscriptionUnits()
    {
        return $this->getSubscriptionUnitsAvailable() > 0;
    }
    
    /**
     * The period in which this item is available
     * @return int
     */
    public function getPeriodIndex()
    {
        return $this->_periodIndex;
    }

    /**
     * The deadline time for subscribers to get this item.
     * @return int Timestamp since EPOC
     */
    public function getDeadlineTime()
    {
        return $this->_deadlineTime;
    }

    /**
     * The charge time for subscribers to get this item.
     * @return int Timestamp since EPOC
     */
    public function getChargeTime()
    {
        return $this->_chargeTime;

    }

    /**
     * The time this item is expected to start arriving.
     * @return int Timestamp since EPOC
     */
    public function getDeliveryStartTime()
    {
        return $this->_deliveryStartTime;
    }

    /**
     * The time this item is expected to arrive at the latest.
     * @return int Timestamp since EPOC
     */
    public function getDeliveryEndTime()
    {
        return $this->_deliveryEndTime;
    }
        
    /**
     * Set the Deadline and Shipping dates for this sequence item.
     * @param type $periodIndex
     * @param type $deadlineTime
     * @param type $chargeTime
     * @param type $deliveryStartTime
     * @param type $deliveryEndTime
     */
    public function setSubscriptionDates($periodIndex, $deadlineTime, $chargeTime, $deliveryStartTime, $deliveryEndTime)
    {
        $this->_deadlineTime      = $deadlineTime;
        $this->_chargeTime        = $chargeTime;
        $this->_periodIndex       = $periodIndex;
        $this->_deliveryStartTime = $deliveryStartTime;
        $this->_deliveryEndTime   = $deliveryEndTime;
    }
}
