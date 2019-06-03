<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Entity\Profile;

/** ♰
 * The <kbd>ProfileSubscriptionShipment</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Profile
 * @subpackage Entity
 */
class ProfileSubscriptionShipment extends \Pley\DataMap\Entity
{
    /** @var int */
    protected $_id;
    /** @var int */
    protected $_userId;
    /** @var int */
    protected $_profileId;
    /** @var int */
    protected $_profileSubscriptionId;
    /** @var int */
    protected $_subscriptionId;
    /** @var int */
    protected $_shipmentSourceType;
    /** @var int */
    protected $_shipmentSourceId;
    /** @var int */
    protected $_scheduleIndex;
    /** @var int */
    protected $_itemSequenceIndex;
    /** @var int */
    protected $_itemId;
    /** @var int */
    protected $_status;
    /** @var int */
    protected $_shirtSize;
    /** @var int */
    protected $_carrierId;
    /** @var int */
    protected $_carrierServiceId;
    /** @var float */
    protected $_carrierRate;
    /** @var string */
    protected $_labelUrl;
    /** @var string */
    protected $_trackingNo;
    /** @var string */
    protected $_vendorShipId;
    /** @var string */
    protected $_vendorShipTrackerId;
    /** @var int */
    protected $_shippedAt;
    /** @var int */
    protected $_deliveredAt;
    /** @var int */
    protected $_labelPurchaseAt;
    /** @var string */
    protected $_street1;
    /** @var string */
    protected $_street2;
    /** @var string */
    protected $_city;
    /** @var string */
    protected $_state;
    /** @var string */
    protected $_zip;
    /** @var string */
    protected $_country;
    /** @var int */
    protected $_shippingZoneId;
    /** @var int */
    protected $_uspsShippingZoneId;
    /** @var int */
    protected $_labelLease;
    
    /** ♰
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment
     */
    public static function withNew($userId, $profileId, $profileSubscriptionId, $subscriptionId,
            $shipmentSourceType, $shipmentSourceId, $scheduleIndex, $itemSequenceIndex)
    {
        $itemId = null; $shirtSize = null;
        $carrierId = $carrierServiceId = $carrierRate = $labelUrl = $trackingNo = null;
        $vendorShipId = $vendorShipTrackerId = null;
        $shippedAt = $deliveredAt = $labelPurchaseAt = null;
        $street1 = $street2 = $city = $state = $zip = $country = $uspsShippingZoneId = $shippingZoneId = null;
        $labelLease = null;
        
        $status = \Pley\Enum\Shipping\ShipmentStatusEnum::PREPROCESSING;
        
        return new static(null, $userId, $profileId, $profileSubscriptionId, $subscriptionId,
            $shipmentSourceType, $shipmentSourceId, $scheduleIndex, $itemSequenceIndex, $itemId,
            $status, $shirtSize, $carrierId, $carrierServiceId, $carrierRate, $labelUrl, $trackingNo, 
            $vendorShipId, $vendorShipTrackerId,
            $shippedAt, $deliveredAt, $labelPurchaseAt,
            $street1, $street2, $city, $state, $zip, $country,
            $shippingZoneId, $uspsShippingZoneId,
            $labelLease);
    }
    
    public function __construct($id, $userId, $profileId, $profileSubscriptionId, $subscriptionId, 
            $shipmentSourceType, $shipmentSourceId, $scheduleIndex, $itemSequenceIndex, $itemId,
            $status, $shirtSize, $carrierId, $carrierServiceId, $carrierRate, $labelUrl, $trackingNo, 
            $vendorShipId, $vendorShipTrackerId, 
            $shippedAt, $deliveredAt, $labelPurchaseAt,
            $street1, $street2, $city, $state, $zip, $country,
            $shippingZoneId, $uspsShippingZoneId,
            $labelLease)
    {
        $this->_id                    = $id;
        $this->_userId                = $userId;
        $this->_profileId             = $profileId;
        $this->_profileSubscriptionId = $profileSubscriptionId;
        $this->_subscriptionId        = $subscriptionId;
        $this->_shipmentSourceType    = $shipmentSourceType;
        $this->_shipmentSourceId      = $shipmentSourceId;
        $this->_scheduleIndex         = $scheduleIndex;
        $this->_itemSequenceIndex     = $itemSequenceIndex;
        $this->_itemId                = $itemId;
        $this->_status                = $status;
        $this->_shirtSize             = $shirtSize;
        $this->_carrierId             = $carrierId;
        $this->_carrierServiceId      = $carrierServiceId;
        $this->_carrierRate           = $carrierRate;
        $this->_labelUrl              = $labelUrl;
        $this->_trackingNo            = $trackingNo;
        $this->_vendorShipId          = $vendorShipId;
        $this->_vendorShipTrackerId   = $vendorShipTrackerId;
        $this->_shippedAt             = $shippedAt;
        $this->_deliveredAt           = $deliveredAt;
        $this->_labelPurchaseAt       = $labelPurchaseAt;
        $this->_street1               = $street1;
        $this->_street2               = $street2;
        $this->_city                  = $city;
        $this->_state                 = $state;
        $this->_zip                   = $zip;
        $this->_country               = $country;
        $this->_shippingZoneId        = $shippingZoneId;
        $this->_uspsShippingZoneId    = $uspsShippingZoneId;
        $this->_labelLease            = $labelLease;
    }

    public function getId()
    {
        return $this->_id;
    }
    
