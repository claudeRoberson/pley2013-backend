<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Entity\Subscription;

use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Pley\Entity\Jsonable;

/**
 * The <kbd>Subscription</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Subscription
 * @subpackage Entity
 */
class Subscription implements ArrayableInterface, JsonableInterface
{
    use Jsonable;
    /** @var int */
    protected $_id;
    /** @var int Value from \Pley\Enum\BrandEnum */
    protected $_brandId;
    /** @var string */
    protected $_name;
    /** @var string */
    protected $_description;
    /** @var int */
    protected $_itemPullType;
    /** @var int */
    protected $_period;
    /** @var int Value from \Pley\Enum\PeriodUnitEnum */
    protected $_periodUnit;
    /** @var int */
    protected $_startPeriod;
    /** @var int */
    protected $_startYear;
    /** @var int */
    protected $_chargeDay;
    /** @var int */
    protected $_deadlineDay;
    /** @var int */
    protected $_deliveryDayStart;
    /** @var int */
    protected $_deliveryDayEnd;
    /** @var int */
    protected $_deadlineExtendedDays;
    /** @var int[] */
    protected $_signupPaymentPlanIdList;
    /** @var int[] */
    protected $_giftPriceIdList;
    /** @var string */
    protected $_welcomeEmailHeaderImg;
    
    public function __construct($id, $brandId, $name, $description, $itemPullType, $period, $periodUnit, 
            $startPeriod, $startYear, $chargeDay, $deadlineDay, $deliveryDayStart, $deliveryDayEnd,
            $deadlineExtendedDays, $signupPaymentPlanIdList, $giftPriceIdList, $welcomeEmailHeaderImg)
    {
        $this->_id                   = $id;
        $this->_brandId              = $brandId;
        $this->_name                 = $name;
        $this->_description          = $description;
        $this->_itemPullType         = $itemPullType;
        $this->_period               = $period;
        $this->_periodUnit           = $periodUnit;
        $this->_startPeriod          = $startPeriod;
        $this->_startYear            = $startYear;
        $this->_chargeDay            = $chargeDay;
        $this->_deadlineDay          = $deadlineDay;
        $this->_deliveryDayStart     = $deliveryDayStart;
        $this->_deliveryDayEnd       = $deliveryDayEnd;
        $this->_deadlineExtendedDays = $deadlineExtendedDays;

        // Validation to make sure that all Payment Plan IDs are integers
        foreach ($signupPaymentPlanIdList as $paymentPlanId) {
            if (!is_int($paymentPlanId)) {
                throw new \UnexpectedContentException("Unexpected Payment Plan ID value `{$paymentPlanId}` for subscription");
            }
        }
        $this->_signupPaymentPlanIdList = $signupPaymentPlanIdList;

        // Validation to make sure that all Payment Plan IDs are integers
        foreach ($giftPriceIdList as $paymentPlanId) {
            if (!is_int($paymentPlanId)) {
                throw new \UnexpectedContentException("Unexpected Gift Payment Plan ID value `{$paymentPlanId}` for subscription");
            }
        }
        $this->_giftPriceIdList = $giftPriceIdList;

        $this->_welcomeEmailHeaderImg = $welcomeEmailHeaderImg;
    }

    /** @return int */
    public function getId()
    {
        return $this->_id;
    }

    /** @return int */
    public function getBrandId()
    {
        return $this->_brandId;
    }

    /** @return string */
    public function getName()
    {
        return $this->_name;
    }

    /** @return string */
    public function getDescription()
    {
        return $this->_description;
    }

    /** @return int */
    public function getItemPullType()
    {
        return $this->_itemPullType;
    }

    /**
     * The number of Period Units in which each item of this subscription will be delivered.
     * <p>Example, if <kbd>PeriodUnit = Month</kbd> and <kbd>Period = 2</kbd>, then an item in this
     * subscription will be delivered every 2 months.</p>
     * @return int
     */
    public function getPeriod()
    {
        return $this->_period;
    }

    /**
     * The Unit of time to be used in combination with the period for frequency of item delivery.
     * <p>Example, if <kbd>PeriodUnit = Month</kbd> and <kbd>Period = 2</kbd>, then an item in this
     * subscription will be delivered every 2 months.</p>
     * @return int
     * @see \Pley\Enum\PeriodUnitEnum
     */
    public function getPeriodUnit()
    {
        return $this->_periodUnit;
    }

    /**
     * The period when the first item is to start.
     * <p>The initial date is calculated with the comination of the Start Year, Start Period and
     * Charge Day</p>
     * @return int
     */
    public function getStartPeriod()
    {
        return $this->_startPeriod;
    }

