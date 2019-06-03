<?php

namespace Pley\Console\Tools;

/** @copyright Pley (c) 2017, All Rights Reserved */

use Illuminate\Console\Command;
use Pley\Entity\Profile\ProfileSubscription;
use Pley\Entity\Profile\QueueItem;
use Pley\Entity\Subscription\SequenceItem;
use Pley\Enum\Shipping\ShipmentStatusEnum;
use Pley\Util\DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The <kbd>ShiftDeadlineDisneyShipments</kbd>
 *asd
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ShiftDeadlineDisneyShipments extends Command
{
    use \Pley\Console\ConsoleOutputTrait;
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:shiftDeadlineDisneyShipments';
    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Shifts Rapunzel shipment, which was created after a deadline';

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
    /** @var \Pley\Dao\Profile\ProfileSubscriptionPlanDao */
    protected $_profileSubsPlanDao;

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
        $this->_profileSubsPlanDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionPlanDao');

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
            [
                'profileSubscriptionId',
                null,
                InputOption::VALUE_OPTIONAL,
                'Profile Subscription Id'
            ],
        ];
    }

    public function fire()
    {
        $this->info('Begin...');

        $dryRun = (bool)$this->input->getOption('dryRun');
        $amount = (int)$this->input->getOption('amount');
        $profileSubscriptionId = (int)$this->input->getOption('profileSubscriptionId');

        $profileSubscriptions = $this->_getProfileSubscriptionsToSchedule($amount, $profileSubscriptionId);
        $count = count($profileSubscriptions);

        if (!$this->confirm(sprintf('There is a %d subscriptions to process. Continue? [y\n]', $count))) {
            $this->error('Operation has been aborted.');
            return;
        }

        foreach ($profileSubscriptions as $profileSubscription) {
            $this->line(sprintf('Processing profile subscription ID: [%d]...', $profileSubscription->getId()));
            if($this->_shiftShipments($profileSubscription, $dryRun) === true){
                $this->_shiftStripePaymentSchedule($profileSubscription, $dryRun);
            }
            $this->line(sprintf('%d subscriptions left', --$count));
        }
        $this->info('COMPLETED SUCCESSFULLY!');
    }

    /**
     * @return \Pley\Entity\Profile\ProfileSubscription[]
     */
    protected function _getProfileSubscriptionsToSchedule($limit = 1, $profileSubscriptionId = null)
    {
        $profileSubscriptions = [];

        if ($profileSubscriptionId) {
            $sql = '
SELECT id FROM profile_subscription
WHERE id = ?;
         ';
            $pstmt = $this->_dbManager->prepare($sql);
            $pstmt->execute(
                [
                    $profileSubscriptionId
                ]);

        } else {
            $sql = '
SELECT id FROM profile_subscription
WHERE created_at >= "2017-07-14 23:59:59" AND created_at <= "2017-07-19 17:30:00" AND subscription_id = 1 LIMIT ?;
         ';
            $pstmt = $this->_dbManager->prepare($sql);
            $pstmt->execute(
                [
                    $limit
                ]);
        }

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($resultSet as $resItem) {
            $profileSubscriptions[] = $this->_profileSubsDao->find($resItem['id']);
        }
        return $profileSubscriptions;
    }

    protected function _shiftShipments(ProfileSubscription $profileSubscription, $dryRun = true)
    {
        $subscription = $this->_subscriptionManager->getSubscription($profileSubscription->getSubscriptionId());
        $subscriptionItemSequenceQueue = $profileSubscription->getItemSequenceQueue();
        $profileSubscriptionShipments = $this->_profileSubsShipDao->findByProfileSubscription($profileSubscription->getId());

        if (!count($profileSubscriptionShipments)) {
            $this->error('No shipments has been found. Exiting');
            return false;
        }
        if (empty($subscriptionItemSequenceQueue)) {
            $this->error('No Item sequence queue - possibly a gift. Exiting');
            return false;
        }
        $currentShipment = end($profileSubscriptionShipments);
        if($currentShipment->getScheduleIndex() !== 3){
            $this->error('First shipment is not Merida. Exiting.');
            return false;
        }
        $this->info('Shipments found: ' . count($profileSubscriptionShipments));

        foreach ($subscriptionItemSequenceQueue as $queueItem) {
            $queueItem->getType();
            $queueItem->getSequenceIndex();
            $prependQueueItem = new QueueItem($queueItem->getSequenceIndex() - 1, $queueItem->getType());
            break;
        }
        array_unshift($subscriptionItemSequenceQueue, $prependQueueItem);
        $this->info('Sequence queue prepended with:  ' . $prependQueueItem->getSequenceIndex() . '-' . $prependQueueItem->getType());


        if ($dryRun === false) {
            $this->info('Updating profile subscription...');

            $profileSubscription->setItemSequenceQueue($subscriptionItemSequenceQueue);
            $this->_profileSubsDao->save($profileSubscription);
            $itemSequence  = $this->_subscriptionRepo->getSequenceItemByIndex($subscription->getId(), 2);
            $rapunzelQueueItem = new QueueItem(2, 'P');

            $this->_subscriptionManager->increaseItemSale($itemSequence, $rapunzelQueueItem);

            $this->info('Updating shipments...');
            $profileSubscriptionShipments = array_reverse($profileSubscriptionShipments);
            foreach ($profileSubscriptionShipments as $profileSubscriptionShipment) {
                $profileSubscriptionShipment->setItemSequenceIndex($profileSubscriptionShipment->getItemSequenceIndex() - 1);
                $profileSubscriptionShipment->setScheduleIndex($profileSubscriptionShipment->getScheduleIndex() - 1);
                $this->_profileSubsShipDao->save($profileSubscriptionShipment);
                $this->info('Updated shipment ID: ' . $profileSubscriptionShipment->getId());
                $this->info('Item sequence index' . $profileSubscriptionShipment->getItemSequenceIndex());
                $this->info('Schedule index' . $profileSubscriptionShipment->getScheduleIndex());
            }
            $this->info('Shipments updated...');
            return true;
        } else {
            $this->error('Dry run. Exiting');
            return false;
        }
    }

    protected function _shiftStripePaymentSchedule(ProfileSubscription $profileSubscription, $dryRun = true)
    {

        $subscription = $this->_subscriptionManager->getSubscription($profileSubscription->getSubscriptionId());
        $subscriptionPlan = $this->_profileSubsPlanDao->findLastByProfileSubscription($profileSubscription->getId());
        $stripeSubscription = \Stripe\Subscription::retrieve($subscriptionPlan->getVPaymentSubscriptionId());
        $paymentPlan = $this->_getPaymentPlan($subscriptionPlan->getPaymentPlanId());
        //$itemSequence  = $this->_subscriptionManager->getItemSequenceForProfileSubscription($subscription, $profileSubscription);
        $itemSequence  = $this->_subscriptionManager->getItemSequence($subscription);

        $trialEnd = $stripeSubscription->trial_end;
        $nextCharge = $this->_userSubscriptionMgr->getFirstRecurringChargeDate($subscription, $paymentPlan, $itemSequence);

        $this->info('Stripe current trial end ' . $trialEnd);
        $this->info('Stripe new charge date ' . $nextCharge);
        if ($dryRun === true) {
            $this->error('Dry run. Exiting');
        }else{
            if ($nextCharge != $trialEnd) {
                $stripeSubscription->trial_end = $nextCharge;
                $stripeSubscription->save();
                $this->info('Stripe charge date updated to ' . $nextCharge);
            }
        }
        return;
    }

    /**
     * Returns the PaymentPlan object for the supplied PlanId
     * @param int $planId
     * @return \Pley\Entity\Payment\PaymentPlan
     */
    private function _getPaymentPlan($planId)
    {
        if (!isset($this->_paymentPlanCache)) {
            $this->_paymentPlanCache = [];
        }

        if (!isset($this->_paymentPlanCache[$planId])) {
            $this->_paymentPlanCache[$planId] = $this->_paymentPlanDao->find($planId);
        }

        return $this->_paymentPlanCache[$planId];
    }
}