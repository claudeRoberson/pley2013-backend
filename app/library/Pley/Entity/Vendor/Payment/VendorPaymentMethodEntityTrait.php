<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\Vendor\Payment;

/**
 * The <kbd>VendorPaymentMethodEntityTrait</kbd> provides methods to set up the Vendor Payment Plan ID.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Vendor.Payment
 * @subpackage Entity
 */
trait VendorPaymentMethodEntityTrait
{
    /** @var string */
    protected $_vPaymentMethodId;
    
    /**
     * Returns the Vendor Payment Method ID.
     * @return string
     */
    public function getVPaymentMethodId()
    {
        return $this->_vPaymentMethodId;
    }
    
    /**
     * Initializes the Vendor Payment information to be used for this User
     * @param string $vSystemId
     * @param string $vMethodId
     * @throws \Pley\Exception\Entity\ImmutableAttributeException If the Account ID is already SET
     * @throws \Pley\Exception\Payment\PaymentSystemMismatchingException If the current object
     *      has already a reference to the vendor payment system and the supplied Vendor
     *      Payment System ID does not match it.
     */
    public function setVPaymentMethod($vSystemId, $vMethodId)
    {
        // Check that we are not trying to override the Transaction ID
        if (isset($this->_vPaymentMethodId)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_vPaymentMethodId');
        }
        
        // Checking if the Vendor Payment System ID is already set (perhaps by some other method)
        // and if so, then that this assignment is not for a different vendor.
        if (isset($this->_vPaymentSystemId) && $this->_vPaymentSystemId != $vSystemId) {
            throw new \Pley\Exception\Payment\PaymentSystemMismatchingException(
                $this->_vPaymentSystemId, $vSystemId
            );
        }
        
        // Now that we know we are clear to assign values (even if updating the system ID as we
        // checked above that it is the same one)
        $this->_vPaymentSystemId = $vSystemId;
        $this->_vPaymentMethodId = $vMethodId;
    }
}
