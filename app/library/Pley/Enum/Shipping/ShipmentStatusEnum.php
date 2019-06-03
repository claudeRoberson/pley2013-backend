<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Enum\Shipping;

/**
 * The <kbd>ShipmentStatusEnum</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum.Shipping
 * @subpackage Enum
 */
class ShipmentStatusEnum extends \Pley\Enum\AbstractEnum
{
    const PREPROCESSING = 1;
    const PROCESSED     = 2;
    const IN_TRANSIT    = 3;
    const DELIVERED     = 4;
    const CANCELLED     = 5;
    const REVIEW        = 6;

    /**
     * Maps and returns the string value for a given item type ID.
     * @param int $statusId
     * @return int
     * @throws \UnexpectedValueException If the size id is not supported.
     */
    public static function asString($statusId)
    {
        switch ($statusId) {
            case self::PREPROCESSING :
                return 'Preparing';
            case self::PROCESSED :
                return 'Left Warehouse';
            case self::IN_TRANSIT :
                return 'In Transit';
            case self::DELIVERED :
                return 'Delivered';
            case self::CANCELLED :
                return 'Cancelled';
            case self::REVIEW :
                return 'In Review';
            default :
                throw new \UnexpectedValueException("Status ID `{$statusId}` not supported");
        }
    }
}
