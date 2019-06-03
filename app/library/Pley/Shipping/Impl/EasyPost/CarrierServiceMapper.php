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
class CarrierServiceMapper
{
    protected static $_carrierServiceMap = [
        // USPS services
        CarrierServiceEnum::CARRIER_USPS => [
            CarrierServiceEnum::USPS_FIRST_CLASS                       => 'First',
            CarrierServiceEnum::USPS_PRIORITY                          => 'Priority',
            CarrierServiceEnum::USPS_EXPRESS                           => 'Express',
            CarrierServiceEnum::USPS_PARCEL_SELECT                     => 'ParcelSelect',
            CarrierServiceEnum::USPS_LIBRARY_MAIL                      => 'LibraryMail',
            CarrierServiceEnum::USPS_MEDIA_MAIL                        => 'MediaMail',
            CarrierServiceEnum::USPS_CRITICAL_MAIL                     => 'CriticalMail',
            CarrierServiceEnum::USPS_FIRST_CLASS_MAIL_INTERNATIONAL    => 'FirstClassMailInternational',
            CarrierServiceEnum::USPS_FIRST_CLASS_PACKAGE_INTERNATIONAL => 'FirstClassPackageInternational',
            CarrierServiceEnum::USPS_PRIORITY_MAIL_INTERNATIONAL       => 'PriorityMailInternational',
            CarrierServiceEnum::USPS_EXPRESS_MAIL_INTERNATIONAL        => 'ExpressMailInternational',
        ],
        
        // UPS services
        CarrierServiceEnum::CARRIER_UPS => [
            CarrierServiceEnum::UPS_GROUND                             => 'Ground',
            CarrierServiceEnum::UPS_STANDARDS                          => 'UPSStandards',
            CarrierServiceEnum::UPS_SAVER                              => 'UPSSaver',
            CarrierServiceEnum::UPS_EXPRESS                            => 'Express',
            CarrierServiceEnum::UPS_EXPRESS_PLUS                       => 'ExpressPlus',
            CarrierServiceEnum::UPS_EXPEDITED                          => 'Expedited',
            CarrierServiceEnum::UPS_NEXT_DAY_AIR                       => 'NextDayAir',
            CarrierServiceEnum::UPS_NEXT_DAY_AIR_SAVER                 => 'NextDayAirSaver',
            CarrierServiceEnum::UPS_NEXT_DAY_AIR_EARLY_AM              => 'NextDayAirEarlyAM',
            CarrierServiceEnum::UPS_2ND_DAY_AIR                        => '2ndDayAir',
            CarrierServiceEnum::UPS_2ND_DAY_AIR_AM                     => '2ndDayAirAM',
            CarrierServiceEnum::UPS_3DAY_SELECT                        => '3DaySelect',
        ],
        
        // UPS Mail Innovation services
        CarrierServiceEnum::CARRIER_UPS_MAIL_INNOVATIONS => [
            CarrierServiceEnum::UPS_MI_FIRST                           => 'First',
            CarrierServiceEnum::UPS_MI_PRIORITY_MAIL                   => 'Priority',
            CarrierServiceEnum::UPS_MI_EXPEDITED_MAIL_INNOVATIONS      => 'ExpeditedMailInnovations',
            CarrierServiceEnum::UPS_MI_PRIORITY_MAIL_INNOVATIONS       => 'PriorityMailInnovations',
            CarrierServiceEnum::UPS_MI_ECONOMY_MAIL_INNOVATIONS        => 'EconomyMailInnovations',
            CarrierServiceEnum::UPS_MI_SINGLE_RETURN                   => 'SingleReturn',
        ],
        
        // UPS SurePost services
        CarrierServiceEnum::CARRIER_UPS_SURE_POST => [
            CarrierServiceEnum::UPS_SP_UNDER_1LB                       => 'SurePostUnder1Lb',
            CarrierServiceEnum::UPS_SP_OVER_1LB                        => 'SurePostOver1Lb',
        ],
        
        // FedEx services
        CarrierServiceEnum::CARRIER_FEDEX => [
            CarrierServiceEnum::FEDEX_GROUND                           => 'FEDEX_GROUND',
            CarrierServiceEnum::FEDEX_2_DAY                            => 'FEDEX_2_DAY',
            CarrierServiceEnum::FEDEX_2_DAY_AM                         => 'FEDEX_2_DAY_AM',
            CarrierServiceEnum::FEDEX_EXPRESS_SAVER                    => 'FEDEX_EXPRESS_SAVER',
            CarrierServiceEnum::FEDEX_STANDARD_OVERNIGHT               => 'STANDARD_OVERNIGHT',
            CarrierServiceEnum::FEDEX_FIRST_OVERNIGHT                  => 'FIRST_OVERNIGHT',
            CarrierServiceEnum::FEDEX_PRIORITY_OVERNIGHT               => 'PRIORITY_OVERNIGHT',
            CarrierServiceEnum::FEDEX_INTERNATIONAL_ECONOMY            => 'INTERNATIONAL_ECONOMY',
            CarrierServiceEnum::FEDEX_INTERNATIONAL_FIRST              => 'INTERNATIONAL_FIRST',
            CarrierServiceEnum::FEDEX_INTERNATIONAL_PRIORITY           => 'INTERNATIONAL_PRIORITY',
            CarrierServiceEnum::FEDEX_GROUND_HOME_DELIVERY             => 'GROUND_HOME_DELIVERY',
            CarrierServiceEnum::FEDEX_SMART_POST                       => 'SMART_POST',
        ],
        
        CarrierServiceEnum::CARRIER_DHL_GLOBAL => [
            CarrierServiceEnum::DHL_GLOBAL_BPM_EXPEDITED_DOMESTIC              => 'BPMExpeditedDomestic',
            CarrierServiceEnum::DHL_GLOBAL_BPM_GROUND_DOMESTIC                 => 'BPMGroundDomestic',
            CarrierServiceEnum::DHL_GLOBAL_FLATS_EXPEDITED_DOMESTIC            => 'FlatsExpeditedDomestic',
            CarrierServiceEnum::DHL_GLOBAL_FLATS_GROUND_DOMESTIC               => 'FlatsGroundDomestic',
            CarrierServiceEnum::DHL_GLOBAL_MEDIA_MAIL_GROUND_DOMESTIC          => 'MediaMailGroundDomestic',
            CarrierServiceEnum::DHL_GLOBAL_PARCEL_PLUS_EXPEDITED_DOMESTIC      => 'ParcelPlusExpeditedDomestic',
            CarrierServiceEnum::DHL_GLOBAL_PARCEL_PLUS_GROUND_DOMESTIC         => 'ParcelPlusGroundDomestic',
            CarrierServiceEnum::DHL_GLOBAL_PARCELS_EXPEDITED_DOMESTIC          => 'ParcelsExpeditedDomestic',
            CarrierServiceEnum::DHL_GLOBAL_PARCELS_GROUND_DOMESTIC             => 'ParcelsGroundDomestic',
            CarrierServiceEnum::DHL_GLOBAL_MARKETING_PARCEL_EXPEDITED_DOMESTIC => 'MarketingParcelExpeditedDomestic',
            CarrierServiceEnum::DHL_GLOBAL_MARKETING_PARCEL_GROUND_DOMESTIC    => 'MarketingParcelGroundDomestic',
        ],
        
        // DHL First Mile uses DHL Global, so we do the internal mapping to work for EasyPost but
        // allow us to identify which part of the DHL service we used.
        CarrierServiceEnum::CARRIER_DHL_FIRST_MILE => [
            CarrierServiceEnum::DHL_FIRST_MILE_PARCEL_PLUS_GROUND => 'ParcelPlusGroundDomestic',
            CarrierServiceEnum::DHL_FIRST_MILE_EXPEDITED_PARCEL   => 'ParcelsExpeditedDomestic',
            CarrierServiceEnum::DHL_FIRST_MILE_GROUND_PARCEL      => 'ParcelsGroundDomestic',
        ],
    ];
    
