<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace Pley\Enum;

/**
 * The <kbd>PaymentSystemEnum</kbd> represents IDs for all Vendor Payment systems we interact with.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
class PaymentSystemEnum extends AbstractEnum
{
    const STRIPE = 1;

    const PAYPAL = 2;

    /**
     * Returns the string value for a given num representation.
     * @param string $paymentSystemId
     * @return int
     * @throws \UnexpectedValueException If the string period unit is not supported.
     */
    public static function toString($paymentSystemId)
    {
        switch ($paymentSystemId) {
            case self::STRIPE:
                return 'Stripe';
            case self::PAYPAL  :
                return 'PayPal';
            default :
                throw new \UnexpectedValueException("Payment System Id `{$paymentSystemId}` not supported");
        }
    }
}
