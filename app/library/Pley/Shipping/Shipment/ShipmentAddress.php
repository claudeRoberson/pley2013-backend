<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Shipping\Shipment;

use \Pley\Entity\Shipping\Warehouse;
use \Pley\Entity\User\UserAddress;

/**
 * The <kbd>ShipmentAddress</kbd> defines a shipping destination.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping.Shipment
 * @subpackage Shipping
 */
class ShipmentAddress
{
    /** @var string */
    protected $_name;
    /** @var string */
    protected $_street1;
    /** @var string */
    protected $_street2;
    /** @var string */
    protected $_city;
    /** @var string */
    protected $_state;
    /** @var string */
    protected $_country;
    /** @var string */
    protected $_zipCode;
    
    public function __construct($name, $street1, $street2, $city, $state, $country, $zipCode)
    {
        $this->_name    = $name;
        $this->_street1 = $street1;
        $this->_street2 = $street2;
        $this->_city    = $city;
        $this->_state   = $state;
        $this->_country = $country;
        $this->_zipCode = $zipCode;
    }
    
    /**
     * Creates a new <kbd>ShipmentAddress</kbd> object from a supplied <kbd>UserAddress</kbd> object
     * and sets the Adressee if supplied.
     * @param \Pley\Entity\User\UserAddress $userAddress
     * @return \Pley\Shipping\Shipment\ShipmentAddress
     */
    public static function fromUserAddress(UserAddress $userAddress)
    {
        $shipmentAddress = new static(
            null,
            $userAddress->getStreet1(),
            $userAddress->getStreet2(),
            $userAddress->getCity(),
            $userAddress->getState(),
            $userAddress->getCountry(),
            $userAddress->getZipCode()
        );
        
        return $shipmentAddress;
    }
    
    /**
     * Creates a new <kbd>ShipmentAddress</kbd> object from a supplied <kbd>Warehouse</kbd> object.
     * @param \Pley\Entity\Shipping\Warehouse $warehouseAddress
     * @return \Pley\Shipping\Shipment\ShipmentAddress
     */
    public static function fromWarehouseAddress(Warehouse $warehouseAddress)
    {
        $shipmentAddress = new static();
        $shipmentAddress->_name    = $warehouseAddress->getShipToName();
        $shipmentAddress->_street1 = $warehouseAddress->getStreet1();
        $shipmentAddress->_street2 = $warehouseAddress->getStreet2();
        $shipmentAddress->_city    = $warehouseAddress->getCity();
        $shipmentAddress->_state   = $warehouseAddress->getState();
        $shipmentAddress->_country = $warehouseAddress->getCountry();
        $shipmentAddress->_zipCode = $warehouseAddress->getZipCode();

        return $shipmentAddress;
    }
    
    /** @return string */
    public function getName()
    {
        return $this->_name;
    }

    /** @param string $name */
    public function setName($name)
    {
        $this->_name = $name;
    }
    
    /** @return string */
    public function getStreet1()
    {
        return $this->_street1;
    }

    /** @return string */
    public function getStreet2()
    {
        return $this->_street2;
    }

    /** @return string */
    public function getCity()
    {
        return $this->_city;
    }

    /** @return string */
    public function getState()
    {
        return $this->_state;
    }

    /** @return string */
    public function getCountry()
    {
        return $this->_country;
    }

    /** @return string */
    public function getZipCode()
    {
        return $this->_zipCode;
    }
}
