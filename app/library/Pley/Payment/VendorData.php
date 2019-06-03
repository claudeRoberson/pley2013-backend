<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Payment;

/**
 * The <kbd>VendorData</kbd> is a generic object that represents a third party element to be nested
 * within any of the Payment objects.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Payment
 * @subpackage Payment
 */
class VendorData
{
    /** @var string */
    private $_vSystemId;
    /** @var string */
    private $_vId;
    /** @var mixed */
    private $_vMetadata;
    
    /**
     * The Vendor System ID
     * @return int
     */
    public function getVendorSystemId()
    {
        return $this->_vSystemId;
    }
        
    /**
     * The Vendor ID for the object represented in this structure.
     * @return string
     */
    public function getVendorId()
    {
        return $this->_vId;
    }

    /**
     * The Vendor's original metadata.
     * <p>
     * @return string
     */
    public function getVendorMetadata()
    {
        return $this->_vMetadata;
    }

    /**
     * Updates the reference to the original vendor data.
     * @param mixed $metadata
     */
    public function setVendorMetadata($metadata)
    {
        $this->_vMetadata = $metadata;
    }
    
    /**
     * Initializes the Vendor data into this object.
     * @param int    $vSystemId
     * @param string $vId
     * @param mixed  $metadata
     */
    public function initVendor($vSystemId, $vId, $metadata)
    {
        $this->_vSystemId = $vSystemId;
        $this->_vId       = $vId;
        $this->_vMetadata = $this->setVendorMetadata($metadata);
    }
}
