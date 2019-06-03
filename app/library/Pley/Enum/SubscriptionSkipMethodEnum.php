<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Enum;

use Pley\Entity\Subscription\Subscription;

/**
 * The <kbd>SubscriptionSkipMethodEnum</kbd> represents IDs of skip box strategies
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
class SubscriptionSkipMethodEnum extends AbstractEnum
{
    /**
     * Should SKIP a box and push all remaining boxes forwards by one as well as shipment periods.
     * Customer will never receive the skipped box, but they will still receive the amount of boxes
     * they had programmed.
     */
    const SKIP = 1;
    /**
     * Should SHIFT a box within a current shipping schedule by one period.
     * Customer will receive it during the next shipping period.
     */
    const SHIFT = 2;

    /**
     * Defines a skip method for a given subscription
     * @param Subscription $subscription
     * @return int
     */
    public static function getSubscriptionSkipMethod(Subscription $subscription)
    {
        if ($subscription->getItemPullType() === SubscriptionItemPullEnum::BY_SCHEDULE) {
            return self::SKIP;
        }
        
        // SubscriptionItemPullEnum::IN_ORDER
        return self::SHIFT;
    }
}