    /**
     * The year when the first item is to start.
     * <p>The initial date is calculated with the comination of the Start Year, Start Period and
     * Charge Day</p>
     * @return int
     */
    public function getStartYear()
    {
        return $this->_startYear;
    }

    /**
     * The day this subscription is to be charged for every recurring payment in relation to the
     * Period and PeriodUnit.
     * <p>Examples
     * <ul>
     *    <li>If <kbd>PeriodUnit = Month</kbd>, <kbd>Period = 1</kbd> and <kbd>ChargeDay = 20</kbd><br/>
     *        The subscription is to be charged on the 20th of every month.
     *    </li>
     *    <li>If <kbd>PeriodUnit = Month</kbd>, <kbd>Period = 2</kbd> and <kbd>ChargeDay = 16</kbd><br/>
     *        The subscription is to be charged on the 16th of every other month.
     *    </li>
     *    <li>If <kbd>PeriodUnit = Week</kbd>, <kbd>Period = 1</kbd> and <kbd>ChargeDay = 3</kbd><br/>
     *        The subscription is to be charged every Wednesday. (1 = Monday, 7 = Sunday)
     *    </li>
     * </ul>
     * </p>
     * @return int
     */
    public function getChargeDay()
    {
        return $this->_chargeDay;
    }

    /**
     * Get subscription deadline day
     * @return int
     */
    public function getDeadlineDay()
    {
        return $this->_deadlineDay;
    }

    /**
     * The first day this subscription item may arrive for the given period.
     * <p>Note: If this day is before the charge day, it means it is on the following period.</p>
     * <p>Examples
     * <ul>
     *    <li>If <kbd>PeriodUnit = Month</kbd>, <kbd>Period = 1</kbd> and <kbd>DeliveryStartDay = 20</kbd><br/>
     *        The subscription Item could arrive starting the 20th of every month.
     *    </li>
     *    <li>If <kbd>PeriodUnit = Month</kbd>, <kbd>Period = 2</kbd> and <kbd>DeliveryStartDay = 16</kbd><br/>
     *        The subscription Item could arrive starting the 16th of every other month.
     *    </li>
     *    <li>If <kbd>PeriodUnit = Week</kbd>, <kbd>Period = 1</kbd> and <kbd>DeliveryStartDay = 3</kbd><br/>
     *        The subscription Item could arrive starting every Wednesday. (1 = Monday, 7 = Sunday)
     *    </li>
     * </ul>
     * </p>
     * @return int
     */
    public function getDeliveryDayStart()
    {
        return $this->_deliveryDayStart;
    }

    /**
     * The last day this subscription item is expected arrive for the given period.
     * <p>If this value is smaller than `deliveryDayStart`, it means it the day on the following period.</p>
     * <p>Examples
     * <ul>
     *    <li>If <kbd>PeriodUnit = Month</kbd>, <kbd>Period = 1</kbd> and <kbd>DeliveryStartDay = 20</kbd><br/>
     *        The subscription should arrive at the latest, the 20th of every month.
     *    </li>
     *    <li>If <kbd>PeriodUnit = Month</kbd>, <kbd>Period = 2</kbd> and <kbd>DeliveryStartDay = 16</kbd><br/>
     *        The subscription should arrive at the latest, the 16th of every other month.
     *    </li>
     *    <li>If <kbd>PeriodUnit = Week</kbd>, <kbd>Period = 1</kbd> and <kbd>DeliveryStartDay = 3</kbd><br/>
     *        The subscription should arrive at the latest by Wednesday of every week. (1 = Monday, 7 = Sunday)
     *    </li>
     * </ul>
     * </p>
     * @return int
     */
    public function getDeliveryDayEnd()
    {
        return $this->_deliveryDayEnd;
    }
    
    /**
     * Returns the number of days after the Registration Deadline where we could have an extended
     * registration deadline if there is inventory available for the shipping period
     * @return int
     */
    public function getDeadlineExtendedDays()
    {
        return $this->_deadlineExtendedDays;
    }

    /**
     * A list of Payment Plan options for signing up to this Subscription
     * @return int[]
     */
    public function getSignupPaymentPlanIdList()
    {
        return $this->_signupPaymentPlanIdList;
    }

    /**
     * A list of Payment Plan options for a Gift Subscription
     * @return int[]
     */
    public function getGiftPriceIdList()
    {
        return $this->_giftPriceIdList;
    }

    /** @var string */
    public function getWelcomeEmailHeaderImg()
    {
        return $this->_welcomeEmailHeaderImg;
    }

    /**
     * @return mixed
     */
    public function getBoxes()
    {
        return $this->_boxes;
    }

    /**
     * @param mixed $boxes
     * @return Subscription
     */
    public function setBoxes($boxes)
    {
        $this->_boxes = $boxes;
        return $this;
    }

}
