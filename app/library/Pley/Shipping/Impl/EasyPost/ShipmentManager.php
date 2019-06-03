<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Shipping\Impl\EasyPost;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Db\AbstractDatabaseManager as DatabaseManager;

/**
 * The <kbd>ShipmentManager</kbd> class implements the ShipmentManagerInterface for integration
 * with the 3rd party EasyPost service.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping.Impl.EasyPost
 * @subpackage Shipping
 */
class ShipmentManager extends \Pley\Shipping\AbstractShipmentManager
{
    /** @var \Pley\Shipping\Impl\EasyPost\CarrierMapper */
    protected $_carrierMapper;
    /** @var \Pley\Shipping\Impl\EasyPost\CarrierServiceMapper */
    protected $_carrierServiceMapper;

    public function __construct(Config $config, DatabaseManager $dbManager,
            \Pley\Dao\User\UserAddressDao $userAddressDao,
            \Pley\Dao\User\UserProfileDao $userProfileDao,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubscriptionDao,
            \Pley\Dao\Profile\ProfileSubscriptionShipmentDao $profileSubsShipDao,
            \Pley\Dao\Subscription\ItemDao $itemDao,
            \Pley\Shipping\CarrierServicePicker $carrierServicePicker,
            \Pley\Shipping\CarrierRatePicker $carrierRatePicker,
            \Pley\Shipping\ShippingZonePicker $shippingZonePicker,
            // Impl specific
            \Pley\Shipping\Impl\EasyPost\CarrierMapper $carrierMapper,
            \Pley\Shipping\Impl\EasyPost\CarrierServiceMapper $carrierServiceMapper
        )
    {
        parent::__construct(
            $config, $dbManager, $userAddressDao, $userProfileDao, $profileSubscriptionDao, $profileSubsShipDao,
            $itemDao, $carrierServicePicker, $carrierRatePicker, $shippingZonePicker
        );

        $this->_carrierMapper        = $carrierMapper;
        $this->_carrierServiceMapper = $carrierServiceMapper;

        // Setting the API Key for transactions made in this manager
        \EasyPost\EasyPost::setApiKey($config['shipping.apiKey']);
    }

    /**
     * Invokes 3rd Party system to verify that a User Address is deliverable and obtain the
     * sanitized version of it.
     * @param \Pley\Entity\User\UserAddress $userAddress
     * @return \Pley\Entity\User\UserAddress|null The closest sanitized Address or null if address
     *      could not be verified.
     */
    public function verifyAddress(\Pley\Entity\User\UserAddress $userAddress)
    {
        $this->validateSupportedDestination($userAddress);

        if(!$userAddress->isUsAddress()){
            return $userAddress; // skip verification for international addresses
        }

        try {
            //optionally you can create and verify in one step
            $address = \EasyPost\Address::create_and_verify([
                'street1' => $userAddress->getStreet1(),
                'street2' => $userAddress->getStreet2(),
                'city'    => $userAddress->getCity(),
                'state'   => $userAddress->getState(),
                'zip'     => $userAddress->getZipCode(),
                'country' => $userAddress->getCountry(),
            ]);
            // Validation returns fully sanitized US ZipCode `xxxxx-yyyy`
            $zipCode = $address['zip'];

            $suggestedAddress = \Pley\Entity\User\UserAddress::forVerification(
                strtoupper($address['street1']),
                isset($address['street2'])? strtoupper($address['street2']) : null,
                $userAddress->getPhone(),
                strtoupper($address['city']),
                strtoupper($address['state']),
                strtoupper($userAddress->getCountry()),
                $zipCode
            );
            $suggestedAddress->setUserId($userAddress->getUserId());

            $this->validateSupportedDestination($suggestedAddress);

            if($this->_isSameAddress($userAddress, $suggestedAddress)){
                $suggestedAddress->setIsValid(true);
            }else{
                $suggestedAddress->setIsValid(false);
            }

            return $suggestedAddress;
        } catch (\EasyPost\Error $e) {
            if ($this->_isEasyPostBadAddressError($e)) {
                $userAddress->setIsValid(false);
                return $userAddress;
            } else {
                $this->_handleEasyPostError($e);
            }
        }
    }

