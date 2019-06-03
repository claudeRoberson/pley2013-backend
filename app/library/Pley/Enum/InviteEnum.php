<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Enum;

/**
 * The <kbd>InviteEnum</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package 
 * @subpackage
 */
final class InviteEnum
{
    const STATUS_EXISTING_MEMBER = 1;
    const STATUS_PENDING         = 2;
    const STATUS_FREE_TRIAL      = 3;
    const STATUS_JOINED          = 4;
    const STATUS_CANCELLED       = 5;

    /**
     * Maps and returns the string value for a given invite status ID.
     * @param int $statusId
     * @return int
     * @throws \UnexpectedValueException If the size id is not supported.
     */
    public static function asString($statusId)
    {
        switch ($statusId) {
            case self::STATUS_EXISTING_MEMBER :
                return 'Existing Member';
            case self::STATUS_PENDING :
                return 'Pending';
            case self::STATUS_FREE_TRIAL :
                return 'Free Trial';
            case self::STATUS_JOINED :
                return 'Joined';
            case self::STATUS_CANCELLED :
                return 'Cancelled';
            default :
                throw new \UnexpectedValueException("Invite status ID `{$statusId}` not supported");
        }
    }
}
