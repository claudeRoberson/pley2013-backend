<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Util;

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
        if (isset($datetime)) {
            $time = strtotime($datetime);
            
            // If the date is something like "0000-00-00 00:00:00", the parsing will yield a negative
            // number, so since it isn't a true date, just reset to null
            if ($time < 0) {
                $time = null;
            }
            
            return $time;
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
     * @param $dateString
     * @param string $format
     * @return int|null
     */
    public static function paypalDateToTime($dateString, $format = 'Y-m-d\TH:i:s.u\Z')
    {
        $date = \DateTime::createFromFormat($format, $dateString);
        if($date){
            return $date->getTimestamp();
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
}