    protected function _isSameAddress(\Pley\Entity\User\UserAddress $userAddress, \Pley\Entity\User\UserAddress $suggestedAddress)
    {
        if(strtoupper($userAddress->getCity()) !== strtoupper($suggestedAddress->getCity())){
            return false;
        }
        if(strtoupper($userAddress->getStreet1()) !== strtoupper($suggestedAddress->getStreet1())){
            return false;
        }
        if(strtoupper($userAddress->getState()) !== strtoupper($suggestedAddress->getState())){
            return false;
        }
        if(strtoupper($userAddress->getZipCode()) !== strtoupper($suggestedAddress->getZipCode())){
            return false;
        }
        return true;
    }

    /**
     * Create a <kbd>Shipment</kbd> object.
     * @param \Pley\Shipping\Shipment\ShipmentParcel  $parcel
     * @param \Pley\Shipping\Shipment\ShipmentAddress $fromAddress
     * @param \Pley\Shipping\Shipment\ShipmentAddress $toAddress
     * @param \Pley\Shipping\Shipment\ShipmentMeta    $shipmentMeta
     * @param int                                     $shippingZoneId
     * @param int                                     $uspsShippingZoneId
     * @return \Pley\Shipping\Impl\EasyPost\Shipment
     */
    protected function _createShipmentDelegate(
        \Pley\Shipping\Shipment\ShipmentParcel $parcel,
        \Pley\Shipping\Shipment\ShipmentAddress $fromAddress,
        \Pley\Shipping\Shipment\ShipmentAddress $toAddress,
        \Pley\Shipping\Shipment\ShipmentMeta $shipmentMeta,
        $shippingZoneId,
        $uspsShippingZoneId)
    {
        $shipment = new \Pley\Shipping\Impl\EasyPost\Shipment(
            $parcel, $fromAddress, $toAddress, $shipmentMeta, $shippingZoneId, $uspsShippingZoneId
        );
        return $shipment;
    }

    /**
     * @var \Pley\Shipping\Shipment\AbstractShipment $shipment
     * @var \Pley\Shipping\Carrier\CarrierService    $carrierService
     * @return array Sturcture for <kbd>list($vendorShipId, $trackingNo, $labelUrl, $vendorRate)</kbd>
     */
    protected function _purchaseLabelDelegate(
            \Pley\Shipping\Shipment\AbstractShipment $shipment,
            \Pley\Shipping\Carrier\CarrierService $carrierService)
    {
        if (!$shipment instanceof \Pley\Shipping\Impl\EasyPost\Shipment) {
            throw new \Exception('Expected instance of \Pley\Shipping\Impl\EasyPost\Shipment.');
        }

        $epCarrier = $this->_carrierMapper->getEasyPostCarrier($carrierService->getCarrier());
        $epService = $this->_carrierServiceMapper->getEasyPostService(
            $carrierService->getCarrier(), $carrierService->getService()
        );

        $epShipment = $shipment->getEasyPostShipment();

        // EasyPost way of updating objects in place and making everything accessible as either
        // an array or an object, it makes it hard to figure out what are the actual fields
        // since these are added at runtime
        // To help us, here are some of the fields known through debugging
        // $shipment->tracking_code = string
        // $shipment->postage_label = \EasyPost\PostageLabel
        // $shipment->postage_label->label_url = string

        // Currently the Default format will be ZPL (usually it is a PNG, but we changed it upon
        // creating the \EasyPost\Shipment object on $this->_getEasyPostShipment(), this saves us
        // from having to make two network calls to retrieve the label we want.
        // 
        // If we need other formats, the regular way would be to call
        //        $shipment->label(['file_format' => 'pdf']);
        //        $shipment->label(['file_format' => 'epl2']);
        //        $shipment->label(['file_format' => 'zpl']);
        // And the respective values would be
        //   Default : $shipment->postage_label->label_url
        //        $shipment->postage_label->label_pdf_url;
        //        $shipment->postage_label->label_epl2_url;
        //        $shipment->postage_label->label_zpl_url;

        $vendorShipId = $trackingNo = $labelUrl = $vendorRate = null;
        $isEPError    = false;

        try {
            /* @var $easyPostRate \EasyPost\Rate */
            $epRate = $epShipment->lowest_rate([$epCarrier], [$epService]);
            $epShipment->buy($epRate);

            $vendorShipId = $epShipment->id;
            $trackingNo   = $epShipment->tracking_code;
            $labelUrl     = $epShipment->postage_label->label_url;
            $vendorRate   = $epRate->rate;

        } catch(\EasyPost\Error $e) {
            // Since EasyPost errors are not robust, we need to quite guess from the error message
            // to understand what the actual error is.
            // Usually it is going to be a userAddress error
            $isEPError = true;

            if ($this->_isEasyPostBadAddressError($e)) {
                throw new \Pley\Exception\Shipping\ShipmentLabelPurchaseException($shipment, $carrierService);

            } else {
                $this->_handleEasyPostError($e);
            }
        } finally {
            if ($isEPError) {
                \Log::error("EasyPost : Failed Shipment Label purchase [$epShipment->id]");
            }
        }

        return [$vendorShipId, $trackingNo, $labelUrl, $vendorRate];
    }


