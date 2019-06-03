<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Enum\Shipping;

use \Pley\Shipping\Carrier\Exception\InvalidCarrierServiceException;

/**
 * The <kbd>CarrierServiceEnum</kbd> declares constants for our supported Carrier Services.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum.Shipping
 * @subpackage Enum
 */
abstract class CarrierServiceEnum extends \Pley\Enum\AbstractEnum
{   
    // USPS Carrier will use the 10XX range
    const CARRIER_USPS                           = 1000;
    const USPS_FIRST_CLASS                       = 1001;
    const USPS_PRIORITY                          = 1002;
    const USPS_EXPRESS                           = 1003;
    const USPS_PARCEL_SELECT                     = 1004;
    const USPS_LIBRARY_MAIL                      = 1005;
    const USPS_MEDIA_MAIL                        = 1006;
    const USPS_CRITICAL_MAIL                     = 1007;
    const USPS_FIRST_CLASS_MAIL_INTERNATIONAL    = 1008;
    const USPS_FIRST_CLASS_PACKAGE_INTERNATIONAL = 1009;
    const USPS_PRIORITY_MAIL_INTERNATIONAL       = 1010;
    const USPS_EXPRESS_MAIL_INTERNATIONAL        = 1011;
    
    // UPS Carrier will use the 20XX range
    const CARRIER_UPS                            = 2000;
    const UPS_GROUND                             = 2001;
    const UPS_STANDARDS                          = 2002;
    const UPS_SAVER                              = 2003;
    const UPS_EXPRESS                            = 2004;
    const UPS_EXPRESS_PLUS                       = 2005;
    const UPS_EXPEDITED                          = 2006;
    const UPS_NEXT_DAY_AIR                       = 2007;
    const UPS_NEXT_DAY_AIR_SAVER                 = 2008;
    const UPS_NEXT_DAY_AIR_EARLY_AM              = 2009;
    const UPS_2ND_DAY_AIR                        = 2010;
    const UPS_2ND_DAY_AIR_AM                     = 2011;
    const UPS_3DAY_SELECT                        = 2012;
    
    // UPS Sure Post Carrier will use the 21XX range
    const CARRIER_UPS_SURE_POST                  = 2100;
    const UPS_SP_UNDER_1LB                       = 2101;
    const UPS_SP_OVER_1LB                        = 2102;
    
    // UPS Mail Innovations Carrier will use the 22XX range
    const CARRIER_UPS_MAIL_INNOVATIONS           = 2200;
    const UPS_MI_FIRST                           = 2201;
    const UPS_MI_PRIORITY_MAIL                   = 2202;
    const UPS_MI_EXPEDITED_MAIL_INNOVATIONS      = 2203;
    const UPS_MI_PRIORITY_MAIL_INNOVATIONS       = 2204;
    const UPS_MI_ECONOMY_MAIL_INNOVATIONS        = 2205;
    const UPS_MI_SINGLE_RETURN                   = 2206;
    
    // FedEx Carrier will use the 30XX range
    const CARRIER_FEDEX                          = 3000;
    const FEDEX_GROUND                           = 3001;
    const FEDEX_2_DAY                            = 3002;
    const FEDEX_2_DAY_AM                         = 3003;
    const FEDEX_EXPRESS_SAVER                    = 3004;
    const FEDEX_STANDARD_OVERNIGHT               = 3005;
    const FEDEX_FIRST_OVERNIGHT                  = 3006;
    const FEDEX_PRIORITY_OVERNIGHT               = 3007;
    const FEDEX_INTERNATIONAL_ECONOMY            = 3008;
    const FEDEX_INTERNATIONAL_FIRST              = 3009;
    const FEDEX_INTERNATIONAL_PRIORITY           = 3010;
    const FEDEX_GROUND_HOME_DELIVERY             = 3011;
    const FEDEX_SMART_POST                       = 3012;

    // DHL "Global" Carrier will use the 40XX range
    const CARRIER_DHL_GLOBAL                             = 4000;
    const DHL_GLOBAL_BPM_EXPEDITED_DOMESTIC              = 4001;
    const DHL_GLOBAL_BPM_GROUND_DOMESTIC                 = 4002;
    const DHL_GLOBAL_FLATS_EXPEDITED_DOMESTIC            = 4003;
    const DHL_GLOBAL_FLATS_GROUND_DOMESTIC               = 4004;
    const DHL_GLOBAL_MEDIA_MAIL_GROUND_DOMESTIC          = 4005;
    const DHL_GLOBAL_PARCEL_PLUS_EXPEDITED_DOMESTIC      = 4006;
    const DHL_GLOBAL_PARCEL_PLUS_GROUND_DOMESTIC         = 4007;
    const DHL_GLOBAL_PARCELS_EXPEDITED_DOMESTIC          = 4008;
    const DHL_GLOBAL_PARCELS_GROUND_DOMESTIC             = 4009;
    const DHL_GLOBAL_MARKETING_PARCEL_EXPEDITED_DOMESTIC = 4010;
    const DHL_GLOBAL_MARKETING_PARCEL_GROUND_DOMESTIC    = 4011;
    
    // DHL "First Mile" Carrier will use the 41XX range
    // DHL First Mile is just a variation of DHL Global, so we just provide a different values for
    // our internal tracking (But 3rd party mappers `CarrierServiceMapper` will map these constants
    // to the right DHL Global values)
    const CARRIER_DHL_FIRST_MILE            = 4100;
    const DHL_FIRST_MILE_PARCEL_PLUS_GROUND = 4107; // DHL_GLOBAL_PARCEL_PLUS_GROUND_DOMESTIC
    const DHL_FIRST_MILE_EXPEDITED_PARCEL   = 4108; // DHL_GLOBAL_PARCELS_EXPEDITED_DOMESTIC
    const DHL_FIRST_MILE_GROUND_PARCEL      = 4109; // DHL_GLOBAL_PARCELS_GROUND_DOMESTIC

    /**
     * Returns the Carrier ID for the supplied Service ID.
     * @param int $serviceId
     * @return int
     * @throws \Pley\Shipping\Carrier\Exception\InvalidCarrierServiceException If unknown service.
     */
    public static function getCarrierForService($serviceId)
    {
        if ($serviceId >= 1001  && $serviceId <= 1099) {
            return self::CARRIER_USPS;
        }
        if ($serviceId >= 2101  && $serviceId <= 2199) {
            return self::CARRIER_UPS_SURE_POST;
        }
        if ($serviceId >= 2201  && $serviceId <= 2299) {
            return self::CARRIER_UPS_MAIL_INNOVATIONS;
        }
        if ($serviceId >= 3001  && $serviceId <= 3099) {
            return self::CARRIER_FEDEX;
        }
        if ($serviceId >= 4001  && $serviceId <= 4099) {
            return self::CARRIER_DHL_GLOBAL;
        }
        if ($serviceId >= 4101  && $serviceId <= 4199) {
            return self::CARRIER_DHL_FIRST_MILE;
        }
        
        throw new InvalidCarrierServiceException($serviceId);
    }

    public static function getServiceIdByCode($serviceCode){
        if(defined(__CLASS__ . '::' . $serviceCode)){
            return constant(__CLASS__ . '::' . $serviceCode);
        }
    }
}
