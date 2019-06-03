<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace api\v1\Shipping;

use Pley\Shipping\ShippingZonePicker;
use Pley\Repository\User\UserAddressRepository;

/**
 * The <kbd>RateController</kbd>
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class RateController extends \api\v1\BaseController
{
    /** @var \Pley\Shipping\ShippingZonePicker */

    protected $_shippingZonePicker;

    /** @var \Pley\Repository\User\UserAddressRepository */

    protected $_userAddressRepository;

    public function __construct(
        ShippingZonePicker $storeRatePicker,
        UserAddressRepository $userAddressRepository
    )
    {
        $this->_shippingZonePicker = $storeRatePicker;
        $this->_userAddressRepository = $userAddressRepository;
    }

    // GET /shipping/address/{addressId}/rate
    public function getShippingRate($addressId)
    {
        \RequestHelper::checkGetRequest();
        $address = $this->_userAddressRepository->find($addressId);
        \ValidationHelper::entityExist($address, \Pley\Entity\Shipping\Zone::class);
        $response = [
            'zone' => []
        ];
        $zone = $this->_shippingZonePicker->getShippingZoneByAddress($address);
        $response['zone'] = $zone->toArray();

        return \Response::json($response);
    }
}
