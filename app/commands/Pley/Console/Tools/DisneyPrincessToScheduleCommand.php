<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console\Tools;

use \Illuminate\Console\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * The <kbd>DisneyPrincessToScheduleCommand</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class DisneyPrincessToScheduleCommand extends Command
{
    use \Pley\Console\ConsoleOutputTrait;
    
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:disneyToSchedule';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'change disney from sequence to schedule and adjust shipments and queues.';
    
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionShipmentDao */
    protected $_profileSubsShipDao;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_paymentPlanDao;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionManager;
    /** @var \Pley\User\UserSubscriptionManager */
    protected $_userSubscriptionMgr;
    /** @var \Pley\Repository\Subscription\SubscriptionRepository */
    protected $_subscriptionRepo;
    
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        
        $this->_dbManager           = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_profileSubsDao      = \App::make('\Pley\Dao\Profile\ProfileSubscriptionDao');
        $this->_profileSubsShipDao  = \App::make('\Pley\Dao\Profile\ProfileSubscriptionShipmentDao');
        $this->_paymentPlanDao      = \App::make('\Pley\Dao\Payment\PaymentPlanDao');
        $this->_subscriptionManager = \App::make('\Pley\Subscription\SubscriptionManager');
        $this->_userSubscriptionMgr = \App::make('\Pley\User\UserSubscriptionManager');
        $this->_subscriptionRepo    = \App::make('\Pley\Repository\Subscription\SubscriptionRepository');

        $this->_setLogOutput(true);
    }
    
    public function fire()
    {
        $this->line('Collecting Profile Subscriptions to fix');
        $profileSubsIdList = $this->_getProfileSubsIdList();
        
        $this->line('Potential Fixes:  ' . count($profileSubsIdList) . ' subscriptions.');
        
        $pp = new \Pley\Console\Util\ProgressPrinter();
        foreach ($profileSubsIdList as $profileSubsId) {
            $this->_processProfileSubscription($profileSubsId);
            $pp->step();
        }
        $pp->finish();
        
        $this->line('Done');
    }
    
    
    private function _getProfileSubsIdList()
    {
        $sql = 'SELECT `id` FROM `profile_subscription` WHERE `subscription_id` = 1 AND `status` <> 3';
        
        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute();
        
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        
        $profileSubsIdList = [];
        foreach ($resultSet as $dbRecord) {
            $profileSubsIdList[] = $dbRecord['id'];
        }
        
        return $profileSubsIdList;
    }
    
    private function _processProfileSubscription($profileSubsId)
    {
        $profileSubs  = $this->_profileSubsDao->find($profileSubsId);
        
        // Adjusting first the remaining reserved sequence, so that if we need to adjust shipments
        // it is as easy as just shifting elements out of their queue.
        $this->_handleRemainingSequence($profileSubs);
        
        // Past due have no shipments left, so no need to check for this
        if ($profileSubs->getStatus() != \Pley\Enum\SubscriptionStatusEnum::PAST_DUE) {
            $this->_handleRemainingShipments($profileSubs);
        }
    }
    
    private function _handleRemainingSequence(\Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        $subscriptionId    = $profileSubs->getSubscriptionId();
        $itemSequenceQueue = $profileSubs->getItemSequenceQueue();
        
        if (empty($itemSequenceQueue)) {
            return;
        }
        
        $shiftCount = 0;
        foreach ($itemSequenceQueue as $queueItem) {
            // If whatever is left over, matches Rapunzel, then this queue is good to go
            if ($queueItem->getSequenceIndex() >= 2) {
                break;
            }
            
            $this->_subscriptionManager->freeReservedItem($subscriptionId, $queueItem->getSequenceIndex());
            $shiftCount++;
        }
        
        // If nothing was shifted, no need to keep going.
        if ($shiftCount == 0) {
            return;
        }
        
        for ($i = 0; $i < $shiftCount; $i++) {
            array_shift($itemSequenceQueue);
        }
        
        $profileSubs->setItemSequenceQueue($itemSequenceQueue);
        $this->_profileSubsDao->save($profileSubs);
    }
    
    private function _handleRemainingShipments(\Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        $shipmentList      = $this->_getShipmentsRemainingForProfileSubscription($profileSubs->getId());
        $itemSequenceQueue = $profileSubs->getItemSequenceQueue();

        // If there are no shipments to check, or from the remaining ones, the next one to be shipped
        // is already set to Rapunzel, there is nothing to adjust
        if (empty($shipmentList) || $shipmentList[0]->getItemSequenceIndex() == 2) {
            return;
        }
        
        // All shipments as of this fix, will start on item sequence 2 (which will equate to Rapunzel)
        $itemSequenceIdxFixList = range(2, 17);
        $shiftDifference        = 2 - $shipmentList[0]->getItemSequenceIndex();

        // Fix existing shipments
        for ($i = 0; $i < count($shipmentList); $i++) {
            /* @var $shipment \Pley\Entity\Profile\ProfileSubscriptionShipment */
            $shipment                 = $shipmentList[$i];
            $currentItemSequenceIndex = $shipment->getItemSequenceIndex();

            // Since we are shifting, whatever was purchased, now should be released
            $this->_subscriptionManager->freePurchasedItem($shipment->getSubscriptionId(), $currentItemSequenceIndex);
            
            // Now we start fixing the shipments starting from the first fixed sequence id
            $newItemSequenceIndex = $itemSequenceIdxFixList[$i];
            $shipment->setItemSequenceIndex($newItemSequenceIndex);
            
            // We don't save immediately as doing so could cause a break on the Unique contraint
            // due to an overlap of sequence indexes, so we just update the objects and after we
            // are done with the loop, we update the shipments in reverse order
            
            // Now that we updated the shipment, we also update the purchased inventory
            $this->_increasedPurchasedItem($newItemSequenceIndex);
        }
        
        // Now that all shipment objects have been adjusted, update them in reverse order to avoid
        // Unique constraint breaks
        for ($i = count($shipmentList) - 1; $i >= 0; $i--) {
            $this->_profileSubsShipDao->save($shipmentList[$i]);
        }
        
        // Freeing the respective reserved inventory based on the difference of shifted positions
        for ($i = 0; $i < $shiftDifference && count($itemSequenceQueue) >= $shiftDifference ; $i++) {
            /* @var $queueItem \Pley\Entity\Profile\QueueItem */
            $queueItem = array_shift($itemSequenceQueue);
            
            $this->_subscriptionManager->freeReservedItem($shipment->getSubscriptionId(), $queueItem->getSequenceIndex());
        }
        $profileSubs->setItemSequenceQueue($itemSequenceQueue);
        $this->_profileSubsDao->save($profileSubs);
    }
    
    /**
     * @param int $profileSubsId
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment[]
     */
    private function _getShipmentsRemainingForProfileSubscription($profileSubsId)
    {
        $sql = 'SELECT `id` FROM `profile_subscription_shipment` '
             . 'WHERE `item_id` IS NULL AND `subscription_id` = 1 AND `profile_subscription_id` = ? '
             . 'ORDER BY `id` ASC';
        
        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute([$profileSubsId]);
        
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $profileSubsShipmentList = [];
        foreach($resultSet as $dbRecord) {
            $profileSubsShipment       = $this->_profileSubsShipDao->find($dbRecord['id']);
            $profileSubsShipmentList[] = $profileSubsShipment;
        }
        
        return $profileSubsShipmentList;
    }
    
    private function _increasedPurchasedItem($itemSequenceIndex)
    {
        if (empty($this->_increasePurchaseItemStmt)) {
            $this->_increasedPurchasedItemStmt = $this->_dbManager->prepare(
                'UPDATE `subscription_item_sequence` '  .
                'SET `subscription_units_purchased` = `subscription_units_purchased` + 1 ' .
                'WHERE `id` = ? '
            );
        }
        
        $sequenceItem = $this->_subscriptionRepo->getSequenceItemByIndex(1, $itemSequenceIndex);
        
        $this->_increasedPurchasedItemStmt->execute([$sequenceItem->getId()]);
        $this->_increasedPurchasedItemStmt->closeCursor();
    }
}
