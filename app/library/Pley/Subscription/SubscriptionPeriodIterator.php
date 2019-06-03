<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Subscription;

/**
 * The <kbd>SubscriptionPeriodIterator</kbd> class is a specialized version of the Period Iterator
 * that allows us to calculate dates with consideration of all the Subscription deadlines
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Subscription
 * @subpackage Subscription
 */
class SubscriptionPeriodIterator extends \Pley\Util\Time\PeriodIterator
{
    /** @var \Pley\Entity\Subscription\SequenceItem[] */
    protected $_sequenceItemList;
    
    /** @var \Pley\Entity\Subscription\Subscription */
    protected $_subscription;
    /** @var boolean */
    protected $_isIgnoreExtendedPeriod;
    
    /** @var \Pley\Subscription\SubscriptionPeriodDefinitionGroup */
    protected $_periodDefGroup;
    
    public function __construct(
            \Pley\Entity\Subscription\Subscription $subscription, $isIgnoreExtendedPeriod = false)
    {
        $this->_subscription           = $subscription;
        $this->_isIgnoreExtendedPeriod = $isIgnoreExtendedPeriod;

        $sequenceItemDao         = \App::make('\Pley\Dao\Subscription\SequenceItemDao');
        $this->_sequenceItemList = $sequenceItemDao->where('`subscription_id` = ?', [$subscription->getId()]);
        
        $this->_resetPeriodDefinitions();
        
        parent::__construct(
            $subscription->getPeriodUnit(), $subscription->getPeriod(), $this->_periodDefGroup->getDeadlinePeriodDef()
        );
    }
    
    /** @return \Pley\Subscription\SubscriptionPeriodDefinitionGroup */
    public function getPeriodDefinitionGroup()
    {
        return $this->_periodDefGroup;
    }
    
    // Overloading
    public function rewind()
    {
        parent::rewind();
        $this->_resetPeriodDefinitions();
    }
        
    // Overriding
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
        $deadlineTimestamp = \Pley\Util\Time\PeriodDefinition::toTimestamp(
            $this->_periodUnit, $this->_pointerPeriod, $this->_pointerDay, $this->_pointerYear
        );
        $deadlineExtendedTimestamp = strtotime(
            sprintf('+%d days', $this->_subscription->getDeadlineExtendedDays()), $deadlineTimestamp
        );
        
        // We are looking for the period which deadline is after the current date
        // however, with the introduction of the extended deadline, if this period were invalid because
        // the current date is past this periods deadline, we need to make one additional check
        // against the ExtendedDeadline in conjuction with Inventory Remaining
        if ($deadlineTimestamp < $this->_periodDefEnd->getTimestamp()) {
            // For cases like, retrieving the Shipping period, we cannot use the Extended period
            // or calculations would yield an incorrect period, so, this flag is used to let the caller
            // explicitly ignore the Extended Deadline Period
            if ($this->_isIgnoreExtendedPeriod) {
                return true;
            }
            
            // We know that the current time is after the regular deadline, now we have to check if
            // it is past the extended one as well, which would mean we just have to keep moving periods
            if ($deadlineExtendedTimestamp < $this->_periodDefEnd->getTimestamp()) {
                return true;
            } 
            
            // Now we know we are in the extended period, so we need to check for available inventory
            // to know if we have found the current period, or should flow as normal into the next
            // period which would be the last valid one.
            if ($this->_isInventoryAvailable()) {
                $this->_isCurrentPeriodFound = true;
            }
            
            return true;
        }
        
        // Reaching here means that we have found the current period for any of the next to conditions
        // A) PeriodDate = End condition
        // B) PeriodDate = First period after End Condition
        // However, note that the current period could have been found as part of the extended deadline checks
        if (!$this->_isCurrentPeriodFound) {
            $this->_isCurrentPeriodFound = true;
            return true;
        }
        
