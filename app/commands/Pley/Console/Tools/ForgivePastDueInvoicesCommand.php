<?php

namespace Pley\Console\Tools;

/** @copyright Pley (c) 2017, All Rights Reserved */

use Illuminate\Console\Command;
use Pley\Enum\SubscriptionStatusEnum;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The <kbd>ForgivePastDueInvoicesCommand</kbd>
 *asd
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ForgivePastDueInvoicesCommand extends Command
{

    use \Pley\Console\ConsoleOutputTrait;

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:forgivePastDueInvoices';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'One time script to forgive past due Stripe invoices';

    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /**
     * @var \Pley\Payment\PaymentManagerFactory
     */
    protected $_paymentManagerFactory;
    /**
     * @var \Pley\Repository\User\UserRepository
     */
    protected $_userRepository;
    /**
     * @var \Pley\Dao\Profile\ProfileSubscriptionDao
     * */
    protected $_profileSubsDao;
    /**
     * @var \Pley\Dao\Profile\ProfileSubscriptionPlanDao
     * */
    protected $_profileSubsPlanDao;
    /**
     * @var \Pley\User\UserSubscriptionManager
     */
    protected $_userSubscriptionManager;
    /**
     * @var \Pley\Subscription\SubscriptionManager
     */
    protected $_subscriptionManager;


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->_config = \App::make('\Pley\Config\ConfigInterface');
        $this->_dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_userRepository = \App::make('\Pley\Repository\User\UserRepository');
        $this->_paymentManagerFactory = \App::make('\Pley\Payment\PaymentManagerFactory');
        $this->_profileSubsDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionDao');
        $this->_profileSubsPlanDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionPlanDao');
        $this->_userSubscriptionManager = \App::make('\Pley\User\UserSubscriptionManager');
        $this->_subscriptionManager = \App::make('\Pley\Subscription\SubscriptionManager');

        $this->_setLogOutput(true);
    }

    public function fire()
    {
        $this->info('Begin...');

        $profileSubscriptions = $this->_getPastDueProfileSubscriptions();
        $count = count($profileSubscriptions);

        if (!$this->confirm(sprintf('There are %d subscriptions to process and reactivate each. Continue? [yes|no]', $count))) {
            $this->error('Operation has been aborted.');
            return;
        }

        $successful = 0;
        $error = 0;

        foreach ($profileSubscriptions as $profileSubscription) {
            $profileSubsPlan = $this->_profileSubsPlanDao->findLastByProfileSubscription($profileSubscription->getId());
            $subscription = $this->_subscriptionManager->getSubscription($profileSubscription->getSubscriptionId());

            $user = $this->_userRepository->find($profileSubscription->getUserId());

            $this->line(sprintf('Processing Stripe subscription ID: [%s]...', $profileSubsPlan->getVPaymentSubscriptionId()));
            $isForgiven = $this->_forgivePastDueInvoice($user->getVPaymentAccountId(), $profileSubsPlan->getVPaymentSubscriptionId());
            if ($isForgiven) {
                $profileSubscription->setStatus(SubscriptionStatusEnum::UNPAID);
                $profileSubsPlan->setStatus(SubscriptionStatusEnum::UNPAID);
                $this->_profileSubsDao->save($profileSubscription);
                $this->_profileSubsPlanDao->save($profileSubsPlan);
                $successful++;
            } else {
                $error++;
            }
            $this->line(sprintf('%d subscriptions left', --$count));
        }
        $this->info('Script completed');
        $this->info('PROCESSED: ' . $successful . ' profile subscriptions');
        $this->error('NOT PROCESSED: ' . $error . ' profile subscriptions');
        return;
    }

    protected function _forgivePastDueInvoice($stripeCustomerId, $stripeSubscriptionId)
    {
        $customerInvoices = \Stripe\Invoice::all(
            ['customer' => $stripeCustomerId,
                'subscription' => $stripeSubscriptionId]);

        if (count($customerInvoices->data)) {
            $lastInvoice = $customerInvoices->data[0]; //get latest invoice
            if ($lastInvoice->paid === false && $lastInvoice->next_payment_attempt === null) {
                $lastInvoice->closed = true;
                $lastInvoice->save();
                $lastInvoice->forgiven = true;
                $lastInvoice->save();
                $this->info(sprintf('Stripe Invoice ID: [%s] has been FORGIVEN', $lastInvoice->id));
                return true;
            } else {
                $this->error(sprintf('Stripe Invoice ID: [%s] is not valid to be forgiven', $lastInvoice->id));
                return false;
            }
        }
    }

    /**
     * @return \Pley\Entity\Profile\ProfileSubscription[]
     */
    protected function _getPastDueProfileSubscriptions()
    {
        $profileSubscriptions = [];
        $sql = 'SELECT `ps`.`id`
FROM profile_subscription `ps`
WHERE `ps`.status = ?';

        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute([SubscriptionStatusEnum::PAST_DUE]);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($resultSet as $resItem) {
            $profileSubscriptions[] = $this->_profileSubsDao->find($resItem['id']);
        }
        return $profileSubscriptions;
    }
}