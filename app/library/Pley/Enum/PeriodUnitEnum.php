<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace Pley\Enum;

/**
 * The <kbd>PeriodUnitEnum</kbd> represents how is time measured for different events, like
 * subscriptions, payment plans, etc.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
final class PeriodUnitEnum extends AbstractEnum
{
    const MONTH = 1;
    const WEEK = 2;

    /**
     * Returns the Constant value for a given String representation.
     * @param string $periodUnitStr
     * @return int
     * @throws \UnexpectedValueException If the string period unit is not supported.
     */
    public static function fromString($periodUnitStr)
    {
        switch (strtolower($periodUnitStr)) {
            case 'month' :
                return \Pley\Enum\PeriodUnitEnum::MONTH;
            case 'week'  :
                return \Pley\Enum\PeriodUnitEnum::WEEK;
            default :
                throw new \UnexpectedValueException("Period Unit `{$periodUnitStr}` not supported");
        }
    }

    /**
     * Returns the sting representation for a given num .
     * @param string $periodUnit
     * @return int
     * @throws \UnexpectedValueException If the string period unit is not supported.
     */
    public static function toString($periodUnit)
    {
        switch ($periodUnit) {
            case \Pley\Enum\PeriodUnitEnum::MONTH:
                return 'month';
            case \Pley\Enum\PeriodUnitEnum::WEEK  :
                return 'week';
            default :
                throw new \UnexpectedValueException("Period Unit `{$periodUnit}` not supported");
        }
    }
}
