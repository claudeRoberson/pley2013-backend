<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Payment;

/**
 * The <kbd>PaymentAccount</kbd> represents the relationship between a User and a Payment Vendor account.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Payment
 * @subpackage Payment
 */
class PaymentAccount extends VendorData
{
    /** @var \Pley\Entity\User\User */
    protected $_user;
    
    public function __construct(\Pley\Entity\User\User $user)
    {
        $this->_user = $user;
    }

    /**
     * Returns the internal reference to the <kbd>User</kbd> object used to create this payment user.
     * @return \Pley\Entity\User\User
     */
    public function getUser()
    {
        return $this->_user;
    }

}
