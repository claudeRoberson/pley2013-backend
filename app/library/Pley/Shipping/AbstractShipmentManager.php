<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Shipping;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Db\AbstractDatabaseManager as DatabaseManager;
use Pley\Entity\User\UserAddress;
use Pley\Enum\ShirtSizeEnum;
use Pley\Shipping\ShippingZonePicker;
/** ♰
 * The <kbd>AbstractShipmentManager</kbd> declares the methods that will return Label and Tracking#
 * information for a given user, address and rented set to ship.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping
 * @subpackage Shipping
 */
abstract class AbstractShipmentManager
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\User\UserAddressDao */
    protected $_userAddressDao;
    /** @var \Pley\Dao\User\UserProfileDao */
    protected $_userProfileDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubscriptionDao;
    /** @var \Pley\Dao\Subscription\ItemDao */
    protected $_itemDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionShipmentDao */
    protected $_profileSubsShipDao;
    /** @var \Pley\Shipping\CarrierServicePicker */
    protected $_carrierServicePicker;
    /** @var \Pley\Shipping\CarrierRatePicker */
    protected $_carrierRatePicker;
    /** @var \Pley\Shipping\ShippingZonePicker */
    protected $_shippingZonePicker;
    
    // Additional variables containing information needed to process
    /** @var array List of allows US states*/
    protected $_allowedStateList;
    /**
     * The UPS Mail Innovations Cost Center identifier
     * @var string
     */
    protected $_upsMICostCenter;
    
    public function __construct(Config $config, DatabaseManager $dbManager, 
            \Pley\Dao\User\UserAddressDao $userAddressDao,
            \Pley\Dao\User\UserProfileDao $userProfileDao,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubscriptionDao,
            \Pley\Dao\Profile\ProfileSubscriptionShipmentDao $profileSubsShipDao,
            \Pley\Dao\Subscription\ItemDao $itemDao,
            \Pley\Shipping\CarrierServicePicker $carrierServicePicker,
            \Pley\Shipping\CarrierRatePicker $carrierRatePicker,
            \Pley\Shipping\ShippingZonePicker $shippingZonePicker)
    {
        $this->_config     = $config;
        $this->_dbManager  = $dbManager;

        $this->_userAddressDao         = $userAddressDao;
        $this->_userProfileDao         = $userProfileDao;
        $this->_profileSubscriptionDao = $profileSubscriptionDao;
        $this->_profileSubsShipDao     = $profileSubsShipDao;
        $this->_itemDao                = $itemDao;

        $this->_carrierServicePicker = $carrierServicePicker;
        $this->_carrierRatePicker    = $carrierRatePicker;
        $this->_shippingZonePicker   = $shippingZonePicker;

        $this->_allowedUsStates = $config['shipping.allowedUsStates'];
        $this->_allowedCountries = $config['shipping.allowedCountries'];

        // Getting the Warehouse address from the configuration file
        $this->_upsMICostCenter  = $config['shipping.upsMailInnovations.costCenter'];
    }
    
    /**
     * Validates if the Supplied Address is a Destination we support.
     * @param \Pley\Entity\User\UserAddress $userAddress
     * @throws \Exception
     */
    public function validateSupportedDestination(\Pley\Entity\User\UserAddress $userAddress)
    {
        // If the Supplied address is Outside the US, throw the respetive exception
        if (strtoupper($userAddress->getCountry()) != 'US') {
            if(!in_array($userAddress->getCountry(), $this->_allowedCountries)){
                throw new \Exception('Country Not Allowed');
            }
            return;
        }
        // If the Supplied Address or the Suggested address is in a State we don't support,
        // throw the respective exception
        if (!in_array($userAddress->getState(), $this->_allowedUsStates)) {
            throw new \Exception('State Not Allowed');
        }
    }
    
    /**
     * Returns the Tracking url for the given carrier and tracking#
     * <p>e.g. http://www.usps.com/track/1234abcd</p>
     * 
     * @param int    $carrierId
     * @param string $trackingNumber
     * @return string
     */
    public function getTrackingUrl($carrierId, $trackingNumber)
    {
        $trackingUrlMap      = $this->_config->get('shipping.trackingUrl');
        $trackingUrlTemplate = $trackingUrlMap[$carrierId];

        // The url template comes as a `printf()` compatible string (i.e. 'http://url/id=%s')
        return sprintf($trackingUrlTemplate, $trackingNumber);
    }
    
    /** ♰
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment $profileShipment
     * @return \Pley\Shipping\Shipment\AbstractShipment
     */
    public function createShipment(\Pley\Entity\Profile\ProfileSubscriptionShipment $profileShipment)
    {
        if (empty($profileShipment->getItemId())) {
            throw new \Exception('Cannot create shipment for shipment that hasn\'t been assigned an item');
        }
        
        $profile     = $this->_userProfileDao->find($profileShipment->getProfileId());
        $profileSubs = $this->_profileSubscriptionDao->find($profileShipment->getProfileSubscriptionId());
        $userAddress = $this->_userAddressDao->find($profileSubs->getUserAddressId());
        $item        = $this->_itemDao->find($profileShipment->getItemId());

        $parcel               = Shipment\ShipmentParcel::fromItem($item);
        $warehouseShipAddress = $this->_getWarehouseAddress();
        $userShipAddress      = Shipment\ShipmentAddress::fromUserAddress($userAddress);

        $shippedShipments = $this->_profileSubsShipDao->findProfileSubscriptionShipped($profileSubs->getId());
        $isFirstShipment = (count($shippedShipments) == 0) ? true : false;

        $metaInfoString = sprintf(' (%s/%s/%s)',
            ShirtSizeEnum::asString($profile->getTypeShirtSizeId()),
            $userAddress->getUserId(),
            ($isFirstShipment) ? 'F' : '');

        $userShipAddress->setName($profile->getFirstName() . $metaInfoString);
        
        $fromAddress = $warehouseShipAddress;
        $toAddress   = $userShipAddress;
        
        $shipmentMeta = new Shipment\ShipmentMeta();
        $shipmentMeta->upsMICostCenter = $this->_upsMICostCenter;
        $shipmentMeta->referenceNo     = $profileShipment->getId();
        
        $uspsShippingZoneId = $this->_getUspsShippingZoneId($fromAddress->getZipCode(), $toAddress->getZipCode());
        $shippingZone = $this->_shippingZonePicker->getShippingZoneByCountryCode($toAddress->getCountry(), $toAddress->getState());

        $shipment = $this->_createShipmentDelegate(
            $parcel,
            $fromAddress,
            $toAddress,
            $shipmentMeta,
            $shippingZone->getId(),
            $uspsShippingZoneId
        );
        
        return $shipment;
    }

    /**
     * Assigns USPS shipping zone and general shipping zone
     * @param UserAddress $userAddress
     * @return UserAddress
     */
    public function assignShippingZones(UserAddress $userAddress){
        $uspsShippingZoneId = $this->getUspsShippingZoneId($userAddress->getZipCode());
        $shippingZoneId = $this->getShippingZoneId($userAddress);
        $userAddress->setUspsShippingZoneId($uspsShippingZoneId);
        $userAddress->setShippingZoneId($shippingZoneId);
        return $userAddress;
    }
    
    /**
     * Returns the Zone for the supplied destination zip code from our Warehouse.
     * @param int $destinationZipCode
     * @return int
     */
    public function getUspsShippingZoneId($destinationZipCode)
    {
        $warehouseShipAddress = $this->_getWarehouseAddress();
        
        $zone = $this->_getUspsShippingZoneId($warehouseShipAddress->getZipCode(), $destinationZipCode);
        return $zone;
    }

    /**
     * Returns the shippingZoneId for the supplied destination zip code from our Warehouse.
     * @param UserAddress $userAddress
     * @return int | null
     */
    public function getShippingZoneId(UserAddress $userAddress)
    {
        $zone = $this->_shippingZonePicker->getShippingZoneByAddress($userAddress);
        return ($zone) ? $zone->getId() : null;
    }
    
    /** ♰
     * @param \Pley\Shipping\Shipment\AbstractShipment $shipment
     * @return \Pley\Shipping\Shipment\ShipmentLabel
     */
    public function purchaseLabel(Shipment\AbstractShipment $shipment)
    {
        $carrierService = $this->_carrierServicePicker->getCarrierService($shipment);
        
        // call delegate
        list ($vendorShipId, $trackingNo, $labelUrl, $vendorRate) = 
                $this->_purchaseLabelDelegate($shipment, $carrierService);
        
        $rate = $this->_carrierRatePicker->getRate($shipment, $carrierService);
        if (empty($rate)) {
            $rate = $vendorRate;
        }
        
        $shipmentRate  = Shipment\ShipmentRate::withCarrierService($carrierService, $rate);
        $shipmentLabel = new Shipment\ShipmentLabel($shipmentRate, $trackingNo, $labelUrl, $vendorShipId);

        return $shipmentLabel;
    }

    /**
     * Returns the Zone number between two zip codes.
     * @param string $sourceZipCode
     * @param string $destinationZipCode
     * @return int
     */
    protected function _getUspsShippingZoneId($sourceZipCode, $destinationZipCode)
    {
        $zoneChart = new \Shipping\ZoneChart\ZoneChart($sourceZipCode);
        return $zoneChart->getZoneFor($destinationZipCode);
    }

    // ---------------------------------------------------------------------------------------------
    // ABSTRACT METHODS ----------------------------------------------------------------------------
    
    /**
     * Verifies if the User Address is deliverable and obtain the sanitized closest version of it.
     * @param \Pley\Entity\User\UserAddress $userAddress
     * @return \Pley\Entity\User\UserAddress|null The closest sanitized Address or null if address
     *      could not be verified.
     */
    public abstract function verifyAddress(\Pley\Entity\User\UserAddress $userAddress);
    
    /** 
     * Delegate to create a <kbd>Shipment</kbd> object.
     * @param \Pley\Shipping\Shipment\ShipmentParcel  $parcel
     * @param \Pley\Shipping\Shipment\ShipmentAddress $fromAddress
     * @param \Pley\Shipping\Shipment\ShipmentAddress $toAddress
     * @param \Pley\Shipping\Shipment\ShipmentMeta    $shipmentMeta
     * @param int                                     $shippingZoneId
     * @param int                                     $uspsShippingZoneId
     * @return \Pley\Shipping\Shipment\AbstractShipment
     */
    protected abstract function _createShipmentDelegate(
        Shipment\ShipmentParcel $parcel,
        Shipment\ShipmentAddress $fromAddress,
        Shipment\ShipmentAddress $toAddress,
        Shipment\ShipmentMeta $shipmentMeta,
        $shippingZoneId,
        $uspsShippingZoneId);
    
    /**
     * @var \Pley\Shipping\Shipment\AbstractShipment $shipment
     * @var \Pley\Shipping\Carrier\CarrierService    $carrierService
     * @return array Sturcture for <kbd>list($vendorShipId, $trackingNo, $labelUrl, $vendorRate)</kbd>
     */
    protected abstract function _purchaseLabelDelegate(
            Shipment\AbstractShipment $shipment,
            Carrier\CarrierService $carrierService);
    
    // ---------------------------------------------------------------------------------------------
    // PRIVATE METHODS -----------------------------------------------------------------------------
    
    /** ♰
     * @return \Pley\Shipping\Shipment\ShipmentAddress
     */
    private function _getWarehouseAddress()
    {
        $warehouseAddressMap = $this->_config->get('shipping.address');
        $shipmentAddress     = new \Pley\Shipping\Shipment\ShipmentAddress(
            $warehouseAddressMap['name'],
            $warehouseAddressMap['address1'],
            $warehouseAddressMap['address2'],
            $warehouseAddressMap['city'],
            $warehouseAddressMap['state'],
            $warehouseAddressMap['country'],
            $warehouseAddressMap['zipCode']
        );
        
        return $shipmentAddress;
    }
    
}
