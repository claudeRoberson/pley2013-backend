<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Subscription;

/**
 * The <kbd>SubscriptionPeriodDefinitionGroup</kbd> class groups the PeriodDefinitions of all dates
 * of a subscription for a given Period
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Subscription
 * @subpackage Subscription
 */
class SubscriptionPeriodDefinitionGroup
{
    /** @var \Pley\Util\Time\PeriodDefinition */
    protected $_chargePeriodDef;
    /** @var \Pley\Util\Time\PeriodDefinition */
    protected $_deadlinePeriodDef;
    /** @var \Pley\Util\Time\PeriodDefinition */
    protected $_deliveryStartPeriodDef;
    /** @var \Pley\Util\Time\PeriodDefinition */
    protected $_deliveryEndPeriodDef;
    /** @var \Pley\Util\Time\PeriodDefinition */
    protected $_deadlineExtendedPeriodDef;
    /** @var int */
    protected $_index = 0;
    
    /** @return \Pley\Util\Time\PeriodDefinition */
    public function getChargePeriodDef()
    {
        return $this->_chargePeriodDef;
    }

    /** @return \Pley\Util\Time\PeriodDefinition */
    public function getDeadlinePeriodDef()
    {
        return $this->_deadlinePeriodDef;
    }

    /** @return \Pley\Util\Time\PeriodDefinition */
    public function getDeliveryStartPeriodDef()
    {
        return $this->_deliveryStartPeriodDef;
    }

    /** @return \Pley\Util\Time\PeriodDefinition */
    public function getDeliveryEndPeriodDef()
    {
        return $this->_deliveryEndPeriodDef;
    }

    /** @return \Pley\Util\Time\PeriodDefinition */
    public function getDeadlineExtendedPeriodDef()
    {
        return $this->_deadlineExtendedPeriodDef;
    }

    /** @return int */
    public function getIndex()
    {
        return $this->_index;
    }
    
    public function setChargePeriodDef(\Pley\Util\Time\PeriodDefinition $chargePeriodDef)
    {
        $this->_chargePeriodDef = $chargePeriodDef;
    }

    public function setDeadlinePeriodDef(\Pley\Util\Time\PeriodDefinition $deadlinePeriodDef)
    {
        $this->_deadlinePeriodDef = $deadlinePeriodDef;
    }

    public function setDeliveryStartPeriodDef(\Pley\Util\Time\PeriodDefinition $deliveryStartPeriodDef)
    {
        $this->_deliveryStartPeriodDef = $deliveryStartPeriodDef;
    }

    public function setDeliveryEndPeriodDef(\Pley\Util\Time\PeriodDefinition $deliveryEndPeriodDef)
    {
        $this->_deliveryEndPeriodDef = $deliveryEndPeriodDef;
    }

    public function setDeadlineExtendedPeriodDef(\Pley\Util\Time\PeriodDefinition $deadlineExtendedPeriodDef)
    {
        $this->_deadlineExtendedPeriodDef = $deadlineExtendedPeriodDef;
    }

    /** @param int $index */
    public function setIndex($index)
    {
        $this->_index = $index;
    }

}