    public function setId($id)
    {
        if (isset($this->_id)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_id');
        }
        $this->_id = $id;
    }

    public function getUserId()
    {
        return $this->_userId;
    }

    public function getProfileId()
    {
        return $this->_profileId;
    }

    public function getProfileSubscriptionId()
    {
        return $this->_profileSubscriptionId;
    }
    
    public function getSubscriptionId()
    {
        return $this->_subscriptionId;
    }

    public function getShipmentSourceType()
    {
        return $this->_shipmentSourceType;
    }

    public function getShipmentSourceId()
    {
        return $this->_shipmentSourceId;
    }

    public function getScheduleIndex()
    {
        return $this->_scheduleIndex;
    }

    public function setScheduleIndex($scheduleIndex)
    {
        $this->_scheduleIndex = $scheduleIndex;
    }

    public function getItemSequenceIndex()
    {
        return $this->_itemSequenceIndex;
    }

    public function setItemSequenceIndex($itemSequenceIndex)
    {
        $this->_itemSequenceIndex = $itemSequenceIndex;
    }

    public function getItemId()
    {
        return $this->_itemId;
    }
    
    public function setItemId($itemId)
    {
        $this->_itemId = $itemId;
    }

    public function getStatus()
    {
        return $this->_status;
    }
    
    public function setStatus($status)
    {
        $this->_status = $status;
    }
    
    public function getShirtSize()
    {
        return $this->_shirtSize;
    }
    
    public function setShirtSize($sizeId)
    {
        if (isset($this->_shirtSize)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_shirtSize');
        }
        $this->_shirtSize = $sizeId;
    }

    public function getCarrierId()
    {
        return $this->_carrierId;
    }

    public function getCarrierServiceId()
    {
        return $this->_carrierServiceId;
    }
    
    public function getCarrierRate()
    {
        return $this->_carrierRate;
    }

    public function getLabelUrl()
    {
        return $this->_labelUrl;
    }

    public function getTrackingNo()
    {
        return $this->_trackingNo;
    }

    public function getVendorShipId()
    {
        return $this->_vendorShipId;
    }

    public function getVendorShipTrackerId()
    {
        return $this->_vendorShipTrackerId;
    }

    public function setVendorShipTrackerId($vTrackerId)
    {
        if (isset($this->_vendorShipTrackerId)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_vendorShipTrackerId');
        }
        
        $this->_vendorShipTrackerId = $vTrackerId;
    }

    public function getShippedAt()
    {
        return $this->_shippedAt;
    }

    public function setShippedAt($time)
    {
        if (isset($this->_shippedAt)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_shippedAt');
        }
        
        $this->_shippedAt = $time;
        
        // Updating the Shipment status as well, but only if the shipment hasn't been delivered
        // Just to make sure that a later in transit event does not override a delivered status.
        if (empty($this->_deliveredAt)) {
            $this->_status = \Pley\Enum\Shipping\ShipmentStatusEnum::IN_TRANSIT;
        }
    }

    public function getDeliveredAt()
    {
        return $this->_deliveredAt;
    }

    public function setDeliveredAt($time)
    {
        if (isset($this->_deliveredAt)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_deliveredAt');
        }
        
        $this->_deliveredAt = $time;
        $this->_status      = \Pley\Enum\Shipping\ShipmentStatusEnum::DELIVERED;
    }

    public function getLabelPurchaseAt()
    {
        return $this->_labelPurchaseAt;
    }

    public function getStreet1()
    {
        return $this->_street1;
    }

    public function getStreet2()
    {
        return $this->_street2;
    }

    public function getCity()
    {
        return $this->_city;
    }

    public function getState()
    {
        return $this->_state;
    }

    public function getZip()
    {
        return $this->_zip;
    }

    public function getCountry()
    {
        return $this->_country;
    }

    public function getShippingZoneId()
    {
        return $this->_shippingZoneId;
    }

    public function getUspsShippingZoneId()
    {
        return $this->_uspsShippingZoneId;
    }

    public function getLabelLease()
    {
        return $this->_labelLease;
    }

    public function setLabelLease($labelLease)
    {
        $this->_labelLease = $labelLease;
    }

    public function setLabel(
        \Pley\Shipping\Shipment\AbstractShipment $shipment,
        \Pley\Shipping\Shipment\ShipmentLabel $shipmentLabel)
    {
        if (isset($this->_labelUrl)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_labelUrl');
        }

        $shipmentAddress = $shipment->getToAddress();

        $this->_street1 = $shipmentAddress->getStreet1();
        $this->_street2 = $shipmentAddress->getStreet2();
        $this->_city = $shipmentAddress->getCity();
        $this->_state = $shipmentAddress->getState();
        $this->_zip = $shipmentAddress->getZipCode();
        $this->_country = $shipmentAddress->getCountry();
        $this->_shippingZoneId = $shipment->getShippingZoneId();
        $this->_uspsShippingZoneId = $shipment->getUspsShippingZoneId();

        $this->_carrierId = $shipmentLabel->getShipmentRate()->getCarrier();
        $this->_carrierServiceId = $shipmentLabel->getShipmentRate()->getService();
        $this->_carrierRate = $shipmentLabel->getShipmentRate()->getRate();
        $this->_labelUrl = $shipmentLabel->getLabelUrl();
        $this->_trackingNo = $shipmentLabel->getTrackingNo();
        $this->_vendorShipId = $shipmentLabel->getVendorShipId();

        $this->_labelPurchaseAt = time();
    }

}
