<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\Vendor\Payment;

/**
 * The <kbd>VendorPaymentAccountEntityTrait</kbd> provides methods to set up the Vendor Payment
 * Account ID.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Vendor.Payment
 * @subpackage Entity
 */
trait VendorPaymentAccountEntityTrait
{
    /** @var string */
    protected $_vPaymentAccountId;
    
    /**
     * Returns the Vendor Payment Account ID.
     * @return string
     */
    public function getVPaymentAccountId()
    {
        return $this->_vPaymentAccountId;
    }
    
    /**
     * Initializes the Vendor Payment information to be used for this User
     * @param string $vSystemId
     * @param string $vAccountId
     */
    public function setVPaymentAccount($vSystemId, $vAccountId)
    {
        $this->_vPaymentSystemId  = $vSystemId;
        $this->_vPaymentAccountId = $vAccountId;
    }
}
