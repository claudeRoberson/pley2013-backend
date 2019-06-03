<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Shipping;

use \Pley\Config\ConfigInterface as Config;

/** ♰
 * The <kbd>CarrierRatePicker</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping
 * @subpackage Shipping
 */
class CarrierRatePicker
{
    private static $DEFAULT_SERVICE = 'default';

    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    
    public function __construct(Config $config)
    {
        $this->_config = $config;
    }
    
    /** ♰
     * @param \Pley\Shipping\Shipment\AbstractShipment $shipment
     * @param \Pley\Shipping\Carrier\CarrierService    $carrierService
     * @return float|null
     */
    public function getRate(Shipment\AbstractShipment $shipment, Carrier\CarrierService $carrierService)
    {
        $ratesMap = $this->_config->get('shippingRates');
        
        if (!isset($ratesMap[$carrierService->getCarrier()])) { return null; }
        
        $carrierRateMap = $ratesMap[$carrierService->getCarrier()];
        $serviceRateMap = null;
        if (isset($carrierRateMap[$carrierService->getService()])) {
            $serviceRateMap = $carrierRateMap[$carrierService->getService()];
            
        } else if (isset($carrierRateMap[static::$DEFAULT_SERVICE])) {
            $serviceRateMap = $carrierRateMap[static::$DEFAULT_SERVICE];
        }
        
        if (empty($serviceRateMap)) { return null; }
        
        $ruleValue = \Pley\Util\ConfigRuleCompare::getRule($serviceRateMap, $shipment->getParcel()->getWeightOz());
        
        if (is_array($ruleValue)) {
            return $ruleValue[$shipment->getUspsShippingZoneId()];
        }
        
        return $ruleValue;
    }
}
