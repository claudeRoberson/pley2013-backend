<?php

namespace Pley\Console\Tools;

/** @copyright Pley (c) 2017, All Rights Reserved */

use Illuminate\Console\Command;
use Pley\Entity\Profile\ProfileSubscription;
use Pley\Enum\Shipping\ShipmentStatusEnum;
use Pley\Util\DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The <kbd>GrantCreditToSubscribersCommand</kbd>
 *asd
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ScheduleMissingDisneyShipments extends Command
{
    use \Pley\Console\ConsoleOutputTrait;
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:scheduleMissingDisneyShipments';
    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Schedules missing Disney shipments which happened because of invalid shippable period';

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
    /** @var \Pley\Dao\Profile\ProfileSubscriptionTransactionDao */
    protected $_profileSubsTransacDao;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        parent::initialize($input, $output);

        $this->_dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_profileSubsDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionDao');
        $this->_profileSubsShipDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionShipmentDao');
        $this->_paymentPlanDao = \App::make('\Pley\Dao\Payment\PaymentPlanDao');
        $this->_subscriptionManager = \App::make('\Pley\Subscription\SubscriptionManager');
        $this->_userSubscriptionMgr = \App::make('\Pley\User\UserSubscriptionManager');
        $this->_subscriptionRepo = \App::make('\Pley\Repository\Subscription\SubscriptionRepository');
        $this->_profileSubsTransacDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionTransactionDao');

        $this->_setLogOutput(true);
    }

    protected function getOptions()
    {
        return [
            [
                'dryRun',
                null,
                InputOption::VALUE_REQUIRED,
                'Is Dry Run'
            ],
            [
                'amount',
                null,
                InputOption::VALUE_REQUIRED,
                'Amount'
            ],
        ];
    }

    public function fire()
    {
        $this->info('Begin...');

        $dryRun = (bool)$this->input->getOption('dryRun');
        $amount = (int)$this->input->getOption('amount');

        $profileSubscriptions = $this->_getProfileSubscriptionsToSchedule($amount);
        $count = count($profileSubscriptions);

        if (!$this->confirm(sprintf('There is a %d subscriptions to process. Continue? [y\n]', $count))) {
            $this->error('Operation has been aborted.');
            return;
        }

        foreach ($profileSubscriptions as $profileSubscription) {
            $this->line(sprintf('Processing profile subscription ID: [%d]...', $profileSubscription->getId()));
            $this->_scheduleShipment($profileSubscription, $dryRun);
            $this->line(sprintf('%d users left', --$count));
        }
        $this->info('COMPLETED SUCCESSFULLY!');
    }

    /**
     * @return \Pley\Entity\Profile\ProfileSubscription[]
     */
    protected function _getProfileSubscriptionsToSchedule($limit = 1)
    {
        $profileSubscriptions = [];

        $sql = '
SELECT id
FROM profile_subscription
WHERE id NOT IN
      (SELECT profile_subscription_id
       FROM profile_subscription_shipment
       WHERE subscription_id = 1
             AND schedule_index = 2
             AND status IN (1, 2)) AND subscription_id = 1 AND status IN (1,4) LIMIT ?;

        ';
        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute(
            [
                $limit
            ]);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($resultSet as $resItem) {
            $profileSubscriptions[] = $this->_profileSubsDao->find($resItem['id']);
        }
        return $profileSubscriptions;
    }

    protected function _scheduleShipment(ProfileSubscription $profileSubscription, $dryRun = true)
    {
        $subscription = $this->_subscriptionManager->getSubscription($profileSubscription->getSubscriptionId());
        $allTransactions = $this->_profileSubsTransacDao->findByProfileSubscription($profileSubscription->getId());
        $lastTransaction = $this->_profileSubsTransacDao->findByLastCharge($profileSubscription->getId());

        if(!count($allTransactions)){
            $this->line('No transactions found. Skipping');
            return;
        }
        $this->line('TRANSACTIONS INFO' . count($allTransactions));

        $this->line('Total transactions: ' . count($allTransactions));
        $this->line('Last user transaction was:');
        $this->line('Last Amount: ' . $lastTransaction->getAmount());
        $this->line('Last Status: ' . $lastTransaction->getTransactionType());

        $this->line('Last Date: ' . DateTime::date($lastTransaction->getTransactionAt()));

        $this->line('Active Period: ' . $this->_subscriptionManager->getActivePeriodIndex($subscription));

        if(count($allTransactions) > 1){
            $this->info('Valid for processing...');
        }else{
            $this->error('Only one transaction! Skipping');
            return;
        }

        if($dryRun === false){
            $this->info('Creating shipment...');
            list ($nextQueueItem, $shipmentCreated) = $this->_userSubscriptionMgr->queueShipment(
                $profileSubscription,
                $subscription,
                $this->_subscriptionManager->getActivePeriodIndex($subscription)
            );
            if($shipmentCreated){
                $this->info('SHIPMENT CREATED ID: '. $shipmentCreated->getId());
            }
        }else{
            $this->error('Dry run. Exiting');
        }
    }
}