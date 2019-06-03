<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Util\Time;

/**
 * The <kbd>DateTime</kbd> Util class provides methods that wrap native functions with support for
 * null values or other methods with common functionality.
 *
 * @author     Alejandro Salazar (alejandros@pley.com)
 * @version    1.0
 * @package    Pley.Util
 * @subpackage Util
 */
abstract class DateTime
{
    const MONDAY    = 1;
    const TUESDAY   = 2;
    const WEDNESDAY = 3;
    const THURSDAY  = 4;
    const FRIDAY    = 5;
    const SATURDAY  = 6;
    const SUNDAY    = 7;
    
    const DAY_TO_SECONDS = 86400;
    
    /**
     * Wrapper method around native function <kbd>strtotime()</kbd> to handle cases where the
     * variable value is <kbd>null</kbd>
     *
     * @param string $datetime
     *
     * @return int
     */
    public static function strToTime($datetime)
    {
        if (isset($datetime) && $datetime !== '0000-00-00 00:00:00') {
            return strtotime($datetime);
        }

        return null;
    }

    /**
     * Wrapper method around native function <kbd>date()</kbd> to handle cases where the
     * variable value is <kbd>null</kbd>
     *
     * @param int    $timestamp
     * @param string $format [Optional]<br/>Default = 'Y-m-d H:i:s'
     *
     * @return string Date in 'yyyy-mm-dd hh:mm:ss' format or the one supplied
     */
    public static function date($timestamp, $format = 'Y-m-d H:i:s')
    {
        if (isset($timestamp)) {
            return date($format, $timestamp);
        }

        return null;
    }

    /**
     * Converts the number of days supplied to its equivalent in seconds.
     * @param $days
     * @return int
     */
    public static function toSeconds($days)
    {
        return (int)round($days * self::DAY_TO_SECONDS);
    }

    /**
     * Converts the number of seconds into its equivalent in days.
     * <p>Note: the days will be rounded</p>
     * @param $secs
     * @return int
     */
    public static function toDays($secs)
    {
        return (int)round($secs / self::DAY_TO_SECONDS);
    }

    /**
     * @param $int
     *
     * @return bool
     */
    public static function isTimestamp($int)
    {
        return (is_numeric($int) && (int)$int == $int);
    }

    /**
     * Returns whether the timestamp matches Saturday or Sunday weekday.
     * @param int|null $timestamp [Optional]<br/>If not supplied, the current time will be used.
     * @return boolean
     */
    public static function isWeekend($timestamp = null)
    {
        return self::isWeekDay(self::SATURDAY, $timestamp) ||
               self::isWeekDay(self::SUNDAY, $timestamp);
    }
    
    /**
     * Returns whether the timestamp matches Saturday weekday.
     * @param int|null $timestamp [Optional]<br/>If not supplied, the current time will be used.
     * @return boolean
     */
    public static function isSaturday($timestamp = null)
    {
        return self::isWeekDay(self::SATURDAY, $timestamp);
    }
    
    /**
     * Returns whether the timestamp matches Sunday weekday.
     * @param int|null $timestamp [Optional]<br/>If not supplied, the current time will be used.
     * @return boolean
     */
    public static function isSunday($timestamp = null)
    {
        return self::isWeekDay(self::SUNDAY, $timestamp);
    }
    
    /**
     * Returns whether the timestamp matches the requested week day.
     * @param int      $weekDay   A value from 1 (Monday) to 7 (Sunday)
     * @param int|null $timestamp [Optional]<br/>If not supplied, the current time will be used.
     * @return boolean
     */
    public static function isWeekDay($weekDay, $timestamp = null)
    {
        if (!isset($timestamp)) {
            $timestamp = time();
        }
        
        return date('N', $timestamp) == $weekDay;
    }
    
    /**
     * Returns the number of weeks in the supplied year
     * @param int $year
     * @return int
     */
    public static function weeksInYear($year)
    {
        return date('W', mktime(0, 0, 0, 12, 31, $year));
    }
    
