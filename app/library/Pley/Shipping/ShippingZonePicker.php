<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Shipping;

use Pley\Entity\User\UserAddress;
use Pley\Enum\Shipping\ShippingZoneEnum;
use Pley\Exception\Shipping\ZoneNotFoundException;
use Pley\Repository\Shipping\ZoneRepository;
use \Pley\Config\ConfigInterface as Config;


/**
 * Class description goes here
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ShippingZonePicker
{
    /**
     * @var ZoneRepository
     */
    protected $_zoneRepository;

    /**
     * StoreRatePicker constructor.
     * @param ZoneRepository $ZoneRepository
     */
    public function __construct(ZoneRepository $zoneRepository)
    {
        $this->_zoneRepository = $zoneRepository;
    }

    /**
     * Returns a shipping rate based on the given UserAddress entity
     * @param UserAddress $address
     * @return \Pley\Entity\Shipping\Zone | null
     * @throws \Exception
     */
    public function getShippingZoneByAddress(UserAddress $address)
    {
        $appliedZone = null;
        $countryZones = $this->_zoneRepository->findByCountry($address->getCountry());
        if (!$countryZones) {
            throw new ZoneNotFoundException($address->getCountry());
        }
        foreach ($countryZones as $zone) {
            if ($zone->getZip() === $address->getZipCode()) {
                return $zone;
            }
            if(empty($address->getState())){
                continue;
            }
            if (strpos($zone->getState(), $address->getState()) !== false) {
                return $zone;
            }
        }
        if (!$appliedZone) {
            foreach ($countryZones as $zone) {
                if ($zone->getState() == '*' && $zone->getZip() == '*') {
                    return $zone;
                }
            }
        }
        return $appliedZone;
    }

    public function getShippingZoneByCountryCode($code = null, $stateCode = null)
    {
        $appliedZone = null;
        $countryZones = $this->_zoneRepository->findByCountry($code);
        if (!$countryZones) {
            throw new ZoneNotFoundException($code);
        }
        foreach ($countryZones as $zone) {
            if(empty($stateCode)){
                continue;
            }
            if (strpos($zone->getState(), $stateCode) !== false) {
                return $zone;
            }
        }
        if (!$appliedZone) {
            foreach ($countryZones as $zone) {
                if ($zone->getState() == '*' && $zone->getZip() == '*') {
                    return $zone;
                }
            }
        }
        return $appliedZone;
    }
}