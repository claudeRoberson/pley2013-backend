<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\Vendor\Payment;

/**
 * The <kbd>VendorPaymentSystemEntityTrait</kbd> is the base implementation for enty Entity class
 * that implements the <kbd>VendorPaymentEntityInterface</kbd> interface.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Vendor.Payment
 * @subpackage Entity
 */
trait VendorPaymentSystemEntityTrait
{
    /** @var int */
    protected $_vPaymentSystemId;
    
    /**
     * Returns the Vendor Payment System ID.
     * @return int
     */
    public function getVPaymentSystemId()
    {
        return $this->_vPaymentSystemId;
    }
}