    /**
     * Returns an object with the date components of a timestamp.
     * @param int $timestamp (Optional)<br/>Default current date timestamp
     * @return \Pley\Util\DateParts
     */
    public static function dateParts($timestamp = null)
    {
        return new DateParts($timestamp);
    }
    
    //----------------------------------------------------------------------------------------------
    // PERIOD RELATED FUNCTIONS --------------------------------------------------------------------
    
    /**
     * Returns the period value for the supplied period unit.
     * <p>By default it is calculated for the present day, unless the optional paramter is supplied
     * for a specific date timestamp.</p>
     * @param int $periodUnit A constant from <kbd>PeriodUnitEnum</kbd>
     * @param int $timestamp  (Optional)<br/>Default current date timestamp
     * @return int Month or Week # of the year.
     * @throws \UnexpectedValueException If an invalid period unit is supplied.
     */
    public static function getPeriod($periodUnit, $timestamp = null)
    {
        $dateParts = static::dateParts($timestamp);
        
        switch ($periodUnit) {
            case \Pley\Enum\PeriodUnitEnum::MONTH:
                return $dateParts->getMonth();
            case \Pley\Enum\PeriodUnitEnum::WEEK:
                return $dateParts->getWeekOfYear();
            default:
                throw new \UnexpectedValueException("Period Unit `{$periodUnit}` not supported");
        }
    }
    
    /**
     * Returns the day of period for the supplied period unit.
     * <p>By default it is calculated for the present day, unless the optional paramter is supplied
     * for a specific date timestamp.</p>
     * @param int $periodUnit A constant from <kbd>PeriodUnitEnum</kbd>
     * @param int $timestamp  (Optional)<br/>Default current date timestamp
     * @return int Day of Month or day of Week.
     * @throws \UnexpectedValueException If an invalid period unit is supplied.
     */
    public static function getDayOfPeriod($periodUnit, $timestamp = null)
    {
        $dateParts = static::dateParts($timestamp);
        
        switch ($periodUnit) {
            case \Pley\Enum\PeriodUnitEnum::MONTH:
                return $dateParts->getDayOfMonth();
            case \Pley\Enum\PeriodUnitEnum::WEEK:
                return $dateParts->getDayOfWeek();
            default:
                throw new \UnexpectedValueException("Period Unit `{$periodUnit}` not supported");
        }
    }
    
    /**
     * Returns the last period for a given period unit on a given year.
     * @param int $periodUnit
     * @param int $year
     * @return int
     * @throws \UnexpectedValueException If an invalid period unit is supplied.
     */
    public static function lastPeriodInUnit($periodUnit, $year)
    {
        switch ($periodUnit) {
            // A year always has 12 months
            case \Pley\Enum\PeriodUnitEnum::MONTH:
                return 12;
            // A year can have either 52 or 53 weeks
            case \Pley\Enum\PeriodUnitEnum::WEEK:
                return static::weeksInYear($year);
            default:
                throw new \UnexpectedValueException("Period Unit `{$periodUnit}` not supported");
        }
    }
    
