<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Util\Time;

/**
 * The <kbd>PeriodIterator</kbd> class allows to iterate through our dynamic Periods given a start
 * period.
 * <p>It will iterate till the first Period that has not yet expired, that means
 * <ul>
 *   <li>The period which deadline is the same as the current day<li>
 *   <li>Or the first period which deadline is after the current day</li>
 * </ul></p>
 * <p>A foreach will stop after any of the conditions above are met, however, this iterator can still
 * be used to calculate future dates, by calling the <kbd>next()</kbd>, <kbd>key()</kbd> and 
 * <kbd>current()</kbd> directly.</p>
 * <p>To avoid memory leaks, this iterator updates the values within the period referenced by the
 * current iteration, so to obtain a new copy of the current iteration period, you can call the
 * <kbd>cloneCurrent()</kbd> method which will return a copy of the current period.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Util.Time
 * @subpackage Util
 */
class PeriodIterator implements \Iterator
{
    /** @var int A value from <kbd>\Pley\Enum\PeriodUnitEnum</kbd> */
    protected $_periodUnit;
    /** @var int */
    protected $_periodStep;
    
    // Series start point --------------------------------------------------------------------------
    /** @var \Pley\Util\Time\PeriodDefinition */
    protected $_periodDefFirstDeadline;
    /**
     * Need to store this value for the edge case of end of month days so that we can correclty
     * represent the last day of a month as we iterate without overlapping into the beginning of
     * following months.
     * This is only relevant if PeriodUnit = MONTH
     * @var int
     */
    protected $_periodDayDeadline;
    
    // Limit to stop the series iteration ----------------------------------------------------------
    /** @var \Pley\Util\Time\PeriodDefinition */
    protected $_periodDefEnd;
    
    // Variables that hold the Current iteration values --------------------------------------------
    /** @var int */
    protected $_currentIndex;
    /** @var \Pley\Util\Time\PeriodDefinition */
    protected $_currentPeriodDef;
    
    // Variables used to Rewind of move to the Next element ----------------------------------------
    /** @var boolean */
    protected $_isCurrentPeriodFound = false;
    /** @var boolean */
    protected $_isPointerMoved = false;
    /** @var int */
    protected $_pointerIndex;
    /** @var int */
    protected $_pointerYear;
    /** @var int */
    protected $_pointerPeriod;
    /** @var int */
    protected $_pointerDay;
    
    /**
     * Creates a new <kbd>PeriodIterator</kbd> given a Subscription definition object
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @return \Pley\Util\Time\PeriodIterator
     */
    public static function fromSubscription(\Pley\Entity\Subscription\Subscription $subscription)
    {
        $periodUnit  = $subscription->getPeriodUnit();
        $periodStep  = $subscription->getPeriod();
        $periodStart = $subscription->getStartPeriod();
        $yearStart   = $subscription->getStartYear();
        $deadlineDay = $subscription->getDeadlineDay();

        $periodDefStart = new PeriodDefinition($periodUnit, $periodStart, $deadlineDay, $yearStart);
        return new static($periodUnit, $periodStep, $periodDefStart);
    }
    
    public function __construct($periodUnit, $periodStep, PeriodDefinition $periodDayStart)
    {
        $this->_periodUnit = $periodUnit;
        $this->_periodStep = $periodStep;

        $this->_periodDefFirstDeadline = $periodDayStart;
        $this->_periodDayDeadline      = $periodDayStart->getDay();
        
        $this->_periodDefEnd = PeriodDefinition::withNow($periodUnit);
        
        $this->_currentPeriodDef = clone $periodDayStart;
        
        $this->rewind();
    }

    /**
     * Moves the iterator to the specified Index
     * @param int $index
     */
    public function forwardToIndex($index)
    {
        if ($index < 0) {
            $index = 0;
        }
        
        $this->rewind();
        for ($i = 0; $i < $index; $i++, $this->next()) {
            $this->current(); // Needed so that the internal pointer actually moves.
        }
        
        // This is to move the pointer one last time after the cycle exited so it correctly points
        // to the desired index as per the last call to `next()`
        $this->current();
    }
    
    /**
     * Returns the current period pair in the iteration.
     * <p>If the pointer was moved by a call to <kbd>next()</kbd>, the current pointer will be updated
     * to return the next element.</p>
     * @return \Pley\Util\Time\PeriodDefinition
     */
    public function current()
    {
        $this->_updateIterationPeriodUnit();
        return $this->_currentPeriodDef;
    }
    
