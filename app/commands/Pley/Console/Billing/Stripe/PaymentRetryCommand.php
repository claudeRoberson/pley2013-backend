<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace Pley\Console\Billing\Stripe;

use \Illuminate\Console\Command;
use Pley\Entity\Profile\ProfileSubscription;
use Pley\Entity\Profile\ProfileSubscriptionPlan;
use Pley\Enum\PaymentSystemEnum;
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
use Pley\Entity\Payment\PaymentRetryLog;
use Pley\Repository\Payment\PaymentRetryLogRepository;

class PaymentRetryCommand extends Command
{

    use \Pley\Console\ConsoleOutputTrait;

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:billing:stripe:payment-retry';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Command to retry the payment on invoices, which has been unpaid (failed all Stripe retries)';

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
     * @var \Pley\Repository\Payment\PaymentRetryLogRepository
     */
    protected $_paymentRetryLogRepository;

    /**
     * @var UserRepository
     */
    protected $_userRepository;

    protected $_maxRetryAge = 0;

    const MAX_RETRIES_NUM = 2;


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
        $this->_paymentRetryLogRepository = \App::make('\Pley\Repository\Payment\PaymentRetryLogRepository');

        $this->_maxRetryAge = 30 * 24 * 3600;
    }

    public function fire()
    {
        $this->info('Processing subscriptions with [UNPAID] status for additional retries.');
        $unpaidProfileSubscriptions = $this->_profileSubscriptionDao->findUnpaid();

        if (!$unpaidProfileSubscriptions) {
            $this->info('No subscriptions to process.');
            return;
        }

        if (!$this->option('action')) {
            $this->error('Please provide an --action=[info|run] option');
        }

        switch ($this->option('action')) {
            case 'info':
                $this->_outputProfileSubscriptionsInfo($unpaidProfileSubscriptions);
                break;
            case 'run':
                $this->_processProfileSubscriptions($unpaidProfileSubscriptions);
                break;
        }
    }

    /**
     * @param ProfileSubscription[] $unpaidProfileSubscriptions
     */
    protected function _outputProfileSubscriptionsInfo($unpaidProfileSubscriptions)
    {
        $header = [
            'Subscription ID',
            'Stripe Subscription ID',
            'Subscription Created At',
            'Last Payment Attempt At',
        ];

        $totals = new \stdClass();
        $totals->totalSubscriptions = 0;
        $totals->paypalTotalSubscriptions = 0;
        $totals->stripeTotalSubscriptions = 0;
        $totals->totalRefundDue = 0;
        $summary = [];
        $count = 0;

        foreach ($unpaidProfileSubscriptions as $key => &$profileSubscription) {
            $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findLastByProfileSubscription($profileSubscription->getId());
            if ($profileSubscriptionPlan->getVPaymentSystemId() !== PaymentSystemEnum::STRIPE) {
                continue;
            }
            if (time() - $profileSubscription->getUpdatedAt() > $this->_maxRetryAge) {
                //skip those subscriptions, which last payment attempt happened more than month ago
                continue;
            }

            $summary[] = [
                $profileSubscription->getId(),
                $profileSubscriptionPlan->getVPaymentSubscriptionId(),
                \Pley\Util\DateTime::date($profileSubscription->getCreatedAt()),
                \Pley\Util\DateTime::date($profileSubscription->getUpdatedAt())
            ];
            $count++;
        }

        $this->table($header, $summary);

        $this->info('Total subscriptions: ' . $count);
        return;
    }

    /**
     * @param ProfileSubscription[] $unpaidProfileSubscriptions
     */
    protected function _processProfileSubscriptions($unpaidProfileSubscriptions)
    {
        foreach ($unpaidProfileSubscriptions as $key => &$profileSubscription) {
            $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findLastByProfileSubscription($profileSubscription->getId());
            if ($profileSubscriptionPlan->getVPaymentSystemId() !== PaymentSystemEnum::STRIPE) {
                continue;
            }
            if (time() - $profileSubscription->getUpdatedAt() > $this->_maxRetryAge) {
                //skip those subscriptions, which last payment attempt happened more than month ago
                continue;
            }
            $this->info('Processing subscription ID: ' . $profileSubscription->getId());

            if (count($this->_paymentRetryLogRepository->getAllSubscriptionRetries($profileSubscription->getId())) >= self::MAX_RETRIES_NUM) {
                $this->info('Max retries amount reached - should be sent to Vindicia.');
            } else {
                $this->info('Processing subscription ID: ' . $profileSubscription->getId());
                try {
                    $this->_pay($profileSubscriptionPlan);
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                    continue;
                }
            }
        }
        return;
    }

    protected function _pay(ProfileSubscriptionPlan $profileSubscriptionPlan)
    {
        $this->info('Fetching Stripe subscription ID: ' . $profileSubscriptionPlan->getVPaymentSubscriptionId());

        $customer = $this->_userRepository->find($profileSubscriptionPlan->getUserId());
        $invoices = \Stripe\Invoice::all(array("subscription" => $profileSubscriptionPlan->getVPaymentSubscriptionId()));
        if (!$invoices->data) {
            throw new \Exception('Recent invoice is paid. Skipping...');
        }
        /**
         * @var $recentInvoice \Stripe\Invoice
         */
        $recentInvoice = current($invoices->data);
        if ($recentInvoice->paid != false) {
            throw new \Exception('Recent invoice is paid. Skipping...');
        }
        try {
            $result = $recentInvoice->pay();
        } catch (\Stripe\Error\Card $exception) {
            $this->_logRetry($profileSubscriptionPlan->getProfileSubscriptionId(), PaymentRetryLog::STATUS_FAIL, $exception->getMessage());
            $this->error($exception->getMessage());
            return;
        }
        $this->_logRetry($profileSubscriptionPlan->getProfileSubscriptionId(), PaymentRetryLog::STATUS_SUCCESS, 'Success!');
        $this->info('Successfully paid an invoice!');
        return;
    }

    protected function _logRetry($profileSubscriptionId, $status, $message)
    {
        $log = new PaymentRetryLog();
        $log->setStatus($status)
            ->setProfileSubscriptionId($profileSubscriptionId)
            ->setResponseMessage($message);
        $this->_paymentRetryLogRepository->save($log);
    }

    protected function getOptions()
    {
        return [
            [
                'action',
                null,
                InputOption::VALUE_REQUIRED,
                'Indicates an action to perform to users [info|run]'
            ],
        ];
    }

}

