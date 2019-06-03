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
use Pley\Enum\TransactionEnum;
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
class AssignChurnDates extends Command
{

    use \Pley\Console\ConsoleOutputTrait;
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:assign-churn-dates';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Churn dates calculation and assignment';

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
        switch ($this->option('status')) {
            case 'cancelled':
                $this->_processCancelled();
                break;
            case 'unpaid':
                $this->_processUnpaid();
                break;
            case 'finished':
                $this->_processFinished();
                break;
        }
        $this->info('Done processing!');
    }

    protected function _processCancelled()
    {
        $cancelledProfileSubscriptionPlans = $this->_profileSubscriptionPlanDao->findByStatus(SubscriptionStatusEnum::CANCELLED);

        //$cancelledProfileSubscriptionPlans[] = $this->_profileSubscriptionPlanDao->find(7165);

        $i = 0;
        foreach ($cancelledProfileSubscriptionPlans as $cancelledProfileSubscriptionPlan) {
            $this->line($i++ . ' of ' . count($cancelledProfileSubscriptionPlans) . ' processed');
            $churnedAt = null;

            $stopAutorenewDate = $cancelledProfileSubscriptionPlan->getAutoRenewStopAt();
            $stopAutorenewDateString = DateTime::date($stopAutorenewDate);

            $cancelAtDate = $cancelledProfileSubscriptionPlan->getCancelAt();

            if ($stopAutorenewDate && $stopAutorenewDate < time()) {
                $churnedAt = $this->calculateChurnBasedOnPeriods($cancelledProfileSubscriptionPlan);

                if ($churnedAt - time() > 3600 * 24) {
                    $this->info('Skip Churn Line... as it is in future ' . DateTime::date($churnedAt));
                    continue;
                }
                $churnedAt = DateTime::date($churnedAt);
                $this->updateChurn($churnedAt, $cancelledProfileSubscriptionPlan->getId());
                $this->info('Updated Churn date to ' . $churnedAt);
                continue;
            }
        }
        return;
    }

    protected function _processUnpaid()
    {
        $unpaidProfileSubscriptionPlans = $this->_profileSubscriptionPlanDao->findByStatus(SubscriptionStatusEnum::UNPAID);

        $i = 0;
        foreach ($unpaidProfileSubscriptionPlans as $unpaidProfileSubscriptionPlan) {
            $this->line($i++ . ' of ' . count($unpaidProfileSubscriptionPlans) . ' processed');
            $churnedAt = null;

            $unpaidDate = $this->_getStatusChangeDate($unpaidProfileSubscriptionPlan->getProfileSubscriptionId(), SubscriptionStatusEnum::UNPAID);
            if(!$unpaidDate){
                continue;
            }
            $unpaidDate = DateTime::strToTime($unpaidDate);
            $unpaidDateString = DateTime::date($unpaidDate);


            $churnedAt = $this->calculateChurnBasedOnPeriods($unpaidProfileSubscriptionPlan, $unpaidDate);

            if ($churnedAt - time() > 3600 * 24) {
                $this->info('Skip Churn Line... as it is in future ' . DateTime::date($churnedAt));
                continue;
            }
            $churnedAt = DateTime::date($churnedAt);
            $this->updateChurn($churnedAt, $unpaidProfileSubscriptionPlan->getId());
            $this->info('Updated Churn date to ' . $churnedAt);
            continue;
        }
        return;
    }

    protected function _processFinished()
    {
        $unpaidProfileSubscriptionPlans = $this->_profileSubscriptionPlanDao->findByStatus(SubscriptionStatusEnum::FINISHED);

        $i = 0;
        foreach ($unpaidProfileSubscriptionPlans as $unpaidProfileSubscriptionPlan) {
            $this->line($i++ . ' of ' . count($unpaidProfileSubscriptionPlans) . ' processed');
            $churnedAt = null;

            $unpaidDate = $this->_getStatusChangeDate($unpaidProfileSubscriptionPlan->getProfileSubscriptionId(), SubscriptionStatusEnum::FINISHED);
            if(!$unpaidDate){
                continue;
            }
            $unpaidDate = DateTime::strToTime($unpaidDate);
            $unpaidDateString = DateTime::date($unpaidDate);


            $churnedAt = $this->calculateChurnBasedOnPeriods($unpaidProfileSubscriptionPlan, $unpaidDate);

            if ($churnedAt - time() > 3600 * 24) {
                $this->info('Skip Churn Line... as it is in future ' . DateTime::date($churnedAt));
                continue;
            }
            $churnedAt = DateTime::date($churnedAt);
            $this->updateChurn($churnedAt, $unpaidProfileSubscriptionPlan->getId());
            $this->info('Updated Churn date to ' . $churnedAt);
            continue;
        }
        return;
    }


    protected function calculateChurnBasedOnPeriods(
        \Pley\Entity\Profile\ProfileSubscriptionPlan $cancelledProfileSubscriptionPlan, $subscriptionStopDateTimeBase = null
    ) {
        if($subscriptionStopDateTimeBase){
            $stopAutorenewDate = $subscriptionStopDateTimeBase;
        }else{
            $stopAutorenewDate = $cancelledProfileSubscriptionPlan->getAutoRenewStopAt();
        }
        $stopAutorenewDateString = DateTime::date($stopAutorenewDate);
        $planId = $cancelledProfileSubscriptionPlan->getPaymentPlanId();

        $cancelAtDate = $cancelledProfileSubscriptionPlan->getCancelAt();
        $profileSubscription = $this->_profileSubscriptionDao->find($cancelledProfileSubscriptionPlan->getProfileSubscriptionId());
        $subscription = $this->_subscriptionManager->getSubscription($profileSubscription->getSubscriptionId());


        $createdAtTime = $cancelledProfileSubscriptionPlan->getCreatedAt();

        if (in_array($planId, [1, 4, 7])) {
            $shipStartDate = $this->_subscriptionManager->getSubscriptionDates($subscription, 0);

            $closestShipForStopAutorenew = $this->getClosestShipDate($stopAutorenewDate,
                $shipStartDate->getDeliveryStartPeriodDef()->getTimestamp());
            $closestShipForCreatedAt = $this->getClosestShipDate($cancelledProfileSubscriptionPlan->getCreatedAt(),
                $shipStartDate->getDeliveryStartPeriodDef()->getTimestamp());

            $firstOfShipmentMonth = new \DateTime();
            $firstOfShipmentMonth->setTimestamp($closestShipForStopAutorenew->getTimestamp());
            $firstOfShipmentMonth = $firstOfShipmentMonth->modify('first day of this month');

            if ($closestShipForStopAutorenew->getTimestamp() === $closestShipForCreatedAt->getTimestamp()) {
                if ($createdAtTime >= $firstOfShipmentMonth->getTimestamp() && $createdAtTime <= $closestShipForStopAutorenew->getTimestamp()) {
                    $churnedAt = $this->addPeriod($closestShipForCreatedAt, 0.5);
                    return $churnedAt;
                } else {
                    return $closestShipForCreatedAt->getTimestamp();
                }
            } else {
                return $closestShipForStopAutorenew->getTimestamp();
            }
        }
        if (in_array($planId, [2, 5, 8])) {
            $shipStartDate = $this->_subscriptionManager->getSubscriptionDates($subscription, 0);
            $closestShipmentDate = $this->getClosestShipDate(
                $cancelledProfileSubscriptionPlan->getCreatedAt(),
                $shipStartDate->getDeliveryStartPeriodDef()->getTimestamp());
            $churnedAt = $this->addPeriod($closestShipmentDate, 3);
            return $churnedAt;
        }
        if (in_array($planId, [3, 6, 9])) {
            $shipStartDate = $this->_subscriptionManager->getSubscriptionDates($subscription, 0);
            $closestShipmentDate = $this->getClosestShipDate(
                $cancelledProfileSubscriptionPlan->getCreatedAt(),
                $shipStartDate->getDeliveryStartPeriodDef()->getTimestamp());
            $churnedAt = $this->addPeriod($closestShipmentDate, 6);
            return $churnedAt;
        }

    }

    protected function _getStatusChangeDate($profileSubscriptionId, $statusId)
    {
        $sql = '
SELECT MIN(`created_at`) as `changed_status_at` FROM `profile_subscription_status_log` 
WHERE new_status = ?
AND profile_subscription_id = ?
';
        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([SubscriptionStatusEnum::asString($statusId), $profileSubscriptionId]);
        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        return  $resultSet[0]['changed_status_at'];
    }

    /**
     * @param $baseDateTime
     * @param $shipStartDateTime
     * @return \DateTime
     */

    public function getClosestShipDate(
        $baseDateTime,
        $shipStartDateTime
    ) {
        $shipStartDate = new \DateTime();
        $shipStartDate->setTimestamp($shipStartDateTime);

        $baseDate = new \DateTime();
        $baseDate->setTimestamp($baseDateTime);

        $interval = new \DateInterval("P2M");

        $current = $shipStartDate;
        while (true) {
            $baseStr = $baseDate->format(DATE_ATOM);
            $curStr = $current->format(DATE_ATOM);

            if ($baseDate->getTimestamp() < $current->getTimestamp()) {
                return $current;
            }

            $current->add($interval);
        }
    }

    public function addPeriod(
        \DateTime $shipDate,
        $periodsNum
    ) {
        $periodsNum = 2 * $periodsNum;
        $spec = 'P' . $periodsNum . 'M';
        $interval = new \DateInterval($spec);
        $shipDate->add($interval);
        $str = $shipDate->format(DATE_ATOM);
        return $shipDate->getTimestamp();
    }

    protected function updateChurn($churnedAt, $profileSubscriptionPlanId)
    {
        $sql = 'UPDATE `profile_subscription_plan` '
            . 'SET `churned_at` = ? '
            . 'WHERE `id` = ?';

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([$churnedAt, $profileSubscriptionPlanId]);
        $prepStmt->closeCursor();
    }

    protected function getShipmentsQueue($profileSubsId)
    {
        $sql = 'SELECT `id`, `schedule_index` FROM `profile_subscription_shipment` '
            . 'WHERE `profile_subscription_id` = ? '
            . 'ORDER BY `id` ASC';

        $pstmt = $this->_dbManager->prepare($sql);
        $pstmt->execute([$profileSubsId]);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);

        $profileSubsShipmentList = [];
        foreach ($resultSet as $dbRecord) {
            $profileSubsShipment = $this->_profileSubsShipDao->find($dbRecord['id']);
            $profileSubsShipmentList[] = $profileSubsShipment;
        }

        return $profileSubsShipmentList;
    }

    protected function getOptions()
    {
        return [
            [
                'status',
                null,
                InputOption::VALUE_REQUIRED,
                'Status of the subscription [cancelled|unpaid|finished]'
            ],
        ];
    }
}