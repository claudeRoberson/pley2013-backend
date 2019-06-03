<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\Vendor;

/**
 * The <kbd>VendorPaymentEntityInterface</kbd> defines the minimum method required to identify
 * this entity as one that interact with a Payment Vendor system.
 * <p>Please take a look at the Traits under <kbd>\Pley\Entity\Vendor\Payment</kbd> for additional
 * implementations of other known items (i.e. System, Account, PaymentMethod, etc).</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Vendor
 * @subpackage Entity
 */
interface VendorPaymentEntityInterface
{
    /**
     * Returns the Vendor Payment System ID.
     * @return int
     */
    public function getVPaymentSystemId();
}
