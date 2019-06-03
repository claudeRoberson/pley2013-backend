<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Util\Converter;

/** ♰
 * The <kbd>DistanceConverter</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class DistanceConverter
{
    const INCH_TO_CENTIMETER = 2.54;
    
    /**
     * Converts from Centimeters to Inches.
     * @param float $centimeters
     * @param int   $precision [Optional]<br/>Default 2
     * @param int   $mode      [Optional]<br/>Default PHP_ROUND_HALF_UP <p>
     * @return float The converted value
     * @see PHP_ROUND_HALF_UP
     * @see PHP_ROUND_HALF_DOWN
     * @see PHP_ROUND_HALF_EVEN
     * @see PHP_ROUND_HALF_ODD
     */
    public static function centimetersToInches($centimeters, $precision = 2, $mode = PHP_ROUND_HALF_UP)
    {
        return round($centimeters/self::INCH_TO_CENTIMETER, $precision, $mode);
    }
    
    /**
     * Converts from Inches to centimeters.
     * @param float $inches
     * @param int   $precision [Optional]<br/>Default 2
     * @param int   $mode      [Optional]<br/>Default PHP_ROUND_HALF_UP <p>
     * @return float The converted value
     * @see PHP_ROUND_HALF_UP
     * @see PHP_ROUND_HALF_DOWN
     * @see PHP_ROUND_HALF_EVEN
     * @see PHP_ROUND_HALF_ODD
     */
    public static function inchesToCentimeters($inches, $precision = 2, $mode = PHP_ROUND_HALF_UP)
    {
        return round($inches * self::INCH_TO_CENTIMETER, $precision, $mode);
    }
}
