<?php

namespace Pley\Console\Tools;

/** @copyright Pley (c) 2017, All Rights Reserved */

/**
 * Class description goes here
 *
 * @author Vsevolod Yatsiuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */

use Illuminate\Console\Command;
use Pley\Enum\Shipping\ShipmentStatusEnum;
use Pley\Enum\SubscriptionEnum;
use Pley\Util\Time\DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pley\Entity\Profile\ProfileSubscription;
use Pley\Entity\Profile\ProfileSubscriptionPlan;
use Pley\Enum\PaymentSystemEnum;
use Pley\Enum\SubscriptionStatusEnum;
use Pley\Subscription\SubscriptionPeriodIterator;
use \Symfony\Component\Console\Input\InputOption;
use Pley\Dao\Profile\ProfileSubscriptionDao;
use Pley\Dao\Profile\ProfileSubscriptionPlanDao;
use Pley\Dao\Profile\ProfileSubscriptionTransactionDao;
use Pley\Subscription\SubscriptionManager;
use Pley\Entity\Profile\ProfileSubscriptionTransaction;
use Pley\Repository\User\UserRepository;

/**
 * The <kbd>ShowSubscriptionShipmentsByMonth</kbd>
 *asd
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ShowSubscriptionShipmentsByMonth extends Command
{

    use \Pley\Console\ConsoleOutputTrait;
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:show-subscription-shipments';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Show all subscription shipments by month';

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

    /** @var \Pley\User\UserManager */

    protected $_userManager;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
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
        $this->_userManager = \App::make('\Pley\User\UserManager');

        $this->_setLogOutput(true);
    }

    public function fire()
    {
        $this->info('Begin...');
        $subscriptionId = $this->option('subscriptionId');

        $activeProfileSubscriptionsList = $this->_profileSubscriptionDao->findBySubscription($subscriptionId);

        $header = [
            'User ID',
            'User Email',
            'Ship Start Date',
            'Shipment Status',
            'Item Name',
        ];

        $currentPendingShipments = [];

        foreach ($activeProfileSubscriptionsList as $profileSubscription) {
            $shipmentCollection = $this->_userManager->getSubscriptionShipmentCollection($profileSubscription);
            $shipment = $shipmentCollection->getCurrent();
            if (!$shipment) {
                continue;
            }
            if($shipment->getStatus() !== ShipmentStatusEnum::PREPROCESSING){
                continue;
            }
            $sequenceItem = $this->_subscriptionManager->getScheduledItem($shipment);
            $item = !empty($sequenceItem->getItemId()) ?
                $this->_subscriptionManager->getItem($sequenceItem->getItemId()) : null;
            $currentPendingShipments[] = [
                $profileSubscription->getUserId(),
                $this->_userRepository->find($profileSubscription->getUserId())->getEmail(),
                \Pley\Util\DateTime::date($sequenceItem->getDeliveryStartTime()),
                ShipmentStatusEnum::asString($shipment->getStatus()),
                ($item) ? $item->getName() : 'N\A'
            ];
        }
        $this->table($header, $currentPendingShipments);
        $this->line('TOTAL: ' . count($currentPendingShipments));
    }

    protected function getOptions()
    {
        return [
            [
                'subscriptionId',
                null,
                InputOption::VALUE_REQUIRED,
                'Indicates an subscription ID [Disney\NatGeo\HW]'
            ],
        ];
    }
}