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
 * The <kbd>RescheduleDisneyMayBoxes</kbd>
 *asd
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class RescheduleDisneyMayBoxes extends Command
{

    use \Pley\Console\ConsoleOutputTrait;
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:reschedule-disney-may-boxes';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Reschedule Disney May Boxes Command';

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

    const DISNEY_MAY_SCHEDULE_INDEX = 7;

    const RAPUNZEL_BOX_ID = 5;

    const MERIDA_BOX_ID = 7;

    const MIXED_BOX_ID = 17;

    protected $_currentStockLevels = [
        //item id
        1 => [
            //size id
            2 => 8,
            3 => 585,
            4 => 152,
            5 => 18
        ],
        2 => [
            2 => 103,
            3 => 81,
            4 => 149,
            5 => 131
        ],
        5 => [
            0 => 1405
        ],
        7 => [
            2 => 526,
            3 => 161,
            4 => 343,
            5 => 1593
        ],
        10 => [
            2 => 1,
            3 => 0,
            4 => 3,
            5 => 492
        ],
        13 => [
            2 => 0,
            3 => 6,
            4 => 0,
            5 => 594
        ],
        15 => [
            2 => 680,
            3 => 958,
            4 => 252,
            5 => 55
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
        $subscriptionId = SubscriptionEnum::DISNEY_PRINCESS;
        $isDryRun = $this->option('dryRun');


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

        $activeProfileSubscriptionsList = $this->findSubscriptionsWithMayBoxScheduled();

        $currentPendingShipments = [];

        $i = 0;
        foreach ($activeProfileSubscriptionsList as $profileSubscription) {
            $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findLastByProfileSubscription($profileSubscription->getId());
            $subscriptionCreatedAt = DateTime::date($profileSubscription->getCreatedAt());
            $user = $this->_userRepository->find($profileSubscription->getUserId());
            $userProfile = $this->_userProfileDao->find($profileSubscription->getUserProfileId());

            $shipmentCollection = $this->_getShipmentsForProfileSubscription($subscriptionId,
                $profileSubscription->getId());

            $received = 0;
            $prepaid = 0;
            foreach ($shipmentCollection as $shipment) {
                if ($shipment->getStatus() === ShipmentStatusEnum::DELIVERED || $shipment->getStatus() === ShipmentStatusEnum::PROCESSED) {
                    $received++;
                }
                if ($shipment->getStatus() === ShipmentStatusEnum::PREPROCESSING) {
                    $prepaid++;
                }
            }
            if (!$profileSubscriptionPlan && $received = 6) {
                continue;
            }
            if ($received < 7) {
                list($shirtSizeId, $mayBoxId) = $this->findMayBoxId($shipmentCollection, $userProfile, $itemsNames);
                if($isDryRun){
                    $this->recordTempAssignment($profileSubscription->getId(), $mayBoxId, $shirtSizeId);
                }else{
                    try{
                        $this->updateMayShipment($profileSubscription->getId(), $mayBoxId, $fullItemSequence);
                    }catch (\Exception $e){
                        $this->error($e->getMessage());
                    }
                }
            } else {
                $this->line('Received a full set - assigned Mixed box...');
            }
            $this->line('PROCESSED: ' . $i++);
        }
        $this->line('TOTAL: ' . count($currentPendingShipments));
        var_dump($this->_currentStockLevels);
    }

    /**
     * @var \Pley\Entity\Profile\ProfileSubscriptionShipment[] $shipmentCollection
     * @var \Pley\Entity\User\UserProfile $userProfile
     * @var array $fullItemSequence
     * @return array | boolean
     */

    protected function findMayBoxId($shipmentCollection, $userProfile, $itemsSequence)
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
            if ($notReceivedBoxId == self::RAPUNZEL_BOX_ID) { //Rapunzel ID, size agnostic
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
            $this->line('Assigning box : ' . $nextBoxName . ' to user ' . $userProfile->getUserId());
            return [$shirtSizeId, $notReceivedBoxId];
        }
        return [$userProfile->getTypeShirtSizeId(), self::MIXED_BOX_ID];
    }

    protected function updateMayShipment($profileSubscriptionId, $itemId, $fullItemSequence){
        $itemSequenceIdx = $this->getItemSequenceIdxByItemId($fullItemSequence, $itemId);

        $sql = 'UPDATE `profile_subscription_shipment` SET `item_sequence_index` = ? '
            . 'WHERE `subscription_id` = ? AND `profile_subscription_id` = ? AND `schedule_index` = ?';

        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute([$itemSequenceIdx, SubscriptionEnum::DISNEY_PRINCESS, $profileSubscriptionId, self::DISNEY_MAY_SCHEDULE_INDEX]);
    }

    protected function recordTempAssignment($profileSubscriptionId, $itemId, $shirtSizeId){

        $sql = 'INSERT INTO `may_assignments_temp` VALUES (NULL, ?, ?, ?);';
        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute([$profileSubscriptionId, $shirtSizeId, $itemId]);
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

    protected function findSubscriptionsWithMayBoxScheduled()
    {
        $sql = 'SELECT `profile_subscription_id` FROM `profile_subscription_shipment` '
            . 'WHERE `subscription_id` = ? AND `schedule_index` = ? AND `label_url` IS NULL '
            . 'ORDER BY `profile_subscription_id`;';

        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute([SubscriptionEnum::DISNEY_PRINCESS, self::DISNEY_MAY_SCHEDULE_INDEX]);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);

        $profileSubscriptionList = [];
        foreach ($resultSet as $dbRecord) {
            $profileSubscription = $this->_profileSubscriptionDao->find($dbRecord['profile_subscription_id']);
            $profileSubscriptionList[] = $profileSubscription;
        }

        return $profileSubscriptionList;
    }

    /**
     * @param $fullItemSequence
     * @param $itemId
     */
    protected function getItemSequenceIdxByItemId($fullItemSequence, $itemId)
    {
        foreach ($fullItemSequence as $seq) {
            if ($seq->getItemId() === $itemId) {
                return $seq->getSequenceIndex();
            }
        }
    }
    protected function getOptions()
    {
        return [
            [
                'dryRun',
                null,
                InputOption::VALUE_REQUIRED,
                'Update shipments or use a temporary table to store assignment results'
            ],
        ];
    }
}