    /**
     * Retruns the EasyPost service string associated to our service int id.
     * 
     * @param int $carrierId Id from \Pley\Enum\Shipping\CarrierServiceEnum
     * @param int $serviceId Id from \Pley\Enum\Shipping\CarrierServiceEnum
     * @return string
     * @see \Pley\Enum\Shipping\CarrierServiceEnum
     */
    public function getEasyPostService($carrierId, $serviceId)
    {
        return static::$_carrierServiceMap[$carrierId][$serviceId];
    }
    
    /**
     * Retruns our service id associated to the EasyPost service string.
     * 
     * @param int    $carrierId Id from \Pley\Enum\Shipping\CarrierServiceEnum
     * @param string $serviceStr
     * @return int A value from \Pley\Enum\Shipping\CarrierServiceEnum
     * @see \Pley\Enum\Shipping\CarrierServiceEnum|null Null is returned if a new serive is returned
     *  by EasyPost to which we have no knowledge of
     */
    public function getService($carrierId, $serviceStr)
    {
        $carrierServiceMap = static::$_carrierServiceMap[$carrierId];
        $carrierServiceMap = array_flip($carrierServiceMap);
        
        if (isset($carrierServiceMap[$serviceStr])) {
            return $carrierServiceMap[$serviceStr];
        }
        
        return null;
    }
}
