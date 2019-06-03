<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace Pley\Enum;

/**
 * The <kbd>SubscriptionCancelSourceEnum</kbd> represents sources for a Subscription Cancellation.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
class SubscriptionCancelSourceEnum extends AbstractEnum
{
    const USER = 1;
    const CUSTOMER_SERVICE = 2;
    const PAST_DUE = 3;
    const PAYMENT_SYSTEM = 4;
}
