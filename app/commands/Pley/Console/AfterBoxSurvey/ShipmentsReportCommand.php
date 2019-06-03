<?php

namespace Pley\Console\AfterBoxSurvey;

use \Illuminate\Console\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use \Pley\Enum\Shipping\ShipmentStatusEnum;
use \Pley\Util\DateTime;

/** @copyright Pley (c) 2017, All Rights Reserved */

/**
 * The <kbd>ShipmentsReportCommand</kbd>
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ShipmentsReportCommand extends Command
{

    use \Pley\Console\ConsoleOutputTrait;

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:ABS:ShipmentsReport';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Cronjob to send shipments report';

    const AB_TEST_SEND_OUT_PAST_DAYS = 21;

    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;

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
    /** @var \Pley\Repository\User\UserRepository */
    protected $_userRepository;


    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->_config = \App::make('\Pley\Config\ConfigInterface');
        $this->_dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_mail = \App::make('\Pley\Mail\AbstractMail');

        $this->_profileSubsDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionDao');
        $this->_profileSubsShipDao = \App::make('\Pley\Dao\Profile\ProfileSubscriptionShipmentDao');
        $this->_paymentPlanDao = \App::make('\Pley\Dao\Payment\PaymentPlanDao');
        $this->_subscriptionManager = \App::make('\Pley\Subscription\SubscriptionManager');
        $this->_userSubscriptionMgr = \App::make('\Pley\User\UserSubscriptionManager');
        $this->_subscriptionRepo = \App::make('\Pley\Repository\Subscription\SubscriptionRepository');
        $this->_userRepository = \App::make('\Pley\Repository\User\UserRepository');

        $this->_setLogOutput(true);
    }


    protected function getOptions()
    {
        return [
            [
                'daysAgo',
                null,
                InputOption::VALUE_OPTIONAL,
                'Days ago value [integer]'
            ],
        ];
    }

    public function fire()
    {
        setlocale(LC_MONETARY, 'en_US');
        $this->line('Begin shipments report...');

        $daysAgoOverride = (int)$this->input->getOption('daysAgo');

        $daysAgoNum = ($daysAgoOverride) ? $daysAgoOverride : 14;

        $data = [
            'subscriptions' => []
        ];

        $subscriptionsList = $this->_subscriptionManager->getAllSubscriptions();

        $data['subscriptions'][] = [
            'id' => 1,
            'name' => 'Disney Princess',
            'daysAgo' => self::AB_TEST_SEND_OUT_PAST_DAYS,
            'shipmentsDelivered' => []
        ];

        foreach ($subscriptionsList as $subscription) {
            $data['subscriptions'][] = [
                'id' => $subscription->getId(),
                'name' => $subscription->getName(),
                'daysAgo' => $daysAgoNum,
                'shipmentsDelivered' => []
            ];
        }
        foreach ($data['subscriptions'] as &$sub) {
            $sub['shipmentsDelivered'] = $this->_getSubscriptionShipmentsDelivered($sub['id'], $sub['daysAgo']);
        }

        \Mail::send('email.shipments-report.report', $data, function (\Illuminate\Mail\Message $message) {
            $toMap = [
                'vsevolod.yatsuk@agileengine.com' => 'Seva Yatsyuk',
                'lavanyad@pley.com' => 'Lavanya Duggirala',
                'amkuehndorf@gmail.com' => 'Amanda Kuehndorf',
            ];

            $message->to($toMap)
                ->from('no-reply@pley.com', 'Pley Cronjob')
                ->subject('[Pleybox] Shipments Delivered Report');
        });
        $this->info('Report successfully sent.');
    }

    protected function _getSubscriptionShipmentsDelivered($subscriptionId, $daysAgo)
    {
        $shipmentsDelivered = [];
        $deliveredDate = date("Y-m-d", strtotime("-" . $daysAgo . " days"));

        $this->line('Sending out shipments delivered at: ' . $deliveredDate);

        $sql = '
        SELECT id, user_id, delivered_at  from profile_subscription_shipment 
        WHERE status = ? 
        AND DATE(delivered_at) = ?
        AND subscription_id = ? ORDER BY delivered_at DESC;
        ';

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([
            ShipmentStatusEnum::DELIVERED,
            $deliveredDate,
            $subscriptionId
        ]);

        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();

        /**
         * According to requirements for Disney we should send only half of
         * boxes after 14 days and the rest for the 21 day.
         * To test the email open rate
         *
         */
        if ($subscriptionId === 1 && $daysAgo === self::AB_TEST_SEND_OUT_PAST_DAYS) {
            $resultSet = array_reverse($resultSet);
        }
        $shipmentsCount = count($resultSet);
        $median = (int)round($shipmentsCount / 2);
        $i = 0;
        foreach ($resultSet as $result) {
            if ($subscriptionId === 1 && $i === $median) {
                break;
            }
            $shipment = $this->_profileSubsShipDao->find($result['id']);
            $user = $this->_userRepository->find($result['user_id']);
            $box = $this->_subscriptionManager->getItem($shipment->getItemId());
            $shipment->boxName = $box->getName();
            $shipment->user = $user;
            $shipmentsDelivered[] = $shipment;
            $i++;
        }
        return $shipmentsDelivered;
    }
}