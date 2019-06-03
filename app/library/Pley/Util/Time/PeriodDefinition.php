<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Util\Time;

/**
 * The <kbd>PeriodDefinition</kbd> Represents a Period date which could be day of a Month, or 
 * day of a Week.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Util.Time
 * @subpackage Util
 */
class PeriodDefinition
{
    /** @var int */
    private $_periodUnit;
    /** @var int */
    private $_period;
    /** @var int */
    private $_year;
    /** @var int */
    private $_day;
    /** @var int */
    private $_timestamp;
    /** @var int */
    private $_index;
    
    /**
     * Returns a new <kbd>PeriodDefinition</kbd> for the current system time given a PeriodUnit
     * @param int $periodUnit A value from <kbd>\Pley\Enum\PeriodUnitEnum</kbd>
     * @return \Pley\Util\Time\PeriodDefinition
     */
    public static function withNow($periodUnit)
    {
        return static::withTimestamp($periodUnit, time());
    }
    
    /**
     * Returns a new <kbd>PeriodDefinition</kbd> for the supplied timestamp given a PeriodUnit
     * @param int $periodUnit A value from <kbd>\Pley\Enum\PeriodUnitEnum</kbd>
     * @param int $timestamp
     * @return \Pley\Util\Time\PeriodDefinition
     */
    public static function withTimestamp($periodUnit, $timestamp)
    {
        $period = DateTime::getPeriod($periodUnit, $timestamp);
        $day    = DateTime::getDayOfPeriod($periodUnit, $timestamp);
        $year   = DateTime::dateParts($timestamp)->getYear();
        return new static($periodUnit, $period, $day, $year);
    }
    
    public static function toTimestamp($periodUnit, $period, $day, $year)
    {
        $timestamp = null;
        
        if ($periodUnit == \Pley\Enum\PeriodUnitEnum::MONTH) {
            $strMonth = str_pad($period, 2, '0', STR_PAD_LEFT);
            $strDay   = str_pad($day, 2, '0', STR_PAD_LEFT);
            
            $timestamp = strtotime($year . $strMonth . $strDay);
        } else if ($periodUnit == \Pley\Enum\PeriodUnitEnum::WEEK) {
            $strWeek = str_pad($period, 2, '0', STR_PAD_LEFT);
            
            $timestamp = strtotime($year . 'W' . $strWeek . $day);
        }
        
        return $timestamp;
    }
    
    public function __construct($periodUnit, $period, $deadlineDay, $year)
    {
        $this->_periodUnit = $periodUnit;
        $this->_period     = $period;
        $this->_day        = $deadlineDay;
        $this->_year       = $year;
        $this->_index      = 0;
    }

    /** @return int A value from <kbd>\Pley\Enum\PeriodUnitEnum</kbd> */
    public function getPeriodUnit()
    {
        return $this->_periodUnit;
    }
    
    /** @return int */
    public function getPeriod()
    {
        return $this->_period;
    }

    /** @return int */
    public function getDay()
    {
        return $this->_day;
    }

    /** @return int */
    public function getYear()
    {
        return $this->_year;
    }
    
    /** @return int */
    public function getTimestamp()
    {
        if (empty($this->_timestamp)) {
            $this->_timestamp = static::toTimestamp($this->_periodUnit, $this->_period, $this->_day, $this->_year);
        }
        
        return $this->_timestamp;
    }
    
    /**
     * Moves this definition one period forward.
     * @return \Pley\Util\Time\PeriodDefinition
     */
    public function moveNext($periodStep = 1)
    {
        list($this->_period, $this->_day, $this->_year) = \Pley\Util\Time\DateTime::nextPeriod(
            $this->_periodUnit, $this->_period, $this->_day, $this->_year, $periodStep
        );
        $this->_index++;
        
        // Since we moved the period, we need to reset the timestamp cache
        $this->_timestamp = null;
    }
    
    /** 
     * Return the associated index (used when in relationship with a separate structure like a Subscription)
     * <p>Default NULL unless set.</p>
     * @return int
     */
    public function getIndex()
    {
        return $this->_index;
    }

    /**
     * Sets an associated index (for relationship with separate structure like a Subscription)
     * @param int $index
     */
    public function setIndex($index)
    {
        $this->_index = $index;
    }

    /**
     * Checks if this instance is equal to the supplied one.
     * @param \Pley\Util\Time\PeriodDefinition $compare
     * @return boolean
     */
    public function equals(PeriodDefinition $compare)
    {
        return $this->_periodUnit == $compare->_periodUnit
                && $this->_period == $compare->_period
                && $this->_day == $compare->_day
                && $this->_year == $compare->_year;
    }
    
    public function __toString()
    {
        $timestamp = $this->getTimestamp();
        $date = date('Y-m-d', $timestamp);
        return sprintf("[%d] %s : %d", $this->_index, $date, $timestamp);
    }
}
