<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Shipping\Shipment;

/**
 * The <kbd>ShipmentMeta</kbd> holds additional information needed to create a shipment object.
 * <p>Done as an object so method signature doesn't have to change much but can support future
 * metadata elements.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping.Shipment
 * @subpackage Shipping
 */
class ShipmentMeta
{
    /** @var string */
    public $upsMICostCenter;
    /** @var int */
    public $referenceNo;
    
    // Constuctor is just used to initialize default values
    public function __construct()
    {
        $this->isReturn    = false;
        $this->referenceNo = time();
    }
}
