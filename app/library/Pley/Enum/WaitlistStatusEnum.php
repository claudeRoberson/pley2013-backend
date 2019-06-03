<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Enum;

/**
 * The <kbd>WaitlistStatusEnum</kbd> represents status in which a Subscription Plan is
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
class WaitlistStatusEnum extends AbstractEnum
{
    const ACTIVE = 1;
    const RELEASED = 2;
    const CANCELLED = 3;
    const PAYMENT_ATTEMPT_FAILED = 4;
}