    /**
     * Creates a copy of the current <kbd>PeriodDay</kbd> object.
     * <p>This is needed if the user wants to keep a snapshot of the current PeriodDefinition without
     * it being affected by the iteration process.</p>
     * <p>i.e.
     * <pre>foreach ($iterator => $periodPair) {}
     * $lastValidPair = $iterator->cloneCurrent();
     * $iterator->current();
     * // $periodPair now points to the first invalid pair after the iterator finished
     * // $lastValidPair still points to the last valid pair before the foreach exited
     * $newClonePair = $iterator->cloneCurrent();
     * // Now both $periodPair is the same as $newClonePair, since the call to current() updated the internal value
     * </pre></p>
     * @return \Pley\Util\Time\PeriodDefinition
     */
    public function cloneCurrent()
    {
        $this->_updateIterationPeriodUnit();
        $clone = clone $this->_currentPeriodDef;
        return $clone;
    }

    public function key()
    {
        $this->_updateIterationPeriodUnit();
        return $this->_currentIndex;
    }

    public function next()
    {
        $this->_isPointerMoved = true;
        
        list ($periodNext, $dayNext, $yearNext) = \Pley\Util\Time\DateTime::nextPeriod(
            $this->_periodUnit, 
            $this->_currentPeriodDef->getPeriod(), 
            $this->_periodDayDeadline, 
            $this->_currentPeriodDef->getYear(), 
            $this->_periodStep
        );
        
        $this->_pointerPeriod = $periodNext;
        $this->_pointerDay    = $dayNext;
        $this->_pointerYear   = $yearNext;
        
        // Advancing the Index of the iteration
        $this->_pointerIndex++;
    }

    public function rewind()
    {
        $this->_pointerIndex         = 0;
        $this->_pointerPeriod        = $this->_periodDefFirstDeadline->getPeriod();
        $this->_pointerYear          = $this->_periodDefFirstDeadline->getYear();
        $this->_pointerDay           = $this->_periodDefFirstDeadline->getDay();
        $this->_isPointerMoved       = true;
        $this->_isCurrentPeriodFound = false;
        $this->_currentPeriodDef     = clone $this->_periodDefFirstDeadline;
    }

    public function valid()
    {
        if ($this->_isCurrentPeriodFound) {
            return false;
        }
        
        // -----------------------------------------------------------------------------------------
        // First check is to make sure that the end date is not before the first deadline
        // We need to avoid making any foreach iteration as we may enter an infinite loop for dates
        // not matching, or moving pointer representing a period that is past the first deadline
        // when the first deadline should be the valid one.
        // Start by comparing years
        if ($this->_periodDefEnd->getTimestamp() < $this->_periodDefFirstDeadline->getTimestamp()) {
            return false;
        }
        // -----------------------------------------------------------------------------------------
        
        // -----------------------------------------------------------------------------------------
        // Now that we know that the PeriodEnd is >= than PeriodFirstDeadline we can do regular checks
        $pointerTimestamp = PeriodDefinition::toTimestamp(
            $this->_periodUnit, $this->_pointerPeriod, $this->_pointerDay, $this->_pointerYear
        );
        
        // If the pointer to the next period timestamp is before the day of the End condition
        // then that period is older and we should keep iterating
        if ($pointerTimestamp < $this->_periodDefEnd->getTimestamp()) {
            return true;
        }
        
        
        // Otherwise, we have reached the current period which can be
        // A) PeriodDate = End condition
        // B) PeriodDate = First period after End Condition
        if (!$this->_isCurrentPeriodFound) {
            $this->_isCurrentPeriodFound = true;
            return true;
        }
        
        return false;
    }

    /**
     * Updates the values of the iteration <kbd>PeriodDay</kbd> with those supplied.
     * @param int $period
     * @param int $year
     */
    protected function _updateIterationPeriodUnit()
    {
        // If the pointer has not been moved, there is no need to update the current PeriodDefinition nor
        // the current index
        if (!$this->_isPointerMoved) {
            return;
        }
        
        // We use reflection to do this update because the PeriodDefinition is meant to be none updatable
        // object so that iteration is not affected unexpectedly by external references
        // But we update the object to avoid creating multiple PeriodDefinition objects on each iteration
        // consuming unnecessary memory.
        $refClass = new \ReflectionClass(PeriodDefinition::class);
        $refPropYear      = $refClass->getProperty('_year');
        $refPropPeriod    = $refClass->getProperty('_period');
        $refPropDay       = $refClass->getProperty('_day');
        $refPropTimestamp = $refClass->getProperty('_timestamp');

        $refPropYear->setAccessible(true);
        $refPropPeriod->setAccessible(true);
        $refPropDay->setAccessible(true);
        $refPropTimestamp->setAccessible(true);
        
        $refPropPeriod->setValue($this->_currentPeriodDef, $this->_pointerPeriod);
        $refPropYear->setValue($this->_currentPeriodDef, $this->_pointerYear);
        $refPropDay->setValue($this->_currentPeriodDef, $this->_pointerDay);
        $refPropTimestamp->setValue($this->_currentPeriodDef, null);
        
        $this->_currentPeriodDef->setIndex($this->_pointerIndex);
        
        $this->_currentIndex = $this->_pointerIndex;
        
        $this->_isPointerMoved = false;
    }
    
}
