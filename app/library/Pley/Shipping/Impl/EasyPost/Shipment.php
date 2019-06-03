<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace Pley\Shipping\Impl\EasyPost;

/**
 * The <kbd>Shipment</kbd> extends the base definition of a shipment so that it contains structure
 * and metadata needed for the EasyPost Shipment object.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping.Impl.EasyPost
 * @subpackage Shipping
 */
class Shipment extends \Pley\Shipping\Shipment\AbstractShipment
{
    /** @var \EasyPost\Shipment */
    private $_easyPostShipment;
    
    /**
     * Indicates if the internal EasyPost references has been initialized
     * @return boolean
     */
    public function isEasyPostShipmentSet()
    {
        return !empty($this->_easyPostShipment);
    }
    
    /**
     * Returns the internal EasyPost reference
     * <p>It initializes it if it has not been yet set</p>
     * @return \EasyPost\Shipment
     */
    public function getEasyPostShipment()
    {
        if (empty($this->_easyPostShipment)) {
            $this->_easyPostShipment = $this->_toEasyPostShipment();
            $this->_vendorShipId     = $this->_easyPostShipment->id;
        }
        
        return $this->_easyPostShipment;
    }

    /**
     * Maps the internal Shipment represetation object into an EasyPost compatible Shipment object.
     * @return \EasyPost\Shipment
     */
    private function _toEasyPostShipment()
    {
        $this->getParcel();
        
        $toAddressMap   = $this->_toEasyPostAddress($this->getToAddress());
        $fromAddressMap = $this->_toEasyPostAddress($this->getFromAddress());
        $parcelMap      = $this->_toEasyPostParcel($this->getParcel());
        $customsInfo    = $this->_toEasyPostCustomsInfo();
        
        $shipmentDetails = [
            'to_address'   => $toAddressMap,
            'from_address' => $fromAddressMap,
            'parcel'       => $parcelMap,
            'customs_info' => $customsInfo,
            
            // Nodes needed for UPS MailInnovations or setting the default label format
            'reference'    => $this->getShipmentMeta()->referenceNo,
            'options'      => [
                // Setting the const center which is needed in case of UPS Mail Innovations carrier
                'cost_center'  => $this->getShipmentMeta()->upsMICostCenter,
                
                // Setting the label format to ZPL instead of the implicit default PNG
                // ZPL is needed for better printing and compatibility with the Zebra Printers
                'label_format' => 'zpl',
            ],
        ];
        
        return \EasyPost\Shipment::create($shipmentDetails);
    }

    /**
     * Returns an array with the EasyPost compatible parcel.
     * @return array
     */
    private function _toEasyPostParcel(\Pley\Shipping\Shipment\ShipmentParcel $parcel)
    {
        $dimensionsIn = $parcel->getDimensionsIn();
        $parcelMap = [
            'length' => $this->_formatEasyPostNumber($dimensionsIn->getLength()),
            'width' => $this->_formatEasyPostNumber($dimensionsIn->getWidth()),
            'height' => $this->_formatEasyPostNumber($dimensionsIn->getHeight()),
            'weight' => $this->_formatEasyPostNumber($parcel->getWeightOz()),
        ];
        return $parcelMap;
    }

    /**
     * Returns an array with the EasyPost compatible address.
     * @return array
     */
    private function _toEasyPostAddress(\Pley\Shipping\Shipment\ShipmentAddress $address)
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

    /**
     * Returns an array with the EasyPost compatible customsInfo.
     * @return array
     */
    private function _toEasyPostCustomsInfo()
    {
        if ($this->isUsShipment()) {
            return []; //no customs info needed for domestic shipments
        }
        return [
            'contents_type' => 'merchandise',
            'contents_explanation' => 'Set of PleyBox toys',
            'eel_pfc' => 'NOEEI 30.37(a)',
            'non_delivery_option' => 'return',
            'restriction_type' => 'none',
            'restriction_comments' => null,
            'customs_items' => $this->_toEasyPostCustomsItems()
        ];
    }

    /**
     * Returns an array with the EasyPost compatible customsItems.
     * @return array
     */
    private function _toEasyPostCustomsItems()
    {
        return [
            [
                'description' => $this->_parcel->getItem()->getName(),
                'quantity' => 1,
                'value' => 25,
                'weight' => $this->_parcel->getWeightOz(),
                'hs_tariff_number' => '98100035', //harmonization code https://hts.usitc.gov/?query=9810.00.35
                'origin_country' => 'US',
            ]
        ];
    }

    /**
     * Helper method to format a number with the number of decimals needed by EasyPost services.
     * @param float|string $number
     * @return string
     */
    private function _formatEasyPostNumber($number)
    {
        return number_format($number, 1, '.', '');
    }
}
