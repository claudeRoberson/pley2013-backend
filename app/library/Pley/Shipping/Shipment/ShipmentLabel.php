<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Shipping\Shipment;

/**
 * The <kbd>ShipmentLabel</kbd> holds information about a purchased label.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping.Shipment
 * @subpackage Shipping
 */
class ShipmentLabel
{
    /** @var \Pley\Shipping\Shipment\ShipmentRate */
    protected $_rate;
    /** @var string */
    protected $_trackingNo;
    /** @var string */
    protected $_labelUrl;
    /** @var string */
    protected $_vendorShipId;
    
    public function __construct(ShipmentRate $rate, $trackingNo, $labelUrl, $vendorShipId)
    {
        $this->_rate         = $rate;
        $this->_trackingNo   = $trackingNo;
        $this->_labelUrl     = $labelUrl;
        $this->_vendorShipId = $vendorShipId;
    }
    
    /** @return \Pley\Shipping\Shipment\ShipmentRate */
    public function getShipmentRate()
    {
        return $this->_rate;
    }

    /** @return string */
    public function getTrackingNo()
    {
        return $this->_trackingNo;
    }

    /** @return string */
    public function getLabelUrl()
    {
        return $this->_labelUrl;
    }
    
    /** @return string */
    public function getVendorShipId()
    {
        return $this->_vendorShipId;
    }

}
