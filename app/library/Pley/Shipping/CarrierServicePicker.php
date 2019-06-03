<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Shipping;

use \Pley\Config\ConfigInterface as Config;

/** ♰
 * The <kbd>CarrierServicePicker</kbd>
 * 
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping
 * @subpackage Shipping
 */
class CarrierServicePicker
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
     * @return \Pley\Shipping\Carrier\CarrierService
     */
    public function getCarrierService(Shipment\AbstractShipment $shipment)
    {
        if($this->_config->get('shipping.carrierOverride.enabled') === true){
            $serviceId = \Pley\Enum\Shipping\CarrierServiceEnum::getServiceIdByCode($this->_config->get('shipping.carrierOverride.service'));
            $carrierId = \Pley\Enum\Shipping\CarrierServiceEnum::getCarrierForService($serviceId);
            return new \Pley\Shipping\Carrier\CarrierService($carrierId, $serviceId);
        }
        $rulesZoneMap = $this->_getRulesZoneMap($shipment);
        $serviceRuleList = $this->_getServiceRules($rulesZoneMap, $shipment);
        $carrierService  = $this->_getService($serviceRuleList, $shipment);
        
        return $carrierService;
    }

    protected function _getRulesZoneMap(Shipment\AbstractShipment $shipment){
        return $this->_config->get('shipping.rulesMap.shippingZones');
    }
    
    /** ♰
     * @param array $rulesZoneMap
     * @param \Pley\Shipping\Shipment\AbstractShipment $shipment
     * @return array
     */
    protected function _getServiceRules($rulesZoneMap, Shipment\AbstractShipment $shipment)
    {
        return $rulesZoneMap[$shipment->getShippingZoneId()]['serviceMap'];
    }
    
    /** ♰
     * @param array $serviceRuleList
     * @param \Pley\Shipping\Shipment\AbstractShipment $shipment
     * @return \Pley\Shipping\Carrier\CarrierService
     * @throws \Exception
     */
    protected function _getService($serviceRuleList, Shipment\AbstractShipment $shipment)
    {

        $uspsShippingZoneId     = $shipment->getUspsShippingZoneId();
        $weightOz = $shipment->getParcel()->getWeightOz();
        
        $serviceDef = \Pley\Util\ConfigRuleCompare::getRule($serviceRuleList, $weightOz);
        if (empty($serviceDef)) { return null; }
        
        $serviceName = null;
        $serviceId   = null;
        if (is_string($serviceDef)) {
            $serviceName = $serviceDef;
            $serviceId   = @constant(\Pley\Enum\Shipping\CarrierServiceEnum::class . '::' . $serviceName);

        } else {
            if (isset($serviceDef[$uspsShippingZoneId])) {
                $serviceName = $serviceDef[$uspsShippingZoneId];
                $serviceId   = @constant(\Pley\Enum\Shipping\CarrierServiceEnum::class . '::' . $serviceName);
            } else {
                $serviceName = $serviceDef[self::$DEFAULT_SERVICE];
                $serviceId   = @constant(\Pley\Enum\Shipping\CarrierServiceEnum::class . '::' . $serviceName);
            }
        }

        // If Service ID is null, it means that such constant is was not defined for the configured Service Name
        if (empty($serviceId)) {
            throw new \Exception('Invalid Shipping Service ' . $serviceName);
        }
        
        $carrierId = \Pley\Enum\Shipping\CarrierServiceEnum::getCarrierForService($serviceId);
        return new \Pley\Shipping\Carrier\CarrierService($carrierId, $serviceId);
    }
}