    /**
     * Returns if the EasyPost exception represents a Bad Address (validation or shipping).
     * @param \EasyPost\Error $exception
     * @return boolean
     */
    private function _isEasyPostBadAddressError(\EasyPost\Error $exception)
    {
        $errMessage = strtolower($exception->getMessage());

        // We have ironed out basically all errors that could be related to shipping a set, the only
        // ones left are those which cannot be shipped because the User Address is incorrect
        // Unfortunately, the Errors returned by EasyPost are not very consistent or sometimes refer
        // to a different error.
        // When an error with a message including "Address is too ambiguous" is returned, it means
        //   the address is missing something (like an address number), but it does not check for
        //   other errors like if the ZIP code matches the given State.
        // When an error with a message including "No rates found" is returned, after many tests, it
        //   means that EasyPost did not capture an address error, so it delegated to the Carrier
        //   which in turn detected the bad address and thus can't give a rate, as such, basically
        //   we couldn't get the rate because of the bad address but the error is misleading.
        $knownAddressErrList = [
            'multiple addresses',
            'more information is needed',
            'address not found',
            'invalid state code',
            'invalid city',
            'address is too ambiguous',
            'the postal code',
            'missing or invalid',
            'unable to validate ship to address',
            'is not a valid state for the specified shipment',
            'missing/invalid shipto postalcode',
            'unable to validate ship from address',
            'no rates found',
            'unable to verify',
        ];

        foreach ($knownAddressErrList as $errSubstring) {
            if (strpos($errMessage, $errSubstring) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper method to handle an EasyPost exception that we are not aware of.
     * @param \EasyPost\Error $exception
     * @throws \Pley\Exception\Shipping\LabelServiceUnavailableException If the EasyPost exception
     *      referces to it being unavailable
     * @throws \EasyPost\Error If it is an error we were not aware of
     */
    private function _handleEasyPostError(\EasyPost\Error $exception)
    {
        $errMessage = $exception->getMessage();

        // There are a few other errors that are not 100% related to addresses but more to server
        // errors, like if their service is down, so we have to handle those ones too.
        if (strpos($errMessage, 'Service Unavailable') !== false) {
            throw new \Pley\Exception\Shipping\LabelServiceUnavailableException();
        }

        // If we could not identify the EasyPost exception, then just let it cascade
        throw $exception;
    }

}
