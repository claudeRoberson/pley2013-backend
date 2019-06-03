<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Shipping\Shipment;

/**
 * The <kbd>ShipmentRate</kbd> holds information about the rate cost using a specific carrier service.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping.Shipment
 * @subpackage Shipping
 */
class ShipmentRate
{
    /** @var int A value from \Pley\Enum\Shipping\CarrierServiceEnum */
    protected $_carrier;
    /** @var int A value from \Pley\Enum\Shipping\CarrierServiceEnum */
    protected $_service;
    /** @var float */
    protected $_rate;

    /** ♰
     * @param \Pley\Shipping\Carrier\CarrierService $carrierService
     * @param float                                 $rate
     * @return \Pley\Shipping\Shipment\ShipmentRate
     */
    public static function withCarrierService(\Pley\Shipping\Carrier\CarrierService $carrierService, $rate)
    {
        return new static($carrierService->getCarrier(), $carrierService->getService(), $rate);
    }
    
    public function __construct($carrier, $service, $rate)
    {
        $this->_carrier = $carrier;
        $this->_service = $service;
        $this->_rate    = $rate;
    }

    /** @return int A value from \Pley\Enum\Shipping\CarrierServiceEnum */
    public function getCarrier()
    {
        return $this->_carrier;
    }

    /** @return int A value from \Pley\Enum\Shipping\CarrierServiceEnum */
    public function getService()
    {
        return $this->_service;
    }

    /** @return float */
    public function getRate()
    {
        return $this->_rate;
    }
}
