<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Console\Shipment;

use \Illuminate\Console\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Input\InputOption;

use \Pley\System\Process\SystemProcessEnum;

/** ♰
 * The <kbd>LabelPurchaserCommand</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class LabelPurchaserCommand extends Command
{
    use \Pley\Console\ConsoleOutputTrait;
    
    /**
     * Constant used to put a Pseudo-Lock on the DB, to prevent two processes from working
     * on the same shipment at the same time.
     * @var int
     */
    private static $LABEL_LEASE_TIME = 300; // 5 mins = 300 secs
    /**
     * Time used by Parent process to wait between spawning Child processes, if all children processes
     * are spawned at the same time, they almost immediately get locked by the DB trying to retrieve
     * the most recent eligible Checkout, so this time allows us to reduce the number of processes
     * that will get locked retrieving such record
     * @var int Time in Milliseconds
     */
    private static $TIME_BETWEEN_CHILD_SPAWNING_MILLI = 500; // 0.5 secs
    
    private static $WAREHOUSE_UTC_OFFSET_SECS = -18000; // KY warehouse is UTC-5 (5hrs time diff)
    
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:labelPurchase';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Cronjob to Purchase Labels for Shipments.';
    
    /**
     * The input interface implementation.
     * <p>Re-declaring the variable to help with autocomplete as we are Decorating the Laravel's
     * <kbd>\Symfony\Component\Console\Input\InputInterface</kbd> input object to get access to
     * extra visibility.
     * @var \Pley\Laravel\Console\Input\VisibilityInputDecorator
     */
    protected $input;
    
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionManager;
    /** @var \Pley\User\UserSubscriptionManager */
    protected $_userSubscriptionManager;
    /** @var \Pley\Shipping\AbstractShipmentManager */
    protected $_shipmentManager;
    /** @var \Pley\Dao\User\UserProfileDao */
    protected $_userProfileDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionShipmentDao */
    protected $_profileSubsShipmentDao;
    
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->input  = new \Pley\Laravel\Console\Input\VisibilityInputDecorator($input);
        $this->output = $output;

        return parent::run($this->input, $this->output);
    }
    
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        
        $parentRunId  = $this->input->getOption('parentRunId');
        if ($parentRunId) {
            $this->_runId = $parentRunId;
        }
        
        $this->_dbManager               = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_subscriptionManager     = \App::make('\Pley\Subscription\SubscriptionManager');
        $this->_userSubscriptionManager = \App::make('\Pley\User\UserSubscriptionManager');
        $this->_shipmentManager         = \App::make('\Pley\Shipping\AbstractShipmentManager');
        $this->_userProfileDao          = \App::make('\Pley\Dao\User\UserProfileDao');
        $this->_profileSubsDao          = \App::make('\Pley\Dao\Profile\ProfileSubscriptionDao');
        $this->_profileSubsShipmentDao  = \App::make('\Pley\Dao\Profile\ProfileSubscriptionShipmentDao');
        
        \LogHelper::ignoreHandlersEmptyContextAndExtra();
        
        $this->_setLogOutput(true);
    }
    
    protected function getOptions()
    {
        return [
            [
                'subscriptionId',
                null,
                InputOption::VALUE_REQUIRED, 
                'Indicates the Subscription ID to be processed for Batch Label purchasing.'
            ],
            [
                'process-count',
                null,
                InputOption::VALUE_OPTIONAL, 
                'Indicates the number of subprocesses to spawn to purchase labels for this run.',
                10
            ],
            [
                // Flag used to determine whether to print the base information when launching the
                // script in case of the timelines not met.
                // Since the script has to be sceduled to be run consistently, however labels cannot
                // be purchased until the Subscription deadline is met, if run manually, we want to
                // be notified of this, but if run as a cronjob, we don't want to saturate logs
                'cron',
                null,
                InputOption::VALUE_OPTIONAL, 
                'Indicates that this process is ran as a cronjob.',
            ],
            [
                'childNo',
                null,
                InputOption::VALUE_OPTIONAL, 
                'Indicates Child Number supplied by the Parent Process.',
            ],
            [
                'childPid',
                null,
                InputOption::VALUE_OPTIONAL, 
                // This is different from calling `getmypid()`, because the child process is a 
                // System call to trigger a new PHP process, which in turn triggers a new process
                // to run the Laravel Framework for the child process command
                'Indicates PID given to the parent for this Child Process.',
            ],
            [
                'parentRunId',
                null,
                InputOption::VALUE_OPTIONAL, 
                'Indicates Run ID of the parent for this Child Process.',
            ],
        ];
    }
    
    public function fire()
    {
        $startTime = microtime(true);
        
        // Adjusting server time to warehouse time so that labels are printed in relation to the 
        // warehouse location timezone that will process it.
        $now = time() + static::$WAREHOUSE_UTC_OFFSET_SECS; 

        $subscriptionId = $this->input->getOption('subscriptionId');
        $isCron         = $this->input->isUserOption('cron');
        $isChildProcess = $this->input->isUserOption('childNo');
        
        // If this process is a child process, no need to proceed to the code that checks if we can
        // go ahead and print labels, that has already been decided by the parent process, so
        // just proceed to start processing.
        if ($isChildProcess) {
            $this->_processor($subscriptionId);
            return;
        }
        
        $subscription = $this->_subscriptionManager->getSubscription($subscriptionId);
        
        // Active shippable period is always the period before the Active period for subscriptions
        // Only exception being Period 0 where both match.
        $activeShippablePeriodIndex = $this->_subscriptionManager->getActiveShippablePeriodIndex($subscription);
        $activePeriodIndex          = $this->_subscriptionManager->getActivePeriodIndex($subscription);
        
        $periodDefGrp = $this->_subscriptionManager->getSubscriptionDates($subscription);
        $deadlineTime = $periodDefGrp->getDeadlinePeriodDef()->getTimestamp();
        
        // We can always purchase labels for the shippable period for the exception of the very first
        // one where Active and Shippable periods are the same, so we have to wait for the deadline
        // to be met.
        if ($activePeriodIndex == 0 && $now < $deadlineTime) {
            $this->line('Subscription to process : ' . $subscription->getName());
            $this->line('+ Very first period hasn\'t reached the deadline yet of ' .  date('Y/m/d', $deadlineTime));
            return;
        }
        
        $eligibleCount = $this->_getEligibleCount($subscriptionId, $activeShippablePeriodIndex);
        // If there is nothing to process, just finish, and only notify if not running as Cronjob
        if ($eligibleCount == 0) {
            if (!$isCron) {
                $this->line('Subscription to process : ' . $subscription->getName());
                $this->line('No Eligible Shipments to process');
            }
            return;
        }
        
        $this->line('Subscription to process : ' . $subscription->getName());
        $this->line(sprintf(
            '+ Processing for Shipping Period Index %d, until %s', $activeShippablePeriodIndex, date('Y/m/d', $deadlineTime)
        ));
        
        // Now we know that at this moment, we are the Parent process, have items to process and
        // are in the valid purchase range, so just select whether we directly execute or multi process
        $isMultiProcess = $this->input->isUserOption('process-count');
        if ($isMultiProcess) {
            $processCount = $this->input->getOption('process-count');
            
            if ($eligibleCount < $processCount) {
                $processCount = $eligibleCount;
            }
            
            $this->_subprocessSpawner($subscriptionId, $processCount);
        } else {
            $this->_processor($subscriptionId);
        }
 
        $endTime     = microtime(true);
        $elapsedTime = $endTime - $startTime;
        $this->line(sprintf('Elapsed Time: %.3f secs', $elapsedTime));
    }
    
    private function _getEligibleCount($subscriptionId, $activeShippablePeriodIndex)
    {
        $availableSql = 'SELECT COUNT(*) AS `count` FROM `profile_subscription_shipment` '
                      . 'WHERE `subscription_id` = ? '
                      .   'AND `label_url` IS NULL '
                      .   'AND `schedule_index` = ? ';
        $availablePstmt = $this->_dbManager->prepare($availableSql);
        $availablePstmt->execute([$subscriptionId, $activeShippablePeriodIndex]);
        $dbRecord = $availablePstmt->fetch(\PDO::FETCH_ASSOC);
        $availablePstmt->closeCursor();
        
        $count = $dbRecord['count'];
        return $count;
    }
    
    private function _subprocessSpawner($subscriptionId, $processCount)
    {
        $this->line("Spawning {$processCount} Children process");
        
        for ($i = 0; $i < $processCount; $i++) {
            $childNo = $i + 1;
            
            // Lets wait for a few moments between new process.
            // This allows us to reduce the changes of immediate DB locking between child processes
            // when trying to retrieve the most recent eligible shipment, which leads to delays, in
            // some ocassions to just not find an eligible one (because the connection was busy),
            // or simply, entering a Deadlock when at least two processes tried to get a lock at 
            // the same time and one fails to get it.
            usleep(self::$TIME_BETWEEN_CHILD_SPAWNING_MILLI * 1000);
            
            $pid = pcntl_fork();
            
            if ($pid == SystemProcessEnum::FORK_PID_FAILED) {
                $this->error('Could not create children processes.');
                die('Could not fork');
                
            } else if ($pid == SystemProcessEnum::FORK_PID_CHILD) {
                $phpPath     = exec('which php');
                $artisanPath = dirname(app_path()) . '/artisan';

                $childNoArg        = sprintf('--childNo=%d', $childNo);
                $childPidArg       = sprintf('--childPid=%d', getmypid());
                $parentRunIdArg    = sprintf('--parentRunId=%d', $this->_runId);
                $subscriptionIdArg = sprintf('--subscriptionId=%d', $subscriptionId);
                
                pcntl_exec($phpPath, [
                    $artisanPath, $this->name, '--cron', $childNoArg, $childPidArg, $parentRunIdArg, $subscriptionIdArg
                ]);
                
                // ----- IMPORTANT -----------------------------------------------------------------
                // Though with the use of `pcntl_exec` it is guaranteed that the process will completely
                // be replaced with the new execution and thus never reaching this point, the only
                // way it would reach this point is if `pcntl_exec` fails to spawn the process and thus
                // returning false and continuing with this code, so, just exit for safe measure to
                // avoid children trying to wait on siblings.
                exit();
            
            // This else is for the Parent Process to handle this Child Fork creation iteration
            } else {
                $this->_setLogOutput(false);
                $this->line(sprintf(
                    "Spawned children process [%2d out of %d] [PID: %d]", ($i+1), $processCount, $pid
                ));
                $this->_setLogOutput(true);
            }
        }
    
        // Since the Children process exits as part of the Fork loop, it is guaranteed that this
        // while loop will only be executed by the parent.
        $status = null;
        while (($childPID = pcntl_waitpid(SystemProcessEnum::PID_WAIT_GROUP_ID, $status)) != -1) {
            $existStatus = pcntl_wexitstatus($status);
            
            if ($existStatus != SystemProcessEnum::EXIT_STATUS_SUCCESS) {
                $this->error("Child {$childPID} failed with status `{$existStatus}`");
            }
        }
    }
    
    private function _processor($subscriptionId)
    {
        $childNo  = $this->input->getOption('childNo');
        $childPID = $this->input->getOption('childPid');
        
        $subscription               = $this->_subscriptionManager->getSubscription($subscriptionId);
        $activeShippablePeriodIndex = $this->_subscriptionManager->getActiveShippablePeriodIndex($subscription);
        
        while (($profileSubsShipment = $this->_getEligibleShipment($subscriptionId, $activeShippablePeriodIndex)) != null) {
            try {
                $this->_purchaseShipmentLabel($profileSubsShipment);
                $this->_userSubscriptionManager->reactivateProfileSubscription($profileSubsShipment);
            } catch (\Exception $ex) {
                $errMsg = sprintf('Problem purchasing label for Shipment %d, err: %s', $profileSubsShipment->getId(), $ex->getMessage());
                $this->_childError($childPID, $childNo, $errMsg);
                continue;
            }
            $this->_setShirtSize($profileSubsShipment);

            $this->_profileSubsShipmentDao->save($profileSubsShipment);
            
            $this->_childLine($childPID, $childNo, 'Labels added to Shipment ' . $profileSubsShipment->getId());
        }
    }
    
    /**
     * Gets the next eligible Shipment for the supplied Subscription and Shippable Period
     * @param int $subscriptionId
     * @param int $activeShippablePeriodIndex
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment
     */
    private function _getEligibleShipment($subscriptionId, $activeShippablePeriodIndex)
    {
        $that            = $this;
        $profileShipment = false;
        
        do {
            // Due to how MySQL `REPEATABLE READ` mechanism works within transactions, everytime
            // we want to find a new eligible shipment, it has to be done on a new transaction, or
            // it will keep retrieving the same record once it read it for the first time and perhaps
            // a separate process updated it and thus entering an infinite loop.
            $profileShipment = $this->_dbManager->transaction(function() use ($that, $subscriptionId, $activeShippablePeriodIndex) {
                $that->_syncLock();

                // If there are no more potential shipments, we return null, to avoid an infinite loop
                $profileShipmentId = $that->_findPotentialShipmentId($subscriptionId, $activeShippablePeriodIndex);
                if ($profileShipmentId == null) {
                    return null;
                }

                // Now that we collected an ID, we can proceed to lock it to check and put the lease
                // We lock now because we don't know if there is a external process that is trying
                // to lock the same record and modify it, and is happening right after we retrieved the ID
                $profileShipment = $that->_profileSubsShipmentDao->lockFind($profileShipmentId);
                $hasLabel        = !empty($profileShipment->getLabelUrl());
                $isValidLease    = empty($profileShipment->getLabelLease()) || $profileShipment->getLabelLease() < time();

                if ($hasLabel || !$isValidLease) {
                    return false;
                }

                // Now that we know it doesn't have labels and doesn't have an active lease, we can claim it
                $profileShipment->setLabelLease(time() + self::$LABEL_LEASE_TIME);
                $that->_profileSubsShipmentDao->save($profileShipment);

                // Now that we have a potential shipment, and we have updated the lease, we need
                // to make one more check to see if the Profile Subscription has an assigned Address
                // so that we can continue. (The reason being, because with the separate step registration
                // process, it is possible that a subscription is created, but has user has not
                // yet added their address, and as such we wouldn't be able to purchase a label,
                // so we want to skip it after setting the lease, to avoid retrying it over and over)
                $profileSubs = $that->_profileSubsDao->find($profileShipment->getProfileSubscriptionId());
                if (empty($profileSubs->getUserAddressId())) {
                    return false;
                }
                
                // Note, though you see a return here, this return will just finish the function in
                // the transaction, not the `_getEligibleShipment()` method.
                return $profileShipment;
            });
        } while ($profileShipment === false);
        
        return $profileShipment;
    }
    
    /**
     * Performs a Lock query on the sync table to prevent multiple processes from trying to collect
     * the same shipment due to a race condition.
     * <p>It also prevents Dead-Locks as it is a single locking location.</p>
     */
    private function _syncLock()
    {
        // Checking for transaction since we are going to perform a `FOR UPDATE` statement
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $syncSql = 'SELECT `id` FROM `sync_locks` WHERE `id` = ? FOR UPDATE';
        $prepSql = $this->_dbManager->prepare($syncSql);
        $prepSql->execute([\Pley\Enum\DbSyncLockEnum::SHIPMENT_LABEL_PURCHASE]);
        $prepSql->closeCursor();
    }
    
    /**
     * Queries for the next potential eligible profile shipment.
     * <p>It is called potential, because this instruction is not locking to avoid any potential
     * DEAD-LOCKS due to table full-scans, however, other unrelated processes outside this one, could
     * potentially have a lock on the row we'll retrieve and modify it before it is noticed here,
     * so, the retrieved ID has to still be checked.</p>
     * @param int $subscriptionId
     * @param int $activeShippablePeriodIndex
     * @return int|null
     */
    private function _findPotentialShipmentId($subscriptionId, $activeShippablePeriodIndex)
    {
        $findSql = 'SELECT `id` FROM `profile_subscription_shipment` '
                 . 'WHERE `subscription_id` = ? '
                 .   'AND `schedule_index` = ? '
                 .   'AND `label_url` IS NULL '
                 .   'AND (`label_lease` IS NULL OR `label_lease` < ?) '
                 . 'ORDER BY `id` ASC LIMIT 1';
        $findPstmt = $this->_dbManager->prepare($findSql);
        
        $bindings = [$subscriptionId, $activeShippablePeriodIndex, time()];
        $findPstmt->execute($bindings);

        // If there are no records, then just exit the loop and the transaction.
        if ($findPstmt->rowCount() == 0) {
            return null;
        }

        $dbRecord          = $findPstmt->fetch(\PDO::FETCH_ASSOC);
        $profileShipmentId = $dbRecord['id'];
        $findPstmt->closeCursor();
        
        return $profileShipmentId;
    }
    
    /** ♰
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubsShipment
     */
    private function _purchaseShipmentLabel(\Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubsShipment)
    {
        $this->_setItem($profileSubsShipment);
        $shipment = $this->_shipmentManager->createShipment($profileSubsShipment);
        $label    = $this->_shipmentManager->purchaseLabel($shipment);
        
        $profileSubsShipment->setLabel($shipment, $label);
    }
    
    /**
     * Grab the item assigned to the Sequence Index we need to ship to lock down the item for the shipment.
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubsShipment
     */
    private function _setItem(\Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubsShipment)
    {
        $findSql = 'SELECT `item_id` FROM `subscription_item_sequence` '
                 . 'WHERE `subscription_id` = ? '
                 .   'AND `sequence_index` = ?';
        $findPstmt = $this->_dbManager->prepare($findSql);
        $bindings = [$profileSubsShipment->getSubscriptionId(), $profileSubsShipment->getItemSequenceIndex()];
        
        $findPstmt->execute($bindings);
        $dbRecord = $findPstmt->fetch(\PDO::FETCH_ASSOC);
        $itemId   = $dbRecord['item_id'];
        $findPstmt->closeCursor();
        
        $profileSubsShipment->setItemId($itemId);
    }
    
    /** ♰
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubsShipment
     */
    private function _setShirtSize(\Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubsShipment)
    {
        $userProfile = $this->_userProfileDao->find($profileSubsShipment->getProfileId());
        $profileSubsShipment->setShirtSize($userProfile->getTypeShirtSizeId());
    }
    
    // ---------------------------------------------------------------------------------------------
    // Helper printing line methods for children processes
    
    private function _childLine($pid, $childNo, $message)
    {
        $prefix = $this->_childLogPrefix($pid, $childNo);
        $this->line(sprintf('%s%s', $prefix, $message));
    }
    
    private function _childError($pid, $childNo, $message)
    {
        $prefix = $this->_childLogPrefix($pid, $childNo);
        $this->error(sprintf('%s%s', $prefix, $message));
    }
    
    private function _childLogPrefix($pid, $childNo)
    {
        $pidMessage = '';
        if (isset($pid)) {
            $pidMessage = "[PID: {$pid}]";
        }
        
        $childNoMessage = '';
        if (isset($childNo)) {
            $childNoMessage = sprintf('[Child#: %2d]', $childNo);
        }
        
        $prefix = '';
        if (!empty($pidMessage) || !empty($childNoMessage)) {
            $prefix = sprintf('%s%s ', $pidMessage, $childNoMessage);
        }
        
        return $prefix;
    }
}
