<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace Pley\Console\Tools;

use \Illuminate\Console\Command;
use Pley\Entity\Profile\ProfileSubscription;
use Pley\Enum\SubscriptionStatusEnum;
use Pley\Subscription\SubscriptionPeriodIterator;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Input\InputOption;
use Pley\Dao\Profile\ProfileSubscriptionDao;
use Pley\Dao\Profile\ProfileSubscriptionPlanDao;
use Pley\Dao\Profile\ProfileSubscriptionTransactionDao;
use Pley\Subscription\SubscriptionManager;
use Pley\Entity\Profile\ProfileSubscriptionTransaction;
use Pley\Repository\User\UserRepository;

/**
 * The <kbd>PleyBoxMembersCommand</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class RefundNatGeoCommand extends Command
{

    use \Pley\Console\ConsoleOutputTrait;

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:refund-nat-geo-after-everglades';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Command to refund users, which bought more than 6 boxes of NatGeo';

    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;
    /**
     * @var  $_paymentPlanDao \Pley\Dao\Payment\PaymentPlanDao
     */
    protected $_paymentPlanDao;
    /**
     * @var ProfileSubscriptionDao
     */
    protected $_profileSubscriptionDao;

    /**
     * @var ProfileSubscriptionTransactionDao
     */
    protected $_profileSubscriptionTransactionDao;

    /**
     * @var ProfileSubscriptionPlanDao
     */
    protected $_profileSubscriptionPlanDao;
    /**
     * @var \Pley\Dao\Profile\ProfileSubscriptionShipmentDao
     */
    protected $_profileSubsShipDao;
    /**
     * @var SubscriptionManager
     */
    protected $_subscriptionManager;
    /**
     * @var \Pley\User\UserSubscriptionManager
     */
    protected $_userSubscriptionMgr;

    /**
     * @var UserRepository
     */
    protected $_userRepository;


    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->_config = \App::make('\Pley\Config\ConfigInterface');
        $this->_dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_mail = \App::make('\Pley\Mail\AbstractMail');
        $this->_paymentPlanDao = \App::make('\Pley\Dao\Payment\PaymentPlanDao');
        $this->_profileSubscriptionDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionDao');
        $this->_profileSubscriptionTransactionDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionTransactionDao');
        $this->_profileSubscriptionPlanDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionPlanDao');
        $this->_profileSubsShipDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionShipmentDao');
        $this->_subscriptionManager = \App::make('\Pley\Subscription\SubscriptionManager');
        $this->_userRepository = \App::make('\Pley\Repository\User\UserRepository');
        $this->_userSubscriptionMgr = \App::make('\Pley\User\UserSubscriptionManager');
    }

    public function fire()
    {
        $this->info('Processing refunds for NatGeo customers, who purchased a subscription for more that 6 months');
        $refundsSummary = $this->_getNatGeoRefundSummary();

        if (!$refundsSummary) {
            $this->info('No subscriptions to process.');
            return;
        }

        if (!$this->option('action')) {
            $this->error('Please provide an --action=[info|refund-step|refund-all] option');
        }

        switch ($this->option('action')) {
            case 'info':
                $this->_outputRefundsSummaryInfo($refundsSummary);
                break;
            case 'refund':
                $this->_processRefunds($refundsSummary);
                break;
        }
    }

    protected function _getNatGeoRefundSummary()
    {
        $sql = "
        SELECT
  pss.user_id AS user_id,
  u.email AS user_email,
  psp.payment_plan_id                                          AS payment_plan_id,
    pp.period AS plan_boxes_num,
  pss.profile_subscription_id AS profile_subscription_id,
  MIN(pss.schedule_index - 1) AS everglades_ship_period,
  COUNT(*)                                                     AS boxes_after_everglades_to_refund,
  pst.amount AS last_transaction_amount,
  (pst.amount / pp.period) * COUNT(*)  AS amount_to_refund,
  psp.v_payment_system_id AS payment_system,
    psp.v_payment_subscription_id AS payment_system_subscription_id,
  min(pss.created_at)                                          AS most_recent_payment_at,
  psp.created_at                                               AS subscription_created_at
FROM profile_subscription_shipment pss
  JOIN user u ON u.id = pss.user_id
  JOIN profile_subscription_plan psp ON psp.profile_subscription_id = pss.profile_subscription_id
  JOIN profile_subscription_transaction pst ON pss.shipment_source_id = pst.id
  JOIN payment_plan pp ON psp.payment_plan_id = pp.id

WHERE pss.subscription_id = 2 AND pss.status = 1 AND pss.item_sequence_index >= 6
GROUP BY pss.profile_subscription_id;
        ";

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute();

        $dbRecord = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();
        return $dbRecord;
    }

    protected function _outputRefundsSummaryInfo($refundsSummary)
    {
        $header = [
            'User ID',
            'Email',
            'Payment Plan',
            'Plan Boxes #',
            'Profile Sub ID',
            'Everglades Ship Period',
            'Boxes After Everglades',
            'Last TXN AMOUNT',
            'Amount to Refund',
            'Payment System',
            'PSP Sub ID',
            'Recent Payment',
            'Subscription Created'
        ];

        $totals = new \stdClass();
        $totals->totalSubscriptions = 0;
        $totals->paypalTotalSubscriptions = 0;
        $totals->stripeTotalSubscriptions = 0;
        $totals->totalRefundDue = 0;

        foreach ($refundsSummary as &$refundSummaryRow) {

            $paymentPlan = $this->_paymentPlanDao->find($refundSummaryRow['payment_plan_id']);
            $refundSummaryRow['amount_to_refund'] = round($refundSummaryRow['amount_to_refund'], 2);
            $refundSummaryRow['payment_plan_id'] = $paymentPlan->getTitle();
            $refundSummaryRow['payment_system'] = ($refundSummaryRow['payment_system'] == 1) ? 'Stripe' : 'Paypal';

            $totals->totalSubscriptions++;
            $totals->totalRefundDue += $refundSummaryRow['amount_to_refund'];
            ($refundSummaryRow['payment_system'] == 'Stripe') ? $totals->stripeTotalSubscriptions++ : $totals->paypalTotalSubscriptions++;
        }

        $this->table($header, $refundsSummary);

        $this->info('Total subscriptions: ' . $totals->totalSubscriptions);
        $this->info('PayPal: ' . $totals->paypalTotalSubscriptions);
        $this->info('Stripe: ' . $totals->stripeTotalSubscriptions);
        $this->info('Total Refund Due: ' . $totals->totalRefundDue);

        return;
    }

    protected function _processRefunds($refundsSummary)
    {
        $this->info('This operation will make a refund and also remove all shipments after Everglades. It\'s irreversible!');
        if ($this->confirm('Do you wish to continue? [yes|no]')) {
            foreach ($refundsSummary as &$refundSummaryRow) {
                $this->line('Processing subscription:');
                $refundSummaryRow['amount_to_refund'] = round($refundSummaryRow['amount_to_refund'], 2);
                $refundAmount = $refundSummaryRow['amount_to_refund'];
                $vSubscriptionId = $refundSummaryRow['payment_system_subscription_id'];
                $userId = $refundSummaryRow['user_id'];
                $profileSubscriptionId = $refundSummaryRow['profile_subscription_id'];

                $header = [
                    'User ID',
                    'Email',
                    'Profile Sub ID',
                    'Everglades Ship Period',
                    'Boxes After Everglades',
                    'Last TXN AMOUNT',
                    'Amount to Refund',
                    'Payment System',
                    'PSP Sub ID',
                    'Recent Payment',
                    'Subscription Created'
                ];

                unset($refundSummaryRow['payment_plan_id']);
                unset($refundSummaryRow['plan_boxes_num']);
                $refundSummaryRow['payment_system'] = ($refundSummaryRow['payment_system'] == 1) ? 'Stripe' : 'Paypal';
                $this->table($header, [$refundSummaryRow]);

                if ($this->confirm('Really refund this and remove all shipments after Everglades [yes|no]')) {
                    $this->info('PROCESS REFUND!');
                    try {
                        $this->_refund($profileSubscriptionId, $userId, $vSubscriptionId, $refundAmount);
                        $subscription = $this->_subscriptionManager->getSubscription(2);
                        $activePeriodIdx = $this->_subscriptionManager->getActivePeriodIndex($subscription);

                        if ($refundSummaryRow['everglades_ship_period'] < $activePeriodIdx) {
                            $this->info('Profile subscription ID: [' . $profileSubscriptionId . '] received all boxes including Everglades. Please cancel subscription ID: [' . $vSubscriptionId . '] manually in Stripe or Paypal');
                            $profileSubscription = $this->_profileSubscriptionDao->find($profileSubscriptionId);
                            $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findLastByProfileSubscription($profileSubscriptionId);
                            $profileSubscription->setStatus(SubscriptionStatusEnum::FINISHED);
                            $profileSubscriptionPlan->setStatus(SubscriptionStatusEnum::FINISHED);
                            $this->_profileSubscriptionDao->save($profileSubscription);
                            $this->_profileSubscriptionPlanDao->save($profileSubscriptionPlan);
                            $this->line('Subscription has been marked as FINISHED');
                        }


                    } catch (\Exception $e) {
                        $this->error($e->getMessage());
                        $this->error('Refund operation failed, no changes has been made...');
                        $this->line('Fetching next record...');
                        continue;
                    }
                } else {
                    $this->line('Fetching next record...');
                    continue;
                }
            }

        } else {
            $this->error('Aborted by a user');
        }
    }

    protected function _refund($profileSubscriptionId, $userId, $vSubscriptionId, $amount)
    {
        $lastTxn = $this->_profileSubscriptionTransactionDao->findByLastCharge($profileSubscriptionId);
        $user = $this->_userRepository->find($userId);
        $profileSubscription = $this->_profileSubscriptionDao->find($profileSubscriptionId);

        switch ($lastTxn->getVPaymentSystemId()) {
            case \Pley\Enum\PaymentSystemEnum::STRIPE:
                $stripeSubscription = \Stripe\Subscription::retrieve($vSubscriptionId);
                $refundTransaction = \Stripe\Refund::create([
                    "charge" => $lastTxn->getVPaymentTransactionId(),
                    "amount" => $amount * 100,
                ]);
                if (!$refundTransaction->status === 'succeeded') {
                    throw new \Exception('Stripe refund failed...');
                }
                $vRefundTxnId = $refundTransaction->id;
                $createdAt = $refundTransaction->created;

                $trialEnd = $stripeSubscription->trial_end;
                if ($trialEnd != null) {
                    $stripeSubscription->cancel(['at_period_end' => true]);
                }
                $this->line('Refund Successful!');
                $this->line('Subscription has been set to cancel at the end of period.');

                break;
            case \Pley\Enum\PaymentSystemEnum::PAYPAL: {
                /**
                 * @var $paypalManager \Pley\Billing\PaypalManager
                 */
                $paypalManager = app('\Pley\Billing\PaypalManager');
                $txns = $paypalManager->listBillingAgreementTransactions($vSubscriptionId);

                foreach ($txns->getAgreementTransactionList() as $txn){
                    if($txn->getStatus() === 'Completed' || $txn->getStatus() === 'Refunded'){
                        $refundTxn = $paypalManager->refundTransaction($txn->getTransactionId(), $amount, "NatGeo Subscriptions Refund");
                        break;
                    }
                }
                if(!$refundTxn || $refundTxn->getState() !== 'completed'){
                    throw new \Exception('PayPal refund failed...');
                }
                $vRefundTxnId = $refundTxn->getId();
                $createdAt = time();
                $this->line('Refunded PayPal');
                break;
            }
        }

        $profileSubscriptionTransaction = ProfileSubscriptionTransaction::withNew(
            $user->getId(),
            $profileSubscription->getUserProfileId(),
            $profileSubscription->getId(),
            $lastTxn->getProfileSubscriptionPlanId(),
            $lastTxn->getUserPaymentMethodId(),
            \Pley\Enum\TransactionEnum::REFUND,
            $amount,
            $user->getVPaymentSystemId(),
            $lastTxn->getVPaymentMethodId(),
            $vRefundTxnId,
            $createdAt,
            $amount,
            0,
            null,
            null
        );
        $this->line('Refund transaction created! TXN ID: [' . $vRefundTxnId . ']');

        $this->_profileSubscriptionTransactionDao->save($profileSubscriptionTransaction);
        $this->_updateSubscriptionAndShipments($profileSubscription);
    }

    protected function _updateSubscriptionAndShipments(ProfileSubscription $profileSubscription)
    {
        $evergladesItemSequenceIndex = 5;

        $sequenceQueue = $profileSubscription->getItemSequenceQueue();
        foreach ($sequenceQueue as $k => $queueItem) {
            //unset all items, which has a sequence index bigger then Everglades
            if ($queueItem->getSequenceIndex() > $evergladesItemSequenceIndex) {
                unset($sequenceQueue[$k]);
            }
        }
        $profileSubscription->setItemSequenceQueue($sequenceQueue);
        $this->_profileSubscriptionDao->save($profileSubscription);

        $sql = "
        UPDATE profile_subscription_shipment
         SET `status` = ?  
        WHERE profile_subscription_id = ? AND item_sequence_index > ?;";

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([
            \Pley\Enum\Shipping\ShipmentStatusEnum::CANCELLED,
            $profileSubscription->getId(),
            $evergladesItemSequenceIndex
        ]);
        $this->line('Shipments updated!');
    }

    protected function getOptions()
    {
        return [
            [
                'action',
                null,
                InputOption::VALUE_REQUIRED,
                'Indicates an action to perform to users [info|refund]'
            ],
        ];
    }

}

