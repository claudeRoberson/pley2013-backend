<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Enum;

/**
 * The <kbd>SubscriptionStatusEnum</kbd> represents status in which a Subscription Plan is
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
class SubscriptionStatusEnum extends AbstractEnum
{
    const ACTIVE    = 1;
    const PAST_DUE  = 2;
    const CANCELLED = 3;
    const GIFT      = 4;
    const PAUSED    = 5;
    const UNPAID    = 6;
    const FINISHED  = 7;
    const STOPPED = 8;

    /**
     * Maps and returns the string value for a given item type ID.
     * @param int $statusId
     * @return int
     * @throws \UnexpectedValueException If the size id is not supported.
     */
    public static function asString($statusId)
    {
        switch ($statusId) {
            case self::ACTIVE :
                return 'ACTIVE';
            case self::PAST_DUE :
                return 'PAST_DUE';
            case self::CANCELLED :
                return 'CANCELLED';
            case self::GIFT :
                return 'GIFT';
            case self::PAUSED :
                return 'PAUSED';
            case self::UNPAID :
                return 'UNPAID';
            case self::FINISHED :
                return 'FINISHED';
            case self::STOPPED :
                return 'STOPPED';
            default :
                throw new \UnexpectedValueException("Status ID `{$statusId}` not supported");
        }
    }
}
