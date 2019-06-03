<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Shipping\Impl\EasyPost;

use \Pley\Enum\Shipping\CarrierServiceEnum;

/**
 * The <kbd>CarrierMapper</kbd> is a helper class to map between our agnostic shipping carrier ids
 * and the string used by EasyPost.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping.Impl.EasyPost
 * @subpackage Shipping
 */
class CarrierMapper
{
    public static $carrierMap= [
        CarrierServiceEnum::CARRIER_USPS                 => 'USPS',
        CarrierServiceEnum::CARRIER_UPS                  => 'UPS',
        CarrierServiceEnum::CARRIER_UPS_MAIL_INNOVATIONS => 'UPSMailInnovations',
        CarrierServiceEnum::CARRIER_UPS_SURE_POST        => 'UPSSurePost',
        CarrierServiceEnum::CARRIER_DHL_GLOBAL           => 'DHLGlobalMail',
        
        // DHL First Mile uses DHL Global, so we do the internal mapping to work for EasyPost but
        // allow us to identify which part of the DHL service we used.
        CarrierServiceEnum::CARRIER_DHL_FIRST_MILE       => 'DHLGlobalMail',
    ];
    
    /**
     * Retruns the EasyPost carrier string associated to our carrier int id.
     * 
     * @param int $carrierId Id from \Pley\Enum\Shipping\CarrierServiceEnum
     * @return string
     * @see \Pley\Enum\Shipping\CarrierServiceEnum
     */
    public function getEasyPostCarrier($carrierId)
    {
        return static::$carrierMap[$carrierId];
    }
    
    /**
     * Retruns our carrier id associated to the EasyPost carrier string.
     * 
     * @param string $carrierStr
     * @return string A value from \Pley\Enum\Shipping\CarrierServiceEnum
     * @see \Pley\Enum\Shipping\CarrierServiceEnum|null Null is returned if a new serive is returned
     *  by EasyPost to which we have no knowledge of.
     */
    public function getCarrier($carrierStr)
    {
        $carrierMap = array_flip(static::$carrierMap);
        
        if (isset($carrierMap[$carrierStr])) {
            return $carrierMap[$carrierStr];
        }
        
        return null;
    }
}
