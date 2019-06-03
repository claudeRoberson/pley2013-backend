<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Exception\Shipping;

use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;
use \Pley\Exception\ExceptionCode;
use \Pley\Shipping\Shipment\AbstractShipment;
use \Pley\Shipping\Carrier\CarrierService;

/**
 * The <kbd>ShipmentLabelPurchaseException</kbd> class represents the case where a non-supported
 * shipping zone is supplied.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Shipping
 * @subpackage Exception
 */
class ShipmentLabelPurchaseException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(AbstractShipment $shipment, CarrierService $carrierServie, \Exception $previous = null)
    {
        $message = "Failure purchasing a shipment label. ";
        $message .= $this->_toJson($shipment, $carrierServie);
        
        parent::__construct($message, ExceptionCode::SHIPPING_LABEL_PURCHASE, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_CONFLICT;
    }
    
    private function _toJson(AbstractShipment $shipment, CarrierService $carrierService)
    {
        $dataMap = [
            'fromAddress' => $this->_toAddressMap($shipment->getFromAddress()),
            'toAddress' => $this->_toAddressMap($shipment->getToAddress()),
            'parcel' => $this->_toParcelMap($shipment->getParcel()),
            'rate' => [
                'carrier' => $carrierService->getCarrier(),
                'service' => $carrierService->getService(),
            ],
            'usps_shipping_zone' => $shipment->getUspsShippingZoneId(),

        ];
        
        $jsonData = json_encode($dataMap);
        return $jsonData;
    }
    
    private function _toParcelMap(\Pley\Shipping\Shipment\ShipmentParcel $parcel)
    {
        $dimensionsIn = $parcel->getDimensionsIn();
        $parcelMap = [
            'length' => $dimensionsIn->getLength(),
            'width'  => $dimensionsIn->getWidth(),
            'height' => $dimensionsIn->getHeight(),
            'weight' => $parcel->getWeightOz(),
        ];
        return $parcelMap;
    }
    
    private function _toAddressMap(\Pley\Shipping\Shipment\ShipmentAddress $address)
    {
        return [
            'name'    => $address->getName(),
            'street1' => $address->getStreet1(),
            'street2' => $address->getStreet2(),
            'city'    => $address->getCity(),
            'state'   => $address->getState(),
            'zip'     => $address->getZipCode(),
            'country' => $address->getCountry(),
        ];
    }
}
