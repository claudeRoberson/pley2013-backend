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
use Pley\Enum\ShirtSizeEnum;
use Pley\Enum\SubscriptionEnum;
use Pley\Util\Time\DateTime;
use Pley\Util\Util;
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
use League\Csv\Reader;
use League\Csv\Writer;
use Pley\User\UserSubscriptionManager;

/**
 * The <kbd>ShowSubscriptionShipmentsByMonth</kbd>
 *asd
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class GetDisneyReceivedBoxes extends Command
{

    use \Pley\Console\ConsoleOutputTrait;
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:get-disney-received-boxes';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Create a report file with Disney received boxes';

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

    const RAPUNZEL_BOX_ID = 5;

    const MERIDA_BOX_ID = 7;

    const MULTIPRINCESS_BOX_ID = 17;

    protected $_currentStockLevels = [
        //item id
        1 => [
            //size id
            2 => 0,
            3 => 111,
            4 => 100,
            5 => 0
        ],
        2 => [
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 65
        ],
        5 => [
            0 => 745
        ],
        7 => [
            2 => 220,
            3 => 49,
            4 => 53,
            5 => 1435
        ],
        10 => [
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 1664
        ],
        13 => [
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 603
        ],
        15 => [
            2 => 682,
            3 => 923,
            4 => 226,
            5 => 46
        ],
        17 => [
            0 => 20000
        ]
    ];


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
        $subscriptionId = $this->option('subscriptionId');

        $subscription = $this->_subscriptionManager->getSubscription($subscriptionId);
        $fullItemSequence = $this->_subscriptionManager->getFullItemSequence($subscription);

        $itemsIdMap = [];
        $itemsNames = [];

        foreach ($fullItemSequence as $itemSequence) {
            if ($itemSequence->getItemId()) {
                $item = $this->_subscriptionManager->getItem($itemSequence->getItemId());
                $itemsIdMap[$item->getId()] = 'NO';
                $itemsNames[$item->getId()] = $item->getName();
            }
        }

        $header = [
            'User ID',
            'Firstname',
            'Lastname',
            'Email',
            'Plan',
            'Shirt Size',
            'Date Joined',
            'Prepaid Boxes Left',
            'July Box Assigned'
        ];
        foreach ($itemsNames as $itemName) {
            $header[] = $itemName;
        }
        $csv = Writer::createFromPath(new \SplFileObject(storage_path('csv/DP_received_boxes.csv'), 'w+'), 'w+');

        $activeProfileSubscriptionsList = $this->_profileSubscriptionDao->findBySubscription($subscriptionId);
        $activeProfileSubscriptionsList = array_reverse($activeProfileSubscriptionsList);
        $csv->insertOne($header);

        $currentPendingShipments = [];
        $csvData = [];

        $i = 0;
        foreach ($activeProfileSubscriptionsList as $profileSubscription) {
            $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findLastByProfileSubscription($profileSubscription->getId());
            $row = [];
            $subscriptionCreatedAt = DateTime::date($profileSubscription->getCreatedAt());
            $user = $this->_userRepository->find($profileSubscription->getUserId());
            $userProfile = $this->_userProfileDao->find($profileSubscription->getUserProfileId());

            $shipmentCollection = $this->_getShipmentsForProfileSubscription($subscriptionId,
                $profileSubscription->getId());

            $received = 0;
            $prepaid = 0;
            foreach ($shipmentCollection as $shipment) {
                if ($shipment->getStatus() === ShipmentStatusEnum::DELIVERED ||
                    $shipment->getStatus() === ShipmentStatusEnum::PROCESSED ||
                    $shipment->getScheduleIndex() == 7
                ) {
                    $received++;
                    continue;
                }
                if ($shipment->getStatus() === ShipmentStatusEnum::PREPROCESSING) {
                    $prepaid++;
                }
            }
            if (!$profileSubscriptionPlan && $received = 6) {
                continue;
            }
            if ($received < 8) {
                $nextBox = $this->assignBox($shipmentCollection, $userProfile, $itemsNames);
            } else {
                $nextBox = 'FINISHED SUBSCRIPTION';
            }

            $csvData[$profileSubscription->getId()] = [
                'data' => [
                    $user->getId(),
                    $user->getFirstName(),
                    $user->getLastName(),
                    $user->getEmail(),
                    ($profileSubscriptionPlan) ? $profileSubscriptionPlan->getPaymentPlanId() : 'GIFT',
                    ShirtSizeEnum::asString($userProfile->getTypeShirtSizeId()),
                    $subscriptionCreatedAt,
                    $prepaid,
                    $nextBox
                ],
                'items' => $itemsIdMap
            ];

            foreach ($shipmentCollection as $shipment) {
                if ($shipment->getItemId()) {
                    if (array_key_exists($shipment->getItemId(), $csvData[$profileSubscription->getId()]['items'])) {
                        $csvData[$profileSubscription->getId()]['items'][$shipment->getItemId()] = 'YES';
                    }
                }
            }
            foreach ($csvData[$profileSubscription->getId()]['data'] as $subscriptionData) {
                $row[] = $subscriptionData;
            }
            foreach ($csvData[$profileSubscription->getId()]['items'] as $itemData) {
                $row[] = $itemData;
            }
            $csv->insertOne($row);
            $this->line('PROCESSED: ' . $i++);
        }
        $this->line('TOTAL: ' . count($currentPendingShipments));
        var_dump($this->_currentStockLevels);
    }

    /**
     * @var \Pley\Entity\Profile\ProfileSubscriptionShipment[] $shipmentCollection
     * @var \Pley\Entity\User\UserProfile $userProfile
     * @var array $fullItemSequence
     * @return string
     */

    protected function assignBox($shipmentCollection, $userProfile, $itemsSequence)
    {
        $fullSequenceIds = array_keys($itemsSequence);
        $receivedBoxIds = [];
        foreach ($shipmentCollection as $shipment) {
            $receivedBoxIds[] = $shipment->getItemId();
        }
        $notReceivedBoxIds = array_diff($fullSequenceIds, $receivedBoxIds);

        if (in_array(self::RAPUNZEL_BOX_ID, $notReceivedBoxIds)) {
            $key = array_search(self::RAPUNZEL_BOX_ID, $notReceivedBoxIds);
            if ($key) {
                unset($notReceivedBoxIds[$key]);
            }
            array_unshift($notReceivedBoxIds, self::RAPUNZEL_BOX_ID);
        }

        if (in_array(self::MERIDA_BOX_ID, $notReceivedBoxIds)) {
            $key = array_search(self::MERIDA_BOX_ID, $notReceivedBoxIds);
            if ($key) {
                unset($notReceivedBoxIds[$key]);
            }
            array_unshift($notReceivedBoxIds, self::MERIDA_BOX_ID);
        }


        foreach ($notReceivedBoxIds as $notReceivedBoxId) {
            $shirtSizeId = $userProfile->getTypeShirtSizeId();
            if ($notReceivedBoxId == self::RAPUNZEL_BOX_ID || $notReceivedBoxId == self::MULTIPRINCESS_BOX_ID){ // Rapunzel / Multiprincess, size agnostic
                $shirtSizeId = 0;
            }

            if (!array_key_exists($notReceivedBoxId, $this->_currentStockLevels)) {
                continue;
            }
            if (!array_key_exists($shirtSizeId, $this->_currentStockLevels[$notReceivedBoxId])) {
                continue;
            }
            if ($this->_currentStockLevels[$notReceivedBoxId][$shirtSizeId] === 0) {
                continue;
            }
            $this->_currentStockLevels[$notReceivedBoxId][$shirtSizeId]--;
            $nextBoxName = $itemsSequence[$notReceivedBoxId];
            $this->line('Assigned box : ' . $nextBoxName);
            $this->line($nextBoxName . ' Left:  ' . $this->_currentStockLevels[$notReceivedBoxId][$shirtSizeId]);
            return $nextBoxName;
        }
        return 'N\A';
    }

    protected function scheduleShipments()
    {
        $profileSubscriptionIds = [
            /*
                        691,
                        1045,
                        1578,
                        2139,
                        2196,
                        2305,
                        2678,
                        2719,
                        2755,
                        3525,
                        3627,
                        3935,
                        3962,
                        4622,
                        5084,
                        6042,
                        6745
            */
        ];

        foreach ($profileSubscriptionIds as $profileSubscriptionId) {
            $profileSubscription = $this->_profileSubscriptionDao->find($profileSubscriptionId);
            $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findLastByProfileSubscription($profileSubscriptionId);
            $subscription = $this->_subscriptionManager->getSubscription($profileSubscription->getSubscriptionId());

            $activePeriodIdx = 6;
            $this->_userSubscriptionManager->queueShipment($profileSubscription, $subscription, $activePeriodIdx);


            $profileSubscription->setStatus(SubscriptionStatusEnum::ACTIVE);
            $profileSubscriptionPlan->setStatus(SubscriptionStatusEnum::ACTIVE);
            $this->_profileSubscriptionDao->save($profileSubscription);
            $this->_profileSubscriptionPlanDao->save($profileSubscriptionPlan);
        }
        return;
    }

    /**
     * @param int $subscriptionId
     * @param int $profileSubsId
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment[]
     */
    private function _getShipmentsForProfileSubscription($subscriptionId, $profileSubsId)
    {
        $sql = 'SELECT `id` FROM `profile_subscription_shipment` '
            . 'WHERE `subscription_id` = ? AND `profile_subscription_id` = ? '
            . 'ORDER BY `id` ASC';

        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute([$subscriptionId, $profileSubsId]);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);

        $profileSubsShipmentList = [];
        foreach ($resultSet as $dbRecord) {
            $profileSubsShipment = $this->_profileSubsShipDao->find($dbRecord['id']);
            $profileSubsShipmentList[] = $profileSubsShipment;
        }

        return $profileSubsShipmentList;
    }

    protected function getAdditionalSubscriptions($userId)
    {
        $sql = 'SELECT `subscription_id` FROM `profile_subscription` '
            . 'WHERE `user_id` = ? AND `status` = 1';

        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute([$userId]);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);

        $additionalSubs = '';
        $profileSubsShipmentList = [];
        foreach ($resultSet as $dbRecord) {
            if ($dbRecord['subscription_id'] === 2) {
                $additionalSubs .= 'NatGeo,';
            }
            if ($dbRecord['subscription_id'] === 3) {
                $additionalSubs .= 'Hotwheels';
            }
        }
        return $additionalSubs;
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