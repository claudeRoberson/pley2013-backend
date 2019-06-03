<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Shipping\Shipment;

/**
 * The <kbd>AbstractShipment</kbd> is the base Shipment definition class.
 * <p>It is not meant to be used directly for we Shipments are handled by a 3rd Party service
 * and thus require to have specific implementation, this just allows us to have a common interface
 * for our codebase to be agnostic of the 3rd party implementation.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping.Shipment
 * @subpackage Shipping
 */
abstract class AbstractShipment
{
    const COUNTRY_CODE_UNITED_STATES = 'US';

    /** @var \Pley\Shipping\Shipment\ShipmentParcel */
    protected $_parcel;
    /** @var \Pley\Shipping\Shipment\ShipmentAddress */
    protected $_fromAddress;
    /** @var \Pley\Shipping\Shipment\ShipmentAddress */
    protected $_toAddress;
    /** @var \Pley\Shipping\Shipment\ShipmentMeta */
    protected $_shipmentMeta;
    /** @var int */
    protected $_shippingZoneId;
    /** @var int */
    protected $_uspsShippingZoneId;
    /** @var string */
    protected $_vendorShipId;

    public function __construct(
        ShipmentParcel $parcel,
        ShipmentAddress $fromAddress,
        ShipmentAddress $toAddress,
        ShipmentMeta $shipmentMeta,
        $shippingZoneId,
        $uspsShippingZoneId)
    {
        $this->_parcel = $parcel;
        $this->_fromAddress = $fromAddress;
        $this->_toAddress = $toAddress;
        $this->_shipmentMeta = $shipmentMeta;
        $this->_shippingZoneId = $shippingZoneId;
        $this->_uspsShippingZoneId = $uspsShippingZoneId;
    }
    
    /** @return \Pley\Shipping\Shipment\ShipmentParcel */
    public function getParcel()
    {
        return $this->_parcel;
    }
    
    /** @return \Pley\Shipping\Shipment\ShipmentAddress */
    public function getFromAddress()
    {
        return $this->_fromAddress;
    }

    /** @return \Pley\Shipping\Shipment\ShipmentAddress */
    public function getToAddress()
    {
        return $this->_toAddress;
    }

    /** @return int */
    public function getShippingZoneId()
    {
        return $this->_shippingZoneId;
    }

    /** @return int */
    public function getUspsShippingZoneId()
    {
        return $this->_uspsShippingZoneId;
    }

    /** @return \Pley\Shipping\Shipment\ShipmentMeta */
    public function getShipmentMeta()
    {
        return $this->_shipmentMeta;
    }
    
    /** @return string */
    public function getVendorShipId()
    {
        return $this->_vendorShipId;
    }

    /** @param string $vendorShipId */
    public function setVendorShipId($vendorShipId)
    {
        $this->_vendorShipId = $vendorShipId;
    }
    /**
     * Checks if destination country is United States
     * @return bool
     */
    public function isUsShipment()
    {
        return $this->_toAddress->getCountry() === self::COUNTRY_CODE_UNITED_STATES;
    }
    
}
