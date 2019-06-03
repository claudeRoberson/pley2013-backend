<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Enum;

/**
 * The <kbd>ShirtSizeEnum</kbd> Holds constants that represent a the different Shirt Sizes supproted.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
abstract class ShirtSizeEnum extends \Pley\Enum\AbstractEnum
{
    const XXS = 1;
    const XS  = 2;
    const S   = 3;
    const M   = 4;
    const L   = 5;
    const XL  = 6;
    const XXL = 7;

    /**
     * Maps and returns the string value for a given shirt size ID.
     * @param int $sizeId
     * @return int
     * @throws \UnexpectedValueException If the size id is not supported.
     */
    public static function asString($sizeId)
    {
        switch ($sizeId) {
            case self::XXS :
                return 'XXS';
            case self::XS :
                return 'XS';
            case self::S :
                return 'S';
            case self::M :
                return 'M';
            case self::L :
                return 'L';
            case self::XL :
                return 'XL';
            case self::XXL :
                return 'XXL';
            default :
                throw new \UnexpectedValueException("Shirt size ID `{$sizeId}` not supported");
        }
    }
}
