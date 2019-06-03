<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console\Tools;

use \Illuminate\Console\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * The <kbd>DisneyInventorySynchronizerCommand</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package 
 * @subpackage
 */
class DisneyInventorySynchronizerCommand extends Command
{
    use \Pley\Console\ConsoleOutputTrait;
    
    const SUBSCRIPTION_ID = 1; //Disney subscription
    const QUEUE_MIN_START_SEQ_IDX = 2;
    
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:DisneyInventorySynchronizer';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Helper command to synchronize Shipments and user Queues with inventory (due to external manual changes)';
    
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /**
     * @var \Pley\Repository\User\UserRepository
     */
    protected $_userRepository;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionShipmentDao */
    protected $_profileSubsShipDao;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->_dbManager          = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_userRepository     = \App::make('\Pley\Repository\User\UserRepository');
        $this->_profileSubsDao     = \App::make('\Pley\Dao\Profile\ProfileSubscriptionDao');
        $this->_profileSubsShipDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionShipmentDao');


        $this->_setLogOutput(true);
    }
    
    public function fire()
    {
        $profileSubsIdList = $this->_getDisneySubscriptionsIdList();
        $sequenceItemMap   = $this->_getSequenceItemMap();
        $count = count($profileSubsIdList);
        
        if (!$this->confirm(sprintf('%d Users to process. Continue? [yes|no]', $count))) {
            $this->error('Operation has been aborted.');
            return;
        }
        
        $updatedIdList = [];
        $pp           = new \Pley\Console\Util\ProgressPrinter();
        foreach ($profileSubsIdList as $profileSubsId) {
            $isUpdated = $this->_processProfileSubscription($profileSubsId, $sequenceItemMap);
            if ($isUpdated) {
                $updatedIdList[] = $profileSubsId;
            }
            $pp->step();
        }
        $pp->finish();
        
        foreach ($updatedIdList as $updatedProfileSubsId) {
            $this->line('Profile queue updated for ID : ' . $updatedProfileSubsId);
        }
        $this->info('Total Updated : ' . count($updatedIdList));
        
        $this->_updateSequenceItem($sequenceItemMap);
    }
    
    private function _getDisneySubscriptionsIdList()
    {
        $sql = 'SELECT `id` FROM `profile_subscription` WHERE `subscription_id` = ?';
        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([self::SUBSCRIPTION_ID]);
        
        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $profileSubsIdList = [];
        foreach ($resultSet as $record) {
            $profileSubsIdList[] = $record['id'];
        }
        
        return $profileSubsIdList;
    }
    
    /** @return __DISC_SequenceItem[] */
    private function _getSequenceItemMap()
    {
        $sql = 'SELECT `id`, `sequence_index` '
             . 'FROM `subscription_item_sequence` WHERE `subscription_id` = ?';
        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([self::SUBSCRIPTION_ID]);
        
        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $seqItemMap = [];
        foreach ($resultSet as $record) {
            $seqItemMap[$record['sequence_index']] = new __DISC_SequenceItem($record['id'], $record['sequence_index']);
        }
        
        return $seqItemMap;
    }
    
    /**
     * @param int $profileSubscriptionId
     * @param __DISC_SequenceItem[] $sequenceItemMap
     */
    private function _processProfileSubscription($profileSubscriptionId, &$sequenceItemMap)
    {
        $isChangeSubs = false;
        
        $profileSubs = $this->_profileSubsDao->find($profileSubscriptionId);
        $lastShipmentItemSequence = 0;
        
        // Increasing the 
        $profileSubsShipmentList = $this->_profileSubsShipDao->findByProfileSubscription($profileSubscriptionId);
        foreach ($profileSubsShipmentList as $profileSubsShipment) {
            $itemSeqIdx = $profileSubsShipment->getItemSequenceIndex();
            
            /* @var $sequenceItem __DISC_SequenceItem */
            $sequenceItem = $sequenceItemMap[$itemSeqIdx];
            $sequenceItem->purchased++;
            
            if ($itemSeqIdx > $lastShipmentItemSequence) {
                $lastShipmentItemSequence = $itemSeqIdx;
            }
        }
        
        $currentItemSequenceQueue = $profileSubs->getItemSequenceQueue();
        
        // If the subscription is cancelled or is a gift type, just make sure that the queue is empty
        if ($profileSubs->getStatus() == \Pley\Enum\SubscriptionStatusEnum::CANCELLED
                || $profileSubs->getStatus() == \Pley\Enum\SubscriptionStatusEnum::GIFT) {
            $currentItemSequenceQueue = $profileSubs->getItemSequenceQueue();
            if (!empty($currentItemSequenceQueue)) {
                $isChangeSubs = true;
                $profileSubs->setItemSequenceQueue([]);
            }
            
        // Otherwise, we need to recreate the queue of reserved, with minimum sequence 
        } else {
            // lets get the next item that should be reserved
            $reserveFromSeqIdx = max([self::QUEUE_MIN_START_SEQ_IDX, $lastShipmentItemSequence + 1]);
            
            $newItemSequenceQueue = [];
            foreach ($sequenceItemMap as $sequenceItem) {
                // Ignore anything that is before first to reserve
                if ($sequenceItem->sequenceIndex < $reserveFromSeqIdx) {
                    continue;
                }
                
                // Add the item to the queue and increased the reserved amount
                $newItemSequenceQueue[] = new \Pley\Entity\Profile\QueueItem(
                    $sequenceItem->sequenceIndex, \Pley\Entity\Profile\QueueItem::TYPE_RESERVED
                );
                $sequenceItem->reserved++;
            }
            
            // If the queues are the same, ignore an update
            if ($currentItemSequenceQueue[0]->getSequenceIndex() != $newItemSequenceQueue[0]->getSequenceIndex()) {
                $isChangeSubs = true;
                $profileSubs->setItemSequenceQueue($newItemSequenceQueue);
            }
        }
        
        if ($isChangeSubs) {
            $this->_profileSubsDao->save($profileSubs);
            return true;
        }
        return false;
    }
    
    /** @var __DISC_SequenceItem */
    private function _updateSequenceItem($sequenceItemMap)
    {
        $sql = 'UPDATE `subscription_item_sequence` SET '
             .    '`subscription_units_purchased` = ?, '
             .    '`subscription_units_reserved` = ? '
             . 'WHERE `id` = ?';
        $prepStmt = $this->_dbManager->prepare($sql);
        
        $this->line('Updated Subscription Item Sequence');
        foreach ($sequenceItemMap as $sequenceItem) {
            $this->line(sprintf(
                "%d\t%d\t%d\t%d", 
                $sequenceItem->id, $sequenceItem->sequenceIndex, $sequenceItem->purchased, $sequenceItem->reserved
            ));
            
            $prepStmt->execute([
                $sequenceItem->purchased, 
                $sequenceItem->reserved,
                $sequenceItem->id,
            ]);
        }
        
    }
}

class __DISC_SequenceItem {
    public $id;
    public $sequenceIndex;
    public $purchased;
    public $reserved;
    
    public function __construct($id, $sequenceIndex)
    {
        $this->id            = $id;
        $this->sequenceIndex = $sequenceIndex;
        $this->purchased     = 0;
        $this->reserved      = 0;
    }


}