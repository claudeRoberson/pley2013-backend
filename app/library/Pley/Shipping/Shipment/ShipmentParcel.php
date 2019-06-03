<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Shipping\Shipment;

/** ♰
 * The <kbd>ShipmentParcel</kbd> defines a SET dimensions and weight for the shipment.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping.Shipment
 * @subpackage Shipping
 */
class ShipmentParcel
{
    /** @var \Pley\Shipping\Shipment\ShipmentParcelDimension */
    protected $_dimensionsCm;
    /** @var \Pley\Shipping\Shipment\ShipmentParcelDimension */
    protected $_dimensionsIn;
    /** @var float */
    protected $_weightGr;
    /** @var float */
    protected $_weightOz;
    /** @var \Pley\Entity\Subscription\Item */
    protected $_item;
    
    private function __construct() {}
    
    /** ♰
     * @param \Pley\Entity\Subscription\Item $item
     * @return \Pley\Shipping\Shipment\ShipmentParcel
     */
    public static function fromItem(\Pley\Entity\Subscription\Item $item)
    {
        $shipmentParcel = new static();
        $shipmentParcel->_item = $item;
        
        $shipmentParcel->_dimensionsCm = new ShipmentParcelDimension(
            $item->getLengthCm(), $item->getWidthCm(), $item->getHeightCm()
        );
        $shipmentParcel->_dimensionsIn = new ShipmentParcelDimension(
            \Pley\Util\Converter\DistanceConverter::centimetersToInches($item->getLengthCm()), 
            \Pley\Util\Converter\DistanceConverter::centimetersToInches($item->getWidthCm()), 
            \Pley\Util\Converter\DistanceConverter::centimetersToInches($item->getHeightCm())
        );
        
        $shipmentParcel->_weightGr = $item->getWeightGr();
        $shipmentParcel->_weightOz = \Pley\Util\Converter\WeightConverter::gramsToOunces($item->getWeightGr());

        return $shipmentParcel;
    }
    
    /**
     * Returns an object representing the Parcel dimensions in Inches.
     * @return \Pley\Shipping\Shipment\ShipmentParcelDimension
     */
    public function getDimensionsIn()
    {
        return $this->_dimensionsIn;
    }

    /**
     * Returns an object representing the Parcel dimensions in Centimeters.
     * @return \Pley\Shipping\Shipment\ShipmentParcelDimension
     */
    public function getDimensionsCm()
    {
        return $this->_dimensionsCm;
    }

    /**
     * Returns the Parcel weight in Grams.
     * @return float
     */
    public function getWeightGr()
    {
        return $this->_weightGr;
    }

    /**
     * Returns the Parcel weight in Ounces.
     * @return float
     */
    public function getWeightOz()
    {
        return $this->_weightOz;
    }

    /** @return \Pley\Entity\Subscription\Item */
    public function getItem()
    {
        return $this->_item;
    }

}

/** ♰ */
class ShipmentParcelDimension
{
    /** @var float */
    private $_length;
    /** @var float */
    private $_width;
    /** @var float */
    private $_height;
    
    public function __construct($length, $width, $height)
    {
        $this->_length = $length;
        $this->_width  = $width;
        $this->_height = $height;
    }

    /** @return float */
    public function getLength()
    {
        return $this->_length;
    }

    /** @return float */
    public function getWidth()
    {
        return $this->_width;
    }

    /** @return float */
    public function getHeight()
    {
        return $this->_height;
    }

}