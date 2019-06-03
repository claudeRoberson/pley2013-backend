<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace Pley\Subscription;

use \Pley\Entity\Payment\PaymentPlan;
use \Pley\Entity\Profile\ProfileSubscription;
use \Pley\Entity\Subscription\Item;
use \Pley\Entity\Subscription\Subscription;
use \Pley\Util\Time\PeriodDefinition;

/** ♰
 * The <kbd>SubscriptionManager</kbd> handles methods related to subscriptions.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Subscription
 * @subpackage Subscription
 */
class SubscriptionManager
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Repository\Subscription\SubscriptionRepository */
    protected $_subscriptionRepo;
    /** @var \Pley\Dao\Subscription\ItemDao */
    protected $_itemDao;
    /** @var \Pley\Dao\Subscription\ItemPartDao */
    protected $_itemPartDao;
    /** @var \Pley\Dao\Subscription\ItemPartStockDao */
    protected $_itemPartStockDao;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_paymentPlanDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionShipmentDao */
    protected $_profileSubsShipDao;
    /** @var \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao */
    protected $_vendorPaymentPlanDao;
    /** @var  \Pley\Shipping\ShippingZonePicker */
    protected $_shippingZonePicker;
    /**
     * Helper variable to store Reflection objects to avoid multiple object creation and thus
     * increase performance and use less memory.
     * @var array
     */
    private $_reflectedMap = [];

    public function __construct(
        \Pley\Db\AbstractDatabaseManager $dbManager,
        \Pley\Repository\Subscription\SubscriptionRepository $subscriptionRepo,
        \Pley\Dao\Subscription\ItemDao $itemDao,
        \Pley\Dao\Subscription\ItemPartDao $itemPartDao,
        \Pley\Dao\Subscription\ItemPartStockDao $itemPartStockDao,
        \Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
        \Pley\Dao\Profile\ProfileSubscriptionShipmentDao $profileSubsShipDao,
        \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao,
        \Pley\Shipping\ShippingZonePicker $shippingZonePicker)
    {
        $this->_dbManager = $dbManager;
        $this->_subscriptionRepo = $subscriptionRepo;
        $this->_itemDao = $itemDao;
        $this->_itemPartDao = $itemPartDao;
        $this->_itemPartStockDao = $itemPartStockDao;
        $this->_paymentPlanDao = $paymentPlanDao;
        $this->_profileSubsShipDao = $profileSubsShipDao;
        $this->_vendorPaymentPlanDao = $vendorPaymentPlanDao;
        $this->_shippingZonePicker = $shippingZonePicker;
    }

    /**
     * Return the <kbd>Subscription</kbd> entity for the supplied id or null if not found.
     * @param int $subscriptionId
     * @return \Pley\Entity\Subscription\Subscription
     */
    public function getSubscription($subscriptionId)
    {
        return $this->_subscriptionRepo->getSubscription($subscriptionId);
    }

    /**
     * Returns a list of all <kbd>Subscription</kbd> entities.
     * @return \Pley\Entity\Subscription\Subscription[]
     */
    public function getAllSubscriptions()
    {
        return $this->_subscriptionRepo->getAllSubscriptions();
    }

    /**
     * Return a list of the Payment Plans available for new signups on the supplied Subscription.
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @return \Pley\Entity\Payment\PaymentPlan[]
     */
    public function getSubscriptionPaymentPlanList(Subscription $subscription)
    {
        $paymentPlanIdList = $subscription->getSignupPaymentPlanIdList();

        $paymentPlanList = [];
        foreach ($paymentPlanIdList as $paymentPlanId) {
            $paymentPlanList[] = $this->_paymentPlanDao->find($paymentPlanId);
        }

        return $paymentPlanList;
    }

    /**
     * Returns a sanitized list of <kbd>SequenceItem</kbd> objects with those items that still have
     * availabe inventory for new subscriptions.
     * <p>The sanitized list adapts to the Subscription ItemPull type.</p>
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @param bool $includeDates
     * @return \Pley\Entity\Subscription\SequenceItem[]
     */
    public function getItemSequence(Subscription $subscription, $includeDates = true)
    {
        $itemSequenceList = $this->getFullItemSequence($subscription);
        $itemSequenceCount = count($itemSequenceList);

        $sanitizedSequence = [];
        $startingPeriodIdx = 0;  // By default assume that the ItemPull Type is `IN_ORDER`

        // If the ItemPull is actually by schedule, then update the starting period index
        if ($subscription->getItemPullType() == \Pley\Enum\SubscriptionItemPullEnum::BY_SCHEDULE) {
            $startingPeriodIdx = $this->getActivePeriodIndex($subscription);
        }

        for ($i = $startingPeriodIdx; $i < $itemSequenceCount; $i++) {
            $sequenceItem = $itemSequenceList[$i];

            //TODO: analyze if need this stock limitation when getting an item sequence
            //if ($sequenceItem->hasAvailableSubscriptionUnits()) {
                $sanitizedSequence[] = $sequenceItem;
            //}
        }

        if ($includeDates) {
            $this->_setItemSequenceDates($subscription, $sanitizedSequence);
        }

        return $sanitizedSequence;
    }


    public function getItemSequenceForProfileSubscription(
            Subscription $subscription, \Pley\Entity\Profile\ProfileSubscription $profileSubscription)
    {
        $shipmentList = $this->_profileSubsShipDao->findByProfileSubscription($profileSubscription->getId());

        // If there are no shipments, we don't need to ajust, we just retrieve the regular sequence
        if (empty($shipmentList)) {
            return $this->getItemSequence($subscription);
        }

        $latestProfileShipment   = $shipmentList[0]; // List is ordered from most recent first
        $lastShipmentScheduleIdx = $latestProfileShipment->getScheduleIndex();
        $itemSequenceList        = $this->getFullItemSequence($subscription);

        // Now we need to create the item sequence, which depends on the pull type and relationship
        // to the latest shipment
        if ($subscription->getItemPullType() == \Pley\Enum\SubscriptionItemPullEnum::BY_SCHEDULE) {
            $activePeriodIdx   = $this->getActivePeriodIndex($subscription);
            $startingPeriodIdx = $lastShipmentScheduleIdx + 1;

            if ($activePeriodIdx > $startingPeriodIdx) {
                $startingPeriodIdx = $activePeriodIdx;
            }

            // Removing initial items before the period index that comes next
            array_splice($itemSequenceList, 0, $startingPeriodIdx);

        } else { // $subscription->getItemPullType() == SubscriptionItemPullEnum::IN_ORDER
            foreach ($shipmentList as $profileSubsShipment) {
                for ($i = 0; $i < count($itemSequenceList); $i++) {
                    /* @var $sequenceItem \Pley\Entity\Subscription\SequenceItem */
                    $sequenceItem = $itemSequenceList[$i];

                    // If the Item in the sequence match the one in the Shipment, lets remove it and
                    // now look for the next one in the shipment list
                    if ($profileSubsShipment->getItemSequenceIndex() == $sequenceItem->getSequenceIndex()) {
                        array_splice($itemSequenceList, $i, 1);
                        break;
                    }
                }
            }
        }

        $sanitizedSequence = [];
        $itemSequenceCount = count($itemSequenceList);
        for ($i = 0; $i < $itemSequenceCount; $i++) {
            $sequenceItem = $itemSequenceList[$i];

            if ($sequenceItem->hasAvailableSubscriptionUnits()) {
                $sanitizedSequence[] = $sequenceItem;
            }
        }

        $this->_setItemSequenceDates($subscription, $sanitizedSequence, $lastShipmentScheduleIdx);

        return $sanitizedSequence;
    }

    /**
     * Returns the full list of <kbd>SequenceItem</kbd> objects for the supplied subscription.
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @return \Pley\Entity\Subscription\SequenceItem[]
     */
    public function getFullItemSequence(Subscription $subscription)
    {
        return $this->_subscriptionRepo->getItemSequence($subscription->getId());
    }

    /**
     * Returns the index that maps to the supplied Subscription Active period.
     * <p>Subscription periods are calculated dynamically, as such, not only are they sequential
     * programmed dates but the order of the dates can be consider as an array, and thus the very
     * first shipping period would be considered Index 0, the second shipping period would be Index 1,
     * and so on.</p>
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @return int
     */
    public function getActivePeriodIndex(Subscription $subscription)
    {
        $periodDefGrp = $this->_getActivePeriodDefinitionGroup($subscription);
        return $periodDefGrp->getIndex();
    }

    /**
     * Get the Active Period Index for Shippable items.
     * <p>This is different from the Active Period Index which is based on the Period Subscription
     * Deadline, this one is the period after the last item deadline and before the current item's
     * deadline.</p>
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @return int
     */
    public function getActiveShippablePeriodIndex(Subscription $subscription)
    {
        $ignoreExtendedRegPeriod = true;
        $activePeriodDefGrp = $this->_getActivePeriodDefinitionGroup($subscription, $ignoreExtendedRegPeriod);

        // The active shippable period is always the period before the current one.
        // This is because in the current period we are taking new registrations for the next shipment cycle
        // while shipping items from the previous cycle which is over and now in shipping time
        $activeShippablePeriodIdx = $activePeriodDefGrp->getIndex() - 1;

        // Adding a check for the case where we are still in the very first Period, so there is no
        // previous cycle, so the current shippable period is also that of the active period
        if ($activeShippablePeriodIdx < 0) {
            $activeShippablePeriodIdx = 0;
        }

        return $activeShippablePeriodIdx;
    }

    /**
     * Returns the period where the first item will be shipped.
     * <p>If there is enough inventory, usually the Active period is returned, however if there
     * was no more inventory, a future period is returned instead where the first available item
     * is eligible.</p>
     * @param \Pley\Entity\Subscription\Subscription   $subscription
     * @param \Pley\Entity\Subscription\SequenceItem[] $itemSequence    (Optional)<br/>To improve
     *      performance if the sequence has already been calculated before this call.
     * @param int                                      $lastShipmentScheduleIdx (Optional)<br/>Used to
     *      shift the potential start index for an item sequence that follows an exisiting subscription.
     */
    public function getFirstShipmentPeriodIndex(
            Subscription $subscription, array $itemSequence = null, $lastShipmentScheduleIdx = null)
    {
        if ($itemSequence == null) {
            $itemSequence = $this->getItemSequence($subscription);
        }
        
        $activePeriodIdx = $this->getActivePeriodIndex($subscription);
        
        // First we assume that the first item can be shipped within the active period
        $firstPeriodIdx = $activePeriodIdx;

        // If a last schedule index is supplied, this sequence is a follow up sequence for an existing
        // subscription and thus, we need to adjust to the last shipment item given
        if (isset($lastShipmentScheduleIdx) && ($lastShipmentScheduleIdx + 1) > $firstPeriodIdx) {
            $firstPeriodIdx = $lastShipmentScheduleIdx + 1;
        }

        // However, we need to check if this is not true by checking the period assigned to the first
        // item in their queue, as it may represent a future box and thus we have to inform that
        // the the first item will be shipped where it release schedule is set for
        if ($itemSequence[0]->getSequenceIndex() > $firstPeriodIdx) {
            $firstPeriodIdx = $itemSequence[0]->getSequenceIndex();
        }

        return $firstPeriodIdx;
    }

    /**
     * Returns a list of <kbd>PeriodDefinition</kbd> objects from the very first shipping period
     * to the last one defined by the subscription item sequence.
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @return \Pley\Util\Time\PeriodDefinition[]
     */
    public function getFullPeriodDefinitionList(Subscription $subscription)
    {
        // We need to retrieve the item sequence so that we don't end up in an infinite cycle
        // calculating periods till the end of time
        $itemSequenceList  = $this->getFullItemSequence($subscription);
        $itemSequenceCount = count($itemSequenceList);
        
        $periodIterator = new SubscriptionPeriodIterator($subscription);
        
        $periodList = [];
        
        $periodIterator->rewind();
        for ($i = 0; $i < $itemSequenceCount; $i++, $periodIterator->next()) {
            $periodList[] = $periodIterator->cloneCurrent();
        }
        
        return $periodList;
    }
    
    /**
     * Increases the count of a purchased or reserved item in the storage.
     * <p>It also updates the reference on the <kbd>$sequenceItem</kbd> object to the new value.</p>
     * @param \Pley\Entity\Subscription\SequenceItem $sequenceItem
     * @param \Pley\Entity\Profile\QueueItem         $queueItem
     */
    public function increaseItemSale(
            \Pley\Entity\Subscription\SequenceItem $sequenceItem, \Pley\Entity\Profile\QueueItem $queueItem)
    {
        $this->_subscriptionRepo->increaseItemSale($sequenceItem, $queueItem);
    }
    
    /**
     * Frees up a Reserved item from the item sequence on the given subscription.
     * @param int $subscriptionId
     * @param int $itemSequenceIndex
     */
    public function freeReservedItem($subscriptionId, $itemSequenceIndex)
    {
        $sequenceItem = $this->_subscriptionRepo->getSequenceItemByIndex($subscriptionId, $itemSequenceIndex);
        $this->_subscriptionRepo->freeReservedItem($sequenceItem);
    }
    
    /**
     * Frees up a Purchased item from the item sequence on the given subscription.
     * <p>Note: This action is only to be taken as a result of CustomerService performing a full cancel.</p>
     * @param int $subscriptionId
     * @param int $itemSequenceIndex
     */
    public function freePurchasedItem($subscriptionId, $itemSequenceIndex)
    {
        $sequenceItem = $this->_subscriptionRepo->getSequenceItemByIndex($subscriptionId, $itemSequenceIndex);
        $this->_subscriptionRepo->freePurchasedItem($sequenceItem);
    }
    
    /**
     * Moves a unit from the Reserved stock into the Purchased stock.
     * @param \Pley\Entity\Subscription\SequenceItem $sequenceItem
     */
    public function reservedToPaidItem($subscriptionId, $itemSequenceIndex)
    {
        $sequenceItem = $this->_subscriptionRepo->getSequenceItemByIndex($subscriptionId, $itemSequenceIndex);
        $this->_subscriptionRepo->reservedToPaidItem($sequenceItem);
    }
    
    /**
     * Retrieves a PeriodDefinition group object that contains the subscription dates for the active
     * period or the period identified by the supplied optional index.
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @param int                                    $index        (Optional)
     * @return \Pley\Subscription\SubscriptionPeriodDefinitionGroup
     */
    public function getSubscriptionDates(Subscription $subscription, $index = null)
    {
        if ($index === null) {
            $periodDefGrp = $this->_getActivePeriodDefinitionGroup($subscription);
        } else {
            $periodIterator = new SubscriptionPeriodIterator($subscription);
            $periodIterator->forwardToIndex($index);
            $periodDefGrp = $periodIterator->getPeriodDefinitionGroup();
        }
        
        return $periodDefGrp;
    }

    /**
     * Return the <kbd>SequenceItem</kbd> entity for the supplied shipment.
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment $shipment
     * @return \Pley\Entity\Subscription\SequenceItem
     */
    public function getScheduledItem(\Pley\Entity\Profile\ProfileSubscriptionShipment $shipment)
    {
        $scheduleIndex = $shipment->getScheduleIndex();
        $sequenceItem = $this->getSequenceItemByIndex(
            $shipment->getSubscriptionId(), $shipment->getItemSequenceIndex(), $scheduleIndex
        );

        return $sequenceItem;
    }

    /**
     * Retrieves the <kbd>SequenceItem</kbd> object for the supplied sequence index and sets it's dates
     * to the period supplied.
     * @param int $subscriptionId
     * @param int $itemSequenceIndex
     * @param int $datePeriodIndex
     * @return \Pley\Entity\Subscription\SequenceItem
     */
    public function getSequenceItemByIndex($subscriptionId, $itemSequenceIndex, $datePeriodIndex)
    {
        $subscription = $this->getSubscription($subscriptionId);
        $periodDefGrp = $this->getSubscriptionDates($subscription, $datePeriodIndex);

        $sequenceItem = $this->_subscriptionRepo->getSequenceItemByIndex($subscriptionId, $itemSequenceIndex);
        $sequenceItem->setSubscriptionDates(
            $datePeriodIndex, 
            $periodDefGrp->getDeadlinePeriodDef()->getTimestamp(),
            $periodDefGrp->getChargePeriodDef()->getTimestamp(),
            $periodDefGrp->getDeliveryStartPeriodDef()->getTimestamp(),
            $periodDefGrp->getDeliveryEndPeriodDef()->getTimestamp()
        );

        return $sequenceItem;
    }

    /**
     * Helper method to get the <kbd>PeriodDefinition</kbd> object on the given subscription at the
     * supplied index.
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @param int $index
     * @return \Pley\Util\Time\PeriodDefinition
     */
    public function getPeriodDefinitionForIndex(Subscription $subscription, $index)
    {
        // Based on the Schedule Index, we can iterate until the selected period by the index.
        $periodIterator = new SubscriptionPeriodIterator($subscription);
        $periodIterator->forwardToIndex($index);
        $sequencePeriod = $periodIterator->current();
        return $sequencePeriod;
    }

    /**
     * Shift all existing and non-delivered shipments by one period forward.
     * Used for a skip-a-box feature, when pausing a subscription for a one shipping period.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubscription
     * @return bool
     */
    public function canPauseProfileSubscription(ProfileSubscription $profileSubscription){
        if ($profileSubscription->getStatus() != \Pley\Enum\SubscriptionStatusEnum::ACTIVE){
            return false;
        }
        return true;
    }

    /**
     * Decreases the stock of all the parts used for the item specified on this shipment.
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment $profileShipment
     */
    public function decreaseStock(\Pley\Entity\Profile\ProfileSubscriptionShipment $profileShipment)
    {
        $item = $this->getItem($profileShipment->getItemId());
        foreach ($item->getPartList() as $part) {
            // Doing the respective stock decrease
            switch ($part->getType()) {
                case \Pley\Enum\ItemPartEnum::SHIRT:
                    $this->_itemPartStockDao->decreaseStock($part, $profileShipment->getShirtSize());
                    break;

                default:
                    $this->_itemPartStockDao->decreaseStock($part);
                    break;
            }
        }
    }

    /**
     * Retrieve the Item definition with all its parts and the stock for each of these parts.
     * @param int $itemId
     * @param boolean $retrieveStock (Optional)<br/>Default <kbd>false</kbd><br/>Used to retrieve the
     *      stock when assembling for shipping.
     * @return \Pley\Entity\Subscription\Item
     */
    public function getItem($itemId, $retrieveStock = false)
    {
        $item = $this->_itemDao->find($itemId);

        if (empty($item)) {
            return null;
        }

        $partList = $this->_itemPartDao->all($item);
        $item->setPartList($partList);

        // If we don't need to retrieve the stock, then just return the Item and avoid loading more data
        if (!$retrieveStock) {
            return $item;
        }

        // Now that we know we need to retrieve stock, let's do so.
        foreach ($partList as $part) {
            $partStockList = $this->_itemPartStockDao->findByItemPart($part->getId());

            // If there is only one entry, there part is generic and only one stock value
            // so just set the straight value
            if (count($partStockList) == 1) {
                $part->setStockDef($partStockList[0]->getStock());

            // Otherwise, it is a part that requires specifics, like a shirt size, so we create a
            // map of the entries and set it as the Stock definition.
            } else {
                $partStockMap = [];
                foreach ($partStockList as $partStock) {
                    $partStockMap[$partStock->getTypeSourceId()] = $partStock->getStock();
                }
                $part->setStockDef($partStockMap);
            }
        }

        return $item;
    }

    /**
     * Return the boxes amount for a given subscription and payment plan
     * @param int $subscriptionId
     * @param int $paymentPlanId
     * @return int
     */
    public function getSubscriptionBoxCount($subscriptionId, $paymentPlanId)
    {
        $subscription = $this->_subscriptionRepo->getSubscription($subscriptionId);
        $paymentPlan = $this->_paymentPlanDao->find($paymentPlanId);
        return $paymentPlan->getPeriod() / $subscription->getPeriod();
    }

    /**
     * Returns a list of <kbd>Item</kbd> objects that are to be shipped in the active period for the
     * supplied subscription.
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @return \Pley\Entity\Subscription\Item[]
     */
    public function getShippableItemList(Subscription $subscription)
    {
        $activeShippablePeriodIdx = $this->getActiveShippablePeriodIndex($subscription);

        $itemIdList = $this->_profileSubsShipDao->getItemIdListByPeriod(
            $subscription->getId(), $activeShippablePeriodIdx, \Pley\Enum\Shipping\ShipmentStatusEnum::PREPROCESSING
        );
        if (empty($itemIdList)) {
            return [];
        }

        $itemList = [];
        foreach ($itemIdList as $itemId) {
            $itemList[] = $this->getItem($itemId);
        }
        
        return $itemList;
    }

    /**
     * Get the Plan Pricing for the selected Payment Plan (and potential country and state filter)
     * @param \Pley\Entity\Payment\PaymentPlan $paymentPlan
     * @param string                           $countryCode (Optional)
     * @param string                           $stateCode   (Optional)
     * @return \Pley\Entity\Payment\VendorPaymentPlan
     */
    public function getPlanPriceForCountry(PaymentPlan $paymentPlan, $countryCode = null, $stateCode = null)
    {
        $vendorPaymentPlan = null;
        if (!$countryCode) {
            $vendorPaymentPlan = $this->_vendorPaymentPlanDao->findByPaymentPlan(
                $paymentPlan->getId(),
                \Pley\Enum\Shipping\ShippingZoneEnum::DEFAULT_ZONE_ID,
                \Pley\Enum\PaymentSystemEnum::STRIPE
            );
        } else {
            $zone = $this->_shippingZonePicker->getShippingZoneByCountryCode($countryCode, $stateCode);
            $vendorPaymentPlan = $this->_vendorPaymentPlanDao->findByPaymentPlan(
                $paymentPlan->getId(),
                $zone->getId(),
                \Pley\Enum\PaymentSystemEnum::STRIPE
            );
        }
        return $vendorPaymentPlan;
    }

    /**
     * Retrieves a List containing Maps that detail a Shirt size and the number of Shipments related
     * to it, for the supplied parameters.
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @param \Pley\Entity\Subscription\Item $item
     * @param int $periodIndex
     * @return array A list with maps of the following structure<br/>
     *      <pre>array(
     *      &nbsp;   array(
     *      &nbsp;       'sizeId' => int,
     *      &nbsp;       'count'  => int
     *      &nbsp;   ),
     *      &nbsp;   ...
     *      )</pre>
     */
    public function getShipmentCountByShirtSize(Subscription $subscription, Item $item, $periodIndex)
    {
        $sql = 'SELECT `type_shirt_size_id`, COUNT(*) AS `count` '
            . 'FROM `profile_subscription_shipment` '
            . 'WHERE `subscription_id` = ? '
            . 'AND `schedule_index` = ? '
            . 'AND `item_id` = ? '
            . 'AND `status` = 1 '
            . 'AND `label_url` IS NOT NULL '
            . 'GROUP BY `type_shirt_size_id`';
        $prepStmt = $this->_dbManager->prepare($sql);
        $bindings = [
            $subscription->getId(),
            $periodIndex,
            $item->getId()
        ];
        $prepStmt->execute($bindings);

        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $resultLength = $prepStmt->rowCount();
        $prepStmt->closeCursor();

        for ($i = 0; $i < $resultLength; $i++) {
            $resultSet[$i] = [
                'sizeId' => $resultSet[$i]['type_shirt_size_id'],
                'count' => $resultSet[$i]['count'],
            ];
        }

        return $resultSet;
    }

    /**
     * Retrieves a List containing Maps that detail a Shipment Status and the number of Shipments
     * related to it, for the supplied parameters.
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @param \Pley\Entity\Subscription\Item $item
     * @param int $periodIndex
     * @return array A list with maps of the following structure<br/>
     *      <pre>array(
     *      &nbsp;   array(
     *      &nbsp;       'status' => int,
     *      &nbsp;       'count'  => int
     *      &nbsp;   ),
     *      &nbsp;   ...
     *      )</pre>
     */
    public function getShipmentCountByStatus(Subscription $subscription, Item $item, $periodIndex)
    {
        $sql = 'SELECT `status`, COUNT(*) AS `count` '
            . 'FROM `profile_subscription_shipment` '
            . 'WHERE `subscription_id` = ? '
            . 'AND `schedule_index` = ? '
            . 'AND `item_id` = ? '
            . 'GROUP BY `status`';
        $prepStmt = $this->_dbManager->prepare($sql);
        $bindings = [
            $subscription->getId(),
            $periodIndex,
            $item->getId()
        ];
        $prepStmt->execute($bindings);

        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();

        return $resultSet;
    }

    /**
     * Sets the PeriodDefinition for each of the supplied sequence item from the active period.
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @param \Pley\Entity\Subscription\SequenceItem[] $itemSequenceList
     * @param int                                      $lastShipmentScheduleIdx (Optional)<br/>Used to
     *      shift the potential start index for an item sequence that follows an exisiting subscription.
     */
    protected function _setItemSequenceDates(Subscription $subscription, $itemSequenceList, $lastShipmentScheduleIdx = null)
    {
        // Subscription Schedule Periods are in sync with the item sequence, however, there are a couple
        // scenarios to consider
        // A) If items are sold out, the first item in the sequence will be released in the future
        // B) If past released items were not sold out, then we can ship those from the current period
        $firstAvailablePeriodIdx = $this->getFirstShipmentPeriodIndex($subscription, $itemSequenceList, $lastShipmentScheduleIdx);
        
        $itemSequenceLength = count($itemSequenceList);

        // We have to generate periods equal to the item sequence length, however, we have to skip
        // periods before the active one, which means if it is not the very first period, we will need
        // to itearate more times than the length of the sequence.
        $lastPeriod = $itemSequenceLength + $firstAvailablePeriodIdx;

        $periodIterator = new SubscriptionPeriodIterator($subscription);
        $periodIterator->rewind();
        for ($i = 0; $i < $lastPeriod; $i++, $periodIterator->next()) {
            $periodIterator->current();
            
            if ($i < $firstAvailablePeriodIdx) {
                continue;
            }

            $sequenceItem = $itemSequenceList[$i - $firstAvailablePeriodIdx];
            $periodDefGrp = $periodIterator->getPeriodDefinitionGroup();

            $sequenceItem->setSubscriptionDates(
                $periodDefGrp->getIndex(), 
                $periodDefGrp->getDeadlinePeriodDef()->getTimestamp(),
                $periodDefGrp->getChargePeriodDef()->getTimestamp(),
                $periodDefGrp->getDeliveryStartPeriodDef()->getTimestamp(),
                $periodDefGrp->getDeliveryEndPeriodDef()->getTimestamp()
            );
        }
    }

    /**
     * Returns the Active Period Definition.
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @return \Pley\Subscription\SubscriptionPeriodDefinitionGroup
     */
    protected function _getActivePeriodDefinitionGroup(Subscription $subscription, $isIgnoreExtendedPeriod = false)
    {
        $periodIterator = new SubscriptionPeriodIterator($subscription, $isIgnoreExtendedPeriod);

        // Initializing the first Definition of the schedule (Also needed in case the current date is before
        // the first scheduled item and thus the `foreach` exits immediately and never sets this value)
        $periodDef = $periodIterator->current();

        // Locating the current active period by it's index, since we don't really need to do anything
        // with the dates, we know that once the foreach finishes, it will point to the current
        // active period
        /* @var $periodDef \Pley\Util\Time\PeriodDefinition */
        foreach ($periodIterator as $periodDef) {
        }

        return $periodIterator->getPeriodDefinitionGroup();
    }

}