    /**
     * Returns the next period for the supplied period data.
     * <p>Makes checks for the special case of PeriodUnit=Month and the day is beyond the the last
     * day allowed for the supplied period month, and returns the right adjusted value.</p>
     * @param int $periodUnit A value from <kbd>\Pley\Enum\PeriodUnitEnum</kbd>
     * @param int $period     Month or Week of the year, depending on the period unit.
     * @param int $day        Day of the month or week, depending on period unit.
     * @param int $year
     * @param int $periodStep (Optional)<br/>Default 1
     * @return array A list containing [$period, $day, $year] of the next period.
     */
    public static function nextPeriod($periodUnit, $period, $day, $year, $periodStep = 1)
    {
        $lastPeriodInUnit = static::lastPeriodInUnit($periodUnit, $year);
        
        // Move the Period one unit forward
        $nextPeriodPeriod = $period + $periodStep;
        $nextPeriodDay    = $day;
        $nextPeriodYear   = $year;
        
        // if the next Period is in a new Year, we need to move the year forward and go back to the 
        // initial period in this new year, then update
        //   i.e.  if PeriodUnit = Month, last period = 12 (last month in year)
        //   i.e.  if PeriodUnit = Week, last period = 52 or 53 (last week in a given year)
        // NOTE: This assumes that there will not be periods steps that could cause spanning multiple
        // years, say, PeriodStep = every 16 Months or 65 Weeks, as that would require more checks
        // that are not needed unless it is truly decided we need that kind of period span.
        if ($nextPeriodPeriod > $lastPeriodInUnit) {//if ($nextPeriod > $lastPeriodInUnit) {
            $nextPeriodPeriod -= $lastPeriodInUnit;
            $nextPeriodYear    = $nextPeriodYear + 1;
        }
        
        // Sanitization for day only applies to MONTH period units and if the day is greater than
        // 28, as not all months have 29, 30 or 31 days
        if ($periodUnit == \Pley\Enum\PeriodUnitEnum::MONTH && $nextPeriodDay > 28) {
            $verifyDate   = mktime(0, 0, 0, $nextPeriodPeriod, $nextPeriodDay, $nextPeriodYear);
            $verifyPeriod = (int)date('n', $verifyDate); // Get numeric representation of Month with no leading 0
            $verifyYear   = (int)date('Y', $verifyDate); // Get the verify year as we could've moved in year

            // If the period moved, that means the pointer period does not span all the way to the
            // supplied day, and thus, we need to calculate the last day for the pointer period
            if ($verifyPeriod != $nextPeriodPeriod) {
                // Creating a day for the first day of the spanned period, so that we can substract 1 day
                // and obtain the last day of the period we want
                $nextPeriodFirstDate = mktime(0, 0, 0, $verifyPeriod, 1, $verifyYear);
                $fixDate             = strtotime('-1 day', $nextPeriodFirstDate);

                // Get numeric representation of the last day of the period we want
                $nextPeriodDay = (int)date('j', $fixDate);
            }
        }
        
        return [$nextPeriodPeriod, $nextPeriodDay, $nextPeriodYear];
    }
}

/**
 * The <kbd>DateParts</kbd> is a support Class to get the components of a givent date
 * @author Alejandro Salazar (alejandros@pley.com)
 */
class DateParts
{
    private $_year;
    private $_month;
    private $_weekOfYear;
    private $_dayOfYear;
    private $_dayOfMonth;
    private $_dayOfWeek;
    private $_hours;
    private $_minutes;
    private $_seconds;

    public function __construct($timestamp = null)
    {
        if (empty($timestamp)) { $timestamp = time(); }
        $baseParts = getdate($timestamp);
        
        $this->_year       = $baseParts['year'];
        $this->_month      = $baseParts['mon'];
        $this->_weekOfYear = (int)date('W', $timestamp);
        $this->_dayOfYear  = $baseParts['yday'];
        $this->_dayOfMonth = $baseParts['mday'];
        $this->_dayOfWeek  = $baseParts['wday'];
        $this->_hours      = $baseParts['hours'];
        $this->_minutes    = $baseParts['minutes'];
        $this->_seconds    = $baseParts['seconds'];
    }
    
    /** @return int */
    public function getYear()
    {
        return $this->_year;
    }

    /** @return int */
    public function getMonth()
    {
        return $this->_month;
    }

    /** @return int */
    public function getWeekOfYear()
    {
        return $this->_weekOfYear;
    }

    /** @return int */
    public function getDayOfYear()
    {
        return $this->_dayOfYear;
    }

    /** @return int */
    public function getDayOfMonth()
    {
        return $this->_dayOfMonth;
    }

    /** @return int */
    public function getDayOfWeek()
    {
        return $this->_dayOfWeek;
    }

    /** @return int */
    public function getHours()
    {
        return $this->_hours;
    }

    /** @return int */
    public function getMinutes()
    {
        return $this->_minutes;
    }

    /** @return int */
    public function getSeconds()
    {
        return $this->_seconds;
    }
}
