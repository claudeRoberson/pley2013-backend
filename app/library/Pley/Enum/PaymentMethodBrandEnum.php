<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Enum;

/**
 * The <kbd>PaymentMethodBrandEnum</kbd> provides with IDs for common payment method brands.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
class PaymentMethodBrandEnum extends AbstractEnum
{
    const VISA             = 1;
    const MASTER_CARD      = 2;
    const AMERICAN_EXPRESS = 3;
    const JCB              = 4;
    const DISCOVER         = 5;
    const DINERS_CLUB      = 6;

    const PAYPAL           = 99;
}
