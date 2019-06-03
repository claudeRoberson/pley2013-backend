<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Shipping\Carrier;

/**
 * The <kbd>CarrierService</kbd> class is a simple object to hold the carrier id and the service
 * to use from it.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping.Carrier
 * @subpackage Shipping
 */
class CarrierService
{
    /**
     * Value indicative of the type of out-bound carrier service.
     * @var int
     * @see \Pley\Enum\Shipping\CarrierServiceEnum
     */
    protected $_carrier;
    /**
     * Value indicative of the type of in-bound carrier service.
     * @var int
     * @see \Pley\Enum\Shipping\CarrierServiceEnum
     */
    protected $_service;
    
    /**
     * Creates a new <kbd>CarrierService</kbd> instance withe the specified carrier, service and
     * flag to indicate if the service supports the return label.
     * 
     * @param int $carrier Value from \Pley\Enum\Shipping\CarrierServiceEnum
     * @param int $service Value from \Pley\Enum\Shipping\CarrierServiceEnum
     * @see \Pley\Enum\Shipping\CarrierServiceEnum
     */
    public function __construct($carrier, $service)
    {
        $this->_carrier = $carrier;
        $this->_service = $service;
    }

    /**
     * Return value indicative of the Carrier id
     * @return int
     * @see \Pley\Enum\Shipping\CarrierServiceEnum
     */
    public function getCarrier()
    {
        return $this->_carrier;
    }

    /**
     * Return value indicative of Carrier Service id.
     * @return int
     * @see \Pley\Enum\Shipping\CarrierServiceEnum
     */
    public function getService()
    {
        return $this->_service;
    }
}
