<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Util\Converter;

/**
 * The <kbd>WeightConverter</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package 
 * @subpackage
 */
abstract class WeightConverter
{
    const GRAM_TO_OUNCES = 0.035274;
    const OUNCE_TO_GRAMS = 28.3495;
    const POUND_TO_OUNCES = 16;
    
    /**
     * Converts from Grams to Ounces.
     * @param float $grams
     * @param int   $precision [Optional]<br/>Default 2
     * @param int   $mode      [Optional]<br/>Default PHP_ROUND_HALF_UP <p>
     * @return float The converted value
     * @see PHP_ROUND_HALF_UP
     * @see PHP_ROUND_HALF_DOWN
     * @see PHP_ROUND_HALF_EVEN
     * @see PHP_ROUND_HALF_ODD
     */
    public static function gramsToOunces($grams, $precision = 2, $mode = PHP_ROUND_HALF_UP)
    {
        return round($grams * self::GRAM_TO_OUNCES, $precision, $mode);
    }
    
    /**
     * Converts from Grams to Ounces.
     * @param float $ounces
     * @param int   $precision [Optional]<br/>Default 2
     * @param int   $mode      [Optional]<br/>Default PHP_ROUND_HALF_UP <p>
     * @return float The converted value
     * @see PHP_ROUND_HALF_UP
     * @see PHP_ROUND_HALF_DOWN
     * @see PHP_ROUND_HALF_EVEN
     * @see PHP_ROUND_HALF_ODD
     */
    public static function ouncesToGrams($ounces, $precision = 2, $mode = PHP_ROUND_HALF_UP)
    {
        return round($ounces * self::OUNCE_TO_GRAMS, $precision, $mode);
    }

    /**
     * Converts from Ounces to Pounds.
     * @param float $ounces
     * @param int   $precision [Optional]<br/>Default 2
     * @param int   $mode      [Optional]<br/>Default PHP_ROUND_HALF_UP <p>
     * @return float The converted value
     * @see PHP_ROUND_HALF_UP
     * @see PHP_ROUND_HALF_DOWN
     * @see PHP_ROUND_HALF_EVEN
     * @see PHP_ROUND_HALF_ODD
     */
    public static function ouncesToPounds($ounces, $precision = 2, $mode = PHP_ROUND_HALF_UP)
    {
        return round($ounces / self::POUND_TO_OUNCES, $precision, $mode);
    }
}