        return false;
    }
    
    // Overload
    protected function _updateIterationPeriodUnit()
    {
        parent::_updateIterationPeriodUnit();
        
        if (!$this->_currentPeriodDef->equals($this->_periodDefGroup->getDeadlinePeriodDef())) {
            $periodStep = $this->_subscription->getPeriod();
            
            $this->_periodDefGroup->getChargePeriodDef()->moveNext($periodStep);
            $this->_periodDefGroup->getDeadlinePeriodDef()->moveNext($periodStep);
            $this->_periodDefGroup->getDeliveryStartPeriodDef()->moveNext($periodStep);
            $this->_periodDefGroup->getDeliveryEndPeriodDef()->moveNext($periodStep);
            
            // Now that definitions have been moved, we can calculated the extended deadline period
            // so it is always in relationship to the current deadline.
            $extendedDeadlineTimestamp = strtotime(
                sprintf('+%d days', $this->_subscription->getDeadlineExtendedDays()),
                $this->_periodDefGroup->getDeadlinePeriodDef()->getTimestamp()
            );
            
            $this->_periodDefGroup->setDeadlineExtendedPeriodDef(
                \Pley\Util\Time\PeriodDefinition::withTimestamp(
                    $this->_subscription->getPeriodUnit(), $extendedDeadlineTimestamp
                )
            );
            
            $this->_periodDefGroup->setIndex($this->_currentIndex);
        }
    }
    
    protected function _resetPeriodDefinitions()
    {
        $periodUnit  = $this->_subscription->getPeriodUnit();
        $periodStart = $this->_subscription->getStartPeriod();
        $yearStart   = $this->_subscription->getStartYear();

        $this->_periodDefGroup = new SubscriptionPeriodDefinitionGroup();
        $this->_periodDefGroup->setChargePeriodDef(new \Pley\Util\Time\PeriodDefinition(
            $periodUnit, $periodStart, $this->_subscription->getChargeDay(), $yearStart
        ));
        $this->_periodDefGroup->setDeadlinePeriodDef(new \Pley\Util\Time\PeriodDefinition(
            $periodUnit, $periodStart, $this->_subscription->getDeadlineDay(), $yearStart
        ));
        $this->_periodDefGroup->setDeliveryStartPeriodDef(new \Pley\Util\Time\PeriodDefinition(
            $periodUnit, $periodStart, $this->_subscription->getDeliveryDayStart(), $yearStart
        ));
        $this->_periodDefGroup->setDeliveryEndPeriodDef(new \Pley\Util\Time\PeriodDefinition(
            $periodUnit, $periodStart, $this->_subscription->getDeliveryDayEnd(), $yearStart
        ));
        
        // now adjusting periods in case dates span across
        // This is done in sequence since the order of dates is always as
        //  charge -> deadline -> deliveryStart -> deliveryEnd
        if ($this->_subscription->getDeadlineDay() < $this->_subscription->getChargeDay()) {
            $this->_periodDefGroup->getDeadlinePeriodDef()->moveNext();
            $this->_periodDefGroup->getDeliveryStartPeriodDef()->moveNext();
            $this->_periodDefGroup->getDeliveryEndPeriodDef()->moveNext();
        }
        if ($this->_subscription->getDeliveryDayStart() < $this->_subscription->getDeadlineDay()) {
            $this->_periodDefGroup->getDeliveryStartPeriodDef()->moveNext();
            $this->_periodDefGroup->getDeliveryEndPeriodDef()->moveNext();
        }
        if ($this->_subscription->getDeliveryDayEnd() < $this->_subscription->getDeliveryDayStart()) {
            $this->_periodDefGroup->getDeliveryEndPeriodDef()->moveNext();
        }
        
        // Now that definitions have been adjusted, we can calculated the extended deadline period
        $extendedDeadlineTimestamp = strtotime(
            sprintf('+%d days', $this->_subscription->getDeadlineExtendedDays()),
            $this->_periodDefGroup->getDeadlinePeriodDef()->getTimestamp()
        );
        $this->_periodDefGroup->setDeadlineExtendedPeriodDef(
            \Pley\Util\Time\PeriodDefinition::withTimestamp($periodUnit, $extendedDeadlineTimestamp)
        );
    }
    
    protected function _isInventoryAvailable()
    {
        $sequenceItem = $this->_sequenceItemList[0];
        if ($this->_subscription->getItemPullType() == \Pley\Enum\SubscriptionItemPullEnum::BY_SCHEDULE) {
            $sequenceItem = $this->_sequenceItemList[$this->_pointerIndex];
        }
        
        return $sequenceItem->hasAvailableSubscriptionUnits() > 0;
    }
    
}
