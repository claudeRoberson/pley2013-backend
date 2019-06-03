<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console\Tools;

use \Illuminate\Console\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Input\InputOption;

/**
 * The <kbd>SubscriptionItemSequenceRetrofeedCommand</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 */
class SubscriptionItemSequenceRetrofeedCommand extends Command
{
    use \Pley\Console\ConsoleOutputTrait;
    
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:RetrofeedBoxQueue';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'One-off Script to migrate from old shipment structure to Queue structure.';
    
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionManager;
    /** @var \Pley\User\UserSubscriptionManager */
    protected $_userSubscriptionManager;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        
        $this->_dbManager               = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_subscriptionManager     = \App::make('\Pley\Subscription\SubscriptionManager');
        $this->_userSubscriptionManager = \App::make('\Pley\User\UserSubscriptionManager');
        $this->_profileSubsDao          = \App::make('\Pley\Dao\Profile\ProfileSubscriptionDao');

        $this->_setLogOutput(true);
    }
    
    protected function getOptions()
    {
        return [
            [
                'subscriptionId',
                null,
                InputOption::VALUE_REQUIRED, 
                'Indicates the Subscription ID to be synchronized.'
            ],
        ];
    }
    
    public function fire()
    {
        $startTime = microtime(true);
        
        $subscriptionId = $this->input->getOption('subscriptionId');
        
        $that = $this;
        $this->_dbManager->transaction(function() use ($that, $subscriptionId) {
            $that->_processClosure($subscriptionId);
        });
        
        $endTime     = microtime(true);
        $elapsedTime = $endTime - $startTime;
        
        $this->line(sprintf('Elapsed Time: %.3f secs', $elapsedTime));
    }
    
    private function _processClosure($subscriptionId)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $profileSubsList  = $this->_getSubscriptions($subscriptionId);
        $itemSequenceList = $this->_getItemSequenceList($subscriptionId);
        
        foreach ($profileSubsList as $profileSubsData) {
            $this->_processSubscription($itemSequenceList, $profileSubsData, $subscriptionId);
        }
        
        $this->_updateItemSequenceList($itemSequenceList);
    }
    
    private function _getSubscriptions($subscriptionId)
    {
        $sql = 'SELECT `id`, `gift_id`, `status` FROM `profile_subscription`  '
             . 'WHERE `subscription_id` = ? ORDER BY `id`';
        $prepStmt = $this->_prepareStmt($sql);
        $prepStmt->execute([$subscriptionId]);
        
        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();
        
        return $resultSet;
    }
    
    /** @return __SISRC_SequenceItem[] */
    private function _getItemSequenceList($subscriptionId)
    {
        $sql = 'SELECT `id`, `sequence_index`, `subscription_units_programmed` FROM `subscription_item_sequence` '
             . 'WHERE `subscription_id` =  ? ORDER BY `sequence_index` '
             . 'FOR UPDATE';
        $prepStmt = $this->_prepareStmt($sql);
        $prepStmt->execute([$subscriptionId]);
        
        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount = $prepStmt->rowCount();
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = new __SISRC_SequenceItem($resultSet[$i]);
        }
        
        $prepStmt->closeCursor();
        return $resultSet;
    }
    
    /**
     * 
     * @param __SISRC_SequenceItem[] $itemSequenceList
     * @param array                  $profileSubsData
     * @param int                    $subscriptionId
     */
    private function _processSubscription($itemSequenceList, $profileSubsData, $subscriptionId)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $purchasedBoxCount = $this->_getBoxCount($profileSubsData, $subscriptionId);
        
        // If the value is false, it means it was probably one of those Forced Cancelled accounts
        // that we shipped no boxes to
        if ($purchasedBoxCount === false) {
            return;
        }
        
        $isGift = $profileSubsData['status'] == \Pley\Enum\SubscriptionStatusEnum::GIFT;
        
        $itemSequenceQueue = [];
        
        $userBoxProcessedIdx = 0;
        foreach ($itemSequenceList as $sequenceItem) {
            if (!$sequenceItem->hasUnitsLeft()) {
                continue;
            }
            
            $itemForQueue = new \Pley\Entity\Profile\QueueItem(
                $sequenceItem->getSequenceIdx(), \Pley\Entity\Profile\QueueItem::TYPE_RESERVED
            );
            
            // Check if it is a Purchased Box
            if ($userBoxProcessedIdx < $purchasedBoxCount) {
                $itemForQueue->setType(\Pley\Entity\Profile\QueueItem::TYPE_PURCHASED);
                $sequenceItem->increasePurchased();
             
            // If it is a gift, no future boxes are reserved.
            } else if ($isGift) {
                break;
                
            // Otherwise it is a Reserved Box
            } else {
                $sequenceItem->increaseReserved();
            }
            
            $itemSequenceQueue[] = $itemForQueue;
            $userBoxProcessedIdx++;
        }
        
        // Since we have already shipped the first box, for the first period if this subscription has
        // a shipment served, then we need to remove it from their queue.
        $profileSubsId = $profileSubsData['id'];
        $createdAt     = $this->_getShipmentCreatedAt($profileSubsId);
        if ($createdAt !== false) {
            array_shift($itemSequenceQueue);
        }
        
        $this->_updateSubscriptionQueue($profileSubsId, $itemSequenceQueue);
        $this->_addRemainingPaidShipments($profileSubsId, $createdAt);
    }
    
    private function _getBoxCount($profileSubsData, $subscriptionId)
    {
        $supportedStatusList = [
            \Pley\Enum\SubscriptionStatusEnum::ACTIVE, \Pley\Enum\SubscriptionStatusEnum::GIFT
        ];
        
        // Status not handled
        if (!in_array($profileSubsData['status'], $supportedStatusList)) {
            return false;
        }
        
        if ($profileSubsData['status'] == \Pley\Enum\SubscriptionStatusEnum::ACTIVE) {
            $paymentPlanId = $this->_getPaidPaymentPlan($profileSubsData);
        } else { //if ($profileSubsData['status'] == \Pley\Enum\SubscriptionStatusEnum::GIFT) {
            $paymentPlanId = $this->_getGiftPaymentPlan($profileSubsData);
        }
        
        $boxCount = $this->_subscriptionManager->getSubscriptionBoxCount($subscriptionId, $paymentPlanId);
        return $boxCount;
    }
    
    private function _getPaidPaymentPlan($profileSubsData)
    {
        $profileSubsId = $profileSubsData['id'];
        
        $sql = 'SELECT `payment_plan_id` FROM `profile_subscription_plan` '
             . 'WHERE `profile_subscription_id` = ?';
        $prepStmt = $this->_prepareStmt($sql);
        $prepStmt->execute([$profileSubsId]);
        
        $dbRecord = $prepStmt->fetch(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();

        $paymentPlanId = $dbRecord['payment_plan_id'];
        return $paymentPlanId;
    }
    
    private function _getGiftPaymentPlan($profileSubsData)
    {
        $giftId = $profileSubsData['gift_id'];
        
        $sql = 'SELECT `equivalent_payment_plan_id` FROM `gift_price` '
             . 'JOIN `gift` ON `gift`.`gift_price_id` = `gift_price`.`id` '
             . 'WHERE `gift`.`id` = ?';
        $prepStmt = $this->_prepareStmt($sql);
        $prepStmt->execute([$giftId]);
        
        $dbRecord = $prepStmt->fetch(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();

        $paymentPlanId = $dbRecord['equivalent_payment_plan_id'];
        return $paymentPlanId;
    }
    
    private function _getShipmentCreatedAt($profileSubsId)
    {
        $sql = 'SELECT `created_at` FROM `profile_subscription_shipment` WHERE `profile_subscription_id` = ?';
        $prepStmt = $this->_prepareStmt($sql);
        $prepStmt->execute([$profileSubsId]);
        $dbRecord = $prepStmt->fetch(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();
        
        if (empty($dbRecord)) {
            return false;
        }
        
        return $dbRecord['created_at'];
    }
    
    /**
     * 
     * @param int                              $profileSubsId
     * @param \Pley\Entity\Profile\QueueItem[] $itemSequenceQueue
     * @param int                              $createdAt
     */
    private function _addRemainingPaidShipments($profileSubsId, $createdAt)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $activePeriod = 1;
        $profileSubs  = $this->_profileSubsDao->find($profileSubsId);
        $subscription = $this->_subscriptionManager->getSubscription($profileSubs->getSubscriptionId());

        $this->_userSubscriptionManager->queueShipment($profileSubs, $subscription, $activePeriod);
        
        // If createdAt is supplied, it means that there was an existing shipment, and thus, the ones
        // just added were part of that initial creation, that we deleted so we could recreate shipments
        // accordingly, and we just need to make sure they all share the same creation date.
        if ($createdAt !== false) {
            $prepSql = 'UPDATE `profile_subscription_shipment` '
                     . 'SET `created_at` = ?, `updated_at` = 0 '
                     . 'WHERE `profile_subscription_id` = ? '
                     .   'AND `schedule_index` <> 0 ';
            
            $prepStmt = $this->_dbManager->prepare($prepSql);
            $prepStmt->execute([$createdAt, $profileSubsId]);
            $prepStmt->closeCursor();
        }
    }
    
    /**
     * 
     * @param int                              $profileSubsId
     * @param \Pley\Entity\Profile\QueueItem[] $itemSequenceQueue
     */
    private function _updateSubscriptionQueue($profileSubsId, $itemSequenceQueue)
    {
        $itemSequenceArray = [];
        foreach ($itemSequenceQueue as $queueItem) {
            $itemSequenceArray[] = $queueItem->toArray();
        }
        
        $jsonQueue = json_encode($itemSequenceArray);
        
        $sql = 'UPDATE `profile_subscription` '
             . 'SET `item_sequence_queue_json` = ? '
             . 'WHERE `id` = ?';
        $prepStmt = $this->_prepareStmt($sql);
        $prepStmt->execute([$jsonQueue, $profileSubsId]);
        $prepStmt->closeCursor();
    }
    
    /** @param __SISRC_SequenceItem[] $itemSequenceList */
    private function _updateItemSequenceList($itemSequenceList)
    {
        $sql = 'UPDATE `subscription_item_sequence` '
             . 'SET `subscription_units_purchased` = ?, `subscription_units_reserved` = ? '
             . 'WHERE `id` = ?';
        $prepStmt = $this->_prepareStmt($sql);
        
        foreach ($itemSequenceList as $sequenceItem) {
            $prepStmt->execute([
                $sequenceItem->getUnitsPurchased(),
                $sequenceItem->getUnitsReserved(),
                $sequenceItem->getId(),
            ]);
            $prepStmt->closeCursor();
        }
    }
    
    /** @return \PDOStatement */
    private function _prepareStmt($sql)
    {
        $key = md5($sql);
        
        if (!isset($this->_prepStmtCache[$key])) {
            $this->_prepStmtCache[$key] = $this->_dbManager->prepare($sql);
        }
        
        return $this->_prepStmtCache[$key];
    }
}

class __SISRC_SequenceItem
{
    private $_id;
    private $_sequenceIdx;
    private $_unitsProgrammed;
    private $_unitsPurchased;
    private $_unitsReserved;
    
    public function __construct($itemSequenceData)
    {
        $this->_id             = $itemSequenceData['id'];
        $this->_sequenceIdx    = $itemSequenceData['sequence_index'];
        $this->_unitsProgrammed = $itemSequenceData['subscription_units_programmed'];
        $this->_unitsPurchased = 0;
        $this->_unitsReserved  = 0;
    }
    
    /** @return int */
    public function getId()
    {
        return $this->_id;
    }
    
    /** @return int */
    public function getSequenceIdx()
    {
        return $this->_sequenceIdx;
    }

    /** @return boolean */
    public function hasUnitsLeft()
    {
        return ($this->_unitsProgrammed - ($this->_unitsPurchased + $this->_unitsReserved)) > 0;
    }
    
    /** @return int */
    public function getUnitsPurchased()
    {
        return $this->_unitsPurchased;
    }

    /** @return int */
    public function getUnitsReserved()
    {
        return $this->_unitsReserved;
    }
    
    public function increasePurchased()
    {
        $this->_unitsPurchased += 1;
    }
    
    public function increaseReserved()
    {
        $this->_unitsReserved += 1;
    }
}