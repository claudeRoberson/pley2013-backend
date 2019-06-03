<?php

namespace Pley\Console\Tools;

/** @copyright Pley (c) 2017, All Rights Reserved */

/**
 * Script for refunding and resetting shipments
 *
 * @author Vsevolod Yatsiuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */

use Illuminate\Console\Command;
use Pley\Entity\Profile\ProfileSubscriptionShipment;
use Pley\Enum\SubscriptionEnum;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Input\InputOption;
use Pley\Dao\Profile\ProfileSubscriptionDao;
use Pley\Dao\Profile\ProfileSubscriptionPlanDao;
use Pley\Dao\Profile\ProfileSubscriptionTransactionDao;
use Pley\Subscription\SubscriptionManager;
use Pley\Repository\User\UserRepository;

/**
 * The <kbd>RefundShippingLabels</kbd>
 *asd
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class RefundShippingLabels extends Command
{

    use \Pley\Console\ConsoleOutputTrait;
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:refund-shipping-labels';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Refund shipping labels command';

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
     * @var \Pley\Dao\User\UserProfileDao
     *
     */
    protected $_userProfileDao;
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
     * @var \Pley\User\UserSubscriptionManager
     */
    protected $_userSubscriptionManager;

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
        $this->_userProfileDao = \App::make('\Pley\Dao\User\UserProfileDao');
        $this->_userSubscriptionManager = \App::make('\Pley\User\UserSubscriptionManager');

        $this->_setLogOutput(true);
    }

    public function fire()
    {
        $this->info('Begin...');

        $subscriptionId = (int)$this->option('subscriptionId');
        \EasyPost\EasyPost::setApiKey($this->_config['shipping.apiKey']);
        $shipments = [];

        if($subscriptionId === SubscriptionEnum::DISNEY_PRINCESS){
            $shipments = $this->_getDisneyShipmentsToRefund();
        }elseif ($subscriptionId === SubscriptionEnum::NATIONAL_GEOGRAPHIC){
            $shipments = $this->_getNatGeoShipmentsToRefund();
        }

        foreach ($shipments as $shipment) {
            try {
                $epShipment = \EasyPost\Shipment::retrieve($shipment->getVendorShipId());
                $epShipment->refund();
                $this->_resetShipment($shipment);
                $this->info('Successfully refunded and processed shipment ID ' . $shipment->getId());
            } catch (\Exception $e) {
                $this->error('Problem with shipment ID ' . $shipment->getId());
                $this->error($e->getMessage());
                continue;
            }
        }
        $this->info('SUCCESSFULLY COMPLETED!');
    }

    /**
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment[]
     */
    protected function _getDisneyShipmentsToRefund()
    {
        $sql = 'SELECT `id` FROM `profile_subscription_shipment` '
            . 'WHERE `subscription_id` = 1 AND `schedule_index` = 7 AND `status` = 1 AND `v_ship_id` IS NOT NULL';

        $pstmt = $this->_dbManager->prepare($sql);

        $pstmt->execute();

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);

        $profileSubsShipmentList = [];
        foreach ($resultSet as $dbRecord) {
            $profileSubsShipment = $this->_profileSubsShipDao->find($dbRecord['id']);
            $profileSubsShipmentList[] = $profileSubsShipment;
        }

        return $profileSubsShipmentList;
    }

    /**
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment[]
     */
    protected function _getNatGeoShipmentsToRefund()
    {
        $sql = 'SELECT `id` FROM `profile_subscription_shipment` WHERE `subscription_id` = 2 AND `schedule_index` = 9 AND status IN (1,2) AND carrier_id NOT IN (1000);';

        $pstmt = $this->_dbManager->prepare($sql);

        $pstmt->execute();

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);

        $profileSubsShipmentList = [];
        foreach ($resultSet as $dbRecord) {
            $profileSubsShipment = $this->_profileSubsShipDao->find($dbRecord['id']);
            $profileSubsShipmentList[] = $profileSubsShipment;
        }

        return $profileSubsShipmentList;
    }

    protected function _resetShipment(ProfileSubscriptionShipment $shipment)
    {
        $sql = 'UPDATE `profile_subscription_shipment` SET '
            . '	type_shirt_size_id = NULL,
	carrier_id = NULL,
	`status` = 1,
	carrier_service_id = NULL,
	carrier_rate = NULL,
	label_url =NULL,
	tracking_no = NULL,
	v_ship_id = NULL,
	v_ship_tracker_id = NULL,
	shipped_at = NULL,
	delivered_at = NULL,
	street_1 = NULL,
	street_2 =NULL,
	city = NULL,
	state = NULL,
	zip = NULL,
	country = NULL,
	label_purchase_at = NULL,
	label_lease = NULL,
	item_id = NULL,
	shipping_zone_id = NULL,
	shipping_zone_usps = NULL 
	WHERE id = ?;';

        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute([$shipment->getId()]);
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