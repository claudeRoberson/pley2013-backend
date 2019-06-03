<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace Pley\Enum;

/**
 * The <kbd>ItemPartEnum</kbd> represents types of parts an Item can have so that based on the type
 * we can make some choices when creating a Shipment for a User.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
class ItemPartEnum extends AbstractEnum
{
    const GENERIC   = 1;
    const SHIRT     = 2;

    /**
     * Maps and returns the string value for a given item type ID.
     * @param int $typeId
     * @return int
     * @throws \UnexpectedValueException If the size id is not supported.
     */
    public static function asString($typeId)
    {
        switch ($typeId) {
            case self::GENERIC :
                return 'Generic';
            case self::SHIRT :
                return 'Shirt';
            default :
                throw new \UnexpectedValueException("Item part type ID `{$typeId}` not supported");
        }
    }
}
