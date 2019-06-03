<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Enum;

/**
 * The <kbd>SubscriptionItemPullEnum</kbd> represents how Items are pulled for new Subscriptions.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
class SubscriptionItemPullEnum extends AbstractEnum
{
    const IN_ORDER    = 1;
    const BY_SCHEDULE = 2;
}
