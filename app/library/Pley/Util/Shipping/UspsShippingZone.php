<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Util\Shipping;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Util\Time\DateTime;

class UspsShippingZone
{
    const SHIPPING_TIME_REGULAR = 'regular';
    const SHIPPING_TIME_EXPRESS = 'express';
    
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    
    public function __construct(Config $config)
    {
        $this->_config = $config;
    }

    /**
     * @param string $zipCode
     * @param string $carrier [Optional]<br/>Default 'usps'
     *
     * @return null
     */
    public function getUspsShippingZoneId($zipCode, $carrier = 'usps')
    {
        $zonesByZipMap = $this->_config->get('shippingZones.zipMap');

        // Zones are determined by the first 3 digits of the zip code
        $zipPart = substr($zipCode, 0, 3);

        // If there is an entry in the map for the zipcode part, then retrieve the zone by the
        // supplied carrier
        if (isset($zonesByZipMap[$zipPart])) {
            return $zonesByZipMap[$zipPart][$carrier];
        }

        return null;
    }

    /**
     * Get an array with 
     * @param int    $zone
     * @param string $speed (Optional)<br/>Default <kbd>null</kbd> which implies `regular`<br/>
     *      String to identify the speed to check for times, see the shipping time constants.
     * @return array
     * @see ::SHIPPING_TIME_REGULAR
     * @see ::SHIPPING_TIME_EXPRESS
     */
    public function getShippingTime($zone, $speed=null)
    {
        $today = strtotime(date('Y-m-d'));
        
        $oneDaySecs = DateTime::DAY_TO_SECONDS;
        
        $shipTimeMap = $this->_config->get('shippingZones.shipTime');
        
        switch ($speed) {
            case self::SHIPPING_TIME_EXPRESS: break;
            default:
                $speed = self::SHIPPING_TIME_REGULAR;
                break;
        }
        
        $minDeliveryRangeSecs = DateTime::toSeconds($shipTimeMap[$speed][$zone]['min']);
        $maxDeliveryRangeSecs = DateTime::toSeconds($shipTimeMap[$speed][$zone]['max']);
        
        // Default shipping date will be consider the following available day after the current day
        $shipDate = $this->_getAvailableDay($today + $oneDaySecs);
        
        // Minimum delivery date is the the next available day after the min range days
        $minDeliveryDate = $this->_getAvailableDay($shipDate + $minDeliveryRangeSecs);
        
        // To calculate the maximum delivery date in an available day, we cannot use the max range
        // based on the shipping date for the minimum date could have shifted based on Sunday or
        // Holiday, so we need to calculate the day differential and calculate the next available
        // day from the minimum delivery date
        $deliveryRangeDiffSecs = $maxDeliveryRangeSecs - $minDeliveryRangeSecs;
        
        $maxDeliveryDate = $this->_getAvailableDay($minDeliveryDate + $deliveryRangeDiffSecs);
        
        // Formatting the response
        /** @todo We should change this to be the timestamps eventually so that Frontend can display
         *        however it needs it, that way it is more flexible on display */
        $monthDayFormat = 'F j';
        return [
            'ship' => DateTime::date($shipDate, $monthDayFormat),
            'min'  => DateTime::date($minDeliveryDate, $monthDayFormat),
            'max'  => DateTime::date($maxDeliveryDate, $monthDayFormat),
            'shipTime' => $shipTimeMap[$speed][$zone]
        ];
    }

    /**
     * Returns whether the supplied timestamp falls on a Holiday
     * <p>Note: This only accounts for US holidays.</p>
     * @param int|null $timestamp [Optional]<br/>If not supplied, the current time will be used.
     * @return bool
     */
    public function isHoliday($timestamp = null)
    {
        if (!isset($timestamp)) {
            $timestamp = time();
        }
        
        $holidays = $this->_config->get('holidays');
        
        $year     = date('Y', $timestamp);
        $monthDay = date('F j', $timestamp);

        return !empty($holidays[$year][$monthDay]);
    }

    /**
     * Returns the most available day for the supplied timestamp.
     * <p>If the supplied is the most available day, itself is returned, otherwise the following
     * available day will be returned.</p>
     * @param int $timestamp
     * @return int
     */
    private function _getAvailableDay($timestamp)
    {
        $oneDaySecs = DateTime::DAY_TO_SECONDS;
        
        $availableDay = $timestamp;
        
        while(DateTime::isSunday($availableDay) || $this->isHoliday($availableDay)) {
            $availableDay += $oneDaySecs;
        }
        
        return $availableDay;
    }
    
}