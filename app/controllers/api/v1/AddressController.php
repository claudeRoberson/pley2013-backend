<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace api\v1;

class AddressController extends \api\v1\BaseController
{
    /** @var \Pley\Shipping\AbstractShipmentManager */
    protected $_shipmentMgr;

    public function __construct(\Pley\Shipping\Impl\EasyPost\ShipmentManager $shipmentMgr)
    {
        $this->_shipmentMgr     = $shipmentMgr;
    }

    // POST /address/verify
    public function verify()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();
        $validationRules = [
            'street1' => 'required',
            'street2' => 'sometimes',
            'city'    => 'required|alpha_dot_space',
            'state'   => 'required|alpha',
            'country' => 'required|alpha_space',
            'zip'     => 'required'
        ];
        \ValidationHelper::validate($json, $validationRules);

        $suggestedUserAddress = $this->_getSanitizedAddress($json);

        return \Response::json([
            'valid'   => $suggestedUserAddress->isValid(),
            'street1' => $suggestedUserAddress->getStreet1(),
            'street2' => $suggestedUserAddress->getStreet2(),
            'city'    => $suggestedUserAddress->getCity(),
            'state'   => $suggestedUserAddress->getState(),
            'country' => $suggestedUserAddress->getCountry(),
            'zip'     => $suggestedUserAddress->getZipCode()
        ]);
    }

    /**
     * Validates and Sanitizes a supplied address.
     * @param array $addressMap
     * @return \Pley\Entity\User\UserAddress
     */
    private function _getSanitizedAddress($addressMap)
    {
        $inputUserAddress = \Pley\Entity\User\UserAddress::forVerification(
            $addressMap['street1'],
            $addressMap['street2'],
            !isset($addressMap['phone']) ? null : $addressMap['phone'],
            $addressMap['city'],
            $addressMap['state'],
            $addressMap['country'],
            $addressMap['zip']
        );

        $this->_shipmentMgr->validateSupportedDestination($inputUserAddress);
        $suggestedUserAddress = $this->_shipmentMgr->verifyAddress($inputUserAddress);
        $this->_shipmentMgr->validateSupportedDestination($suggestedUserAddress);

        return $suggestedUserAddress;
    }
}
