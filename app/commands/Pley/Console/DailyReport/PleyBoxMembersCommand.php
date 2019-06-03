<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace Pley\Console\DailyReport;

use \Illuminate\Console\Command;
use Pley\Enum\SubscriptionStatusEnum;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * The <kbd>PleyBoxMembersCommand</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class PleyBoxMembersCommand extends Command
{

    use \Pley\Console\ConsoleOutputTrait;

    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:DR:PleyboxMembers';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Cronjob to notify Gift recipients.';

    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->_config = \App::make('\Pley\Config\ConfigInterface');
        $this->_dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_mail = \App::make('\Pley\Mail\AbstractMail');

        $this->_setLogOutput(true);
    }

    public function fire()
    {
        setlocale(LC_MONETARY, 'en_US');

        $totalsData = new __PBMC_TotalsData();
        $subsDataList = [];

        $subscritionInfoList = $this->_getSubscriptionInfoList();
        foreach ($subscritionInfoList as $subscritionInfo) {
            $subsDataList[] = $this->_getSubscriptionData(
                $subscritionInfo['id'], $subscritionInfo['name'], $totalsData
            );
        }

        $dailyCancelled = $this->_getDailyTotalCancelledSubscription();
        $dailyStoppedAutorenew = $this->_getDailyTotalStoppedAutorenew();

        $data = [
            'subscriptionDataList' => $subsDataList,
            'dailyCancelled' => $dailyCancelled,
            'dailyStoppedAutorenew' => $dailyStoppedAutorenew,
            'totalsData' => $totalsData,
        ];

        \Mail::send('email.daily-report.pleyboxMembers', $data, function (\Illuminate\Mail\Message $message) {
            $date = date('Y-m-d');
            $toMap = [
                'rananl@pley.com' => 'Ranan Lachman',
                'mayar@pley.com' => 'Maya Rand',
                'seva@pley.com' => 'Seva'
            ];

            $message->to($toMap)
                ->from('no-reply@pley.com', 'Pley Cronjob')
                ->subject('Pleybox Membership of ' . $date);
        });
    }

    private function _getSubscriptionInfoList()
    {
        $sql = 'SELECT `id`, `name` FROM `subscription`';
        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute();

        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();

        return $resultSet;
    }

    private function _getSubscriptionData($subscriptionId, $subscriptionName, __PBMC_TotalsData $totalsData)
    {
        $subsData = new __PBMC_SubscriptionData($subscriptionId, $subscriptionName);
        $dailyData = new __PBMC_SubscriptionDailyData();
        $dailyData->addStatsItem($this->_getDailyNewSubscriptions($subscriptionId), 'new');
        $dailyData->addStatsItem($this->_getDailyCancelledSubscriptions($subscriptionId), 'cancelled');
        $dailyData->addStatsItem($this->_getDailyStoppedSubscriptions($subscriptionId), 'stopped');

        $subsData->setDailyData($dailyData);

        $perPeriodDataList = $this->_getSubscriptionPerPeriod($subscriptionId);
        foreach ($perPeriodDataList as $perPeriodData) {
            $subsData->addPlanData($perPeriodData);
        }

        $subsData->activeCount = $this->_getActiveMemberCount($subscriptionId);
        $subsData->canceledCount = $this->_getCanceledSubscriptionCount($subscriptionId);
        $subsData->nonAutorenewCount = $this->_getNonAutorenewSubscriptionCount($subscriptionId);

        $totalsData->activeCount += $subsData->activeCount;
        $totalsData->canceledCount += $subsData->canceledCount;

        $totalsData->dailyCount += $subsData->dailyData->getStatsItem('new')->getCount();
        $totalsData->dailyRevenue += $subsData->dailyData->getStatsItem('new')->getRevenue();

        return $subsData;
    }

    /** @return __PBMC_SubscriptionPlanData[] */
    private function _getSubscriptionPerPeriod($subscriptionId)
    {
        $sql = 'SELECT '
            . 'COUNT(*) AS `subscription_count`, '
            . '`pp`.`period`, '
            . '`pp`.`period_unit`, '
            . '`pp_x_vpp`.`unit_price` as `unit_price`, '
            . 'SUM(`pp_x_vpp`.`total`) AS `revenue` '
            . 'FROM `profile_subscription_plan` AS `psp` '
            . 'JOIN `profile_subscription` as `ps` ON `psp`.`profile_subscription_id` = `ps`.`id` '
            . 'JOIN `payment_plan_x_vendor_payment_plan` AS `pp_x_vpp` ON `psp`.`v_payment_plan_id` = `pp_x_vpp`.`v_payment_plan_id` '
            . 'JOIN `payment_plan` AS `pp` ON `pp`.`id` = `pp_x_vpp`.`payment_plan_id` '
            . 'WHERE `ps`.`subscription_id` = ? '
            . 'GROUP BY `pp`.`period`';

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([$subscriptionId]);

        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();

        for ($i = 0; $i < count($resultSet); $i++) {
            $resultSet[$i] = new __PBMC_SubscriptionPlanData($resultSet[$i]);
        }

        return $resultSet;
    }

    private function _getDailyNewSubscriptions($subscriptionId)
    {
        $sql = 'SELECT COUNT(*) AS `subscription_count`, SUM(`pp_x_vpp`.`total`) AS `revenue` '
            . 'FROM `profile_subscription_plan` AS `psp` '
            . 'JOIN `profile_subscription` as `ps` ON `psp`.`profile_subscription_id` = `ps`.`id` '
            . 'JOIN `payment_plan_x_vendor_payment_plan` AS `pp_x_vpp` ON `psp`.`v_payment_plan_id` = `pp_x_vpp`.`v_payment_plan_id` '
            . 'JOIN `payment_plan` AS `pp` ON `pp`.`id` = `pp_x_vpp`.`payment_plan_id` '
            . 'WHERE `ps`.`subscription_id` = ? '
            . 'AND `ps`.`created_at` BETWEEN '
            . 'DATE_FORMAT(CONVERT_TZ(NOW(), "UTC", "America/Los_Angeles"),"%Y-%m-%d 00:00:00") AND '
            . 'DATE_FORMAT(CONVERT_TZ(NOW(), "UTC", "America/Los_Angeles"),"%Y-%m-%d 23:59:59")';

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([$subscriptionId]);

        $dbRecord = $prepStmt->fetch(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();
        return $dbRecord;
    }

    private function _getDailyCancelledSubscriptions($subscriptionId)
    {
        $sql = 'SELECT COUNT(*) AS `subscription_count`, SUM(`pp_x_vpp`.`total`) AS `revenue` '
            . 'FROM `profile_subscription_plan` AS `psp` '
            . 'JOIN `profile_subscription` as `ps` ON `psp`.`profile_subscription_id` = `ps`.`id` '
            . 'JOIN `payment_plan_x_vendor_payment_plan` AS `pp_x_vpp` ON `psp`.`v_payment_plan_id` = `pp_x_vpp`.`v_payment_plan_id` '
            . 'JOIN `payment_plan` AS `pp` ON `pp`.`id` = `pp_x_vpp`.`payment_plan_id` '
            . 'WHERE `ps`.`subscription_id` = ? AND `ps`.`status` = ? '
            . 'AND `ps`.`updated_at` BETWEEN '
            . 'DATE_FORMAT(CONVERT_TZ(NOW(), "UTC", "America/Los_Angeles"),"%Y-%m-%d 00:00:00") AND '
            . 'DATE_FORMAT(CONVERT_TZ(NOW(), "UTC", "America/Los_Angeles"),"%Y-%m-%d 23:59:59")';

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([$subscriptionId, SubscriptionStatusEnum::CANCELLED]);

        $dbRecord = $prepStmt->fetch(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();
        return $dbRecord;
    }

    private function _getDailyStoppedSubscriptions($subscriptionId)
    {
        $sql = 'SELECT COUNT(*) AS `subscription_count`, SUM(`pp_x_vpp`.`total`) AS `revenue` '
            . 'FROM `profile_subscription_plan` AS `psp` '
            . 'JOIN `profile_subscription` as `ps` ON `psp`.`profile_subscription_id` = `ps`.`id` '
            . 'JOIN `payment_plan_x_vendor_payment_plan` AS `pp_x_vpp` ON `psp`.`v_payment_plan_id` = `pp_x_vpp`.`v_payment_plan_id` '
            . 'JOIN `payment_plan` AS `pp` ON `pp`.`id` = `pp_x_vpp`.`payment_plan_id` '
            . 'WHERE `ps`.`subscription_id` = ? AND `ps`.`status` = ? '
            . 'AND `ps`.`updated_at` BETWEEN '
            . 'DATE_FORMAT(CONVERT_TZ(NOW(), "UTC", "America/Los_Angeles"),"%Y-%m-%d 00:00:00") AND '
            . 'DATE_FORMAT(CONVERT_TZ(NOW(), "UTC", "America/Los_Angeles"),"%Y-%m-%d 23:59:59")';

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([$subscriptionId, SubscriptionStatusEnum::STOPPED]);

        $dbRecord = $prepStmt->fetch(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();
        return $dbRecord;
    }

    private function _getDailyTotalCancelledSubscription()
    {
        $sql = 'SELECT COUNT(*) AS `count` FROM `profile_subscription` '
            . 'WHERE `status` = 3 '
            . 'AND DATE(CONVERT_TZ(`updated_at`, "UTC", "America/Los_Angeles")) =  '
            . 'DATE(CONVERT_TZ(NOW(), "UTC", "America/Los_Angeles"));';

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute();

        $dbRecord = $prepStmt->fetch(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();

        $count = $dbRecord['count'];
        return $count;
    }

    private function _getDailyTotalStoppedAutorenew()
    {
        $sql = 'SELECT COUNT(*) AS `count` FROM `profile_subscription` '
            . 'WHERE `status` = 8 '
            . 'AND DATE(CONVERT_TZ(`updated_at`, "UTC", "America/Los_Angeles")) =  '
            . 'DATE(CONVERT_TZ(NOW(), "UTC", "America/Los_Angeles"));';

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute();

        $dbRecord = $prepStmt->fetch(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();

        $count = $dbRecord['count'];
        return $count;
    }

    private function _getActiveMemberCount($subscriptionId)
    {
        return $this->_getSubscriptionCount($subscriptionId, \Pley\Enum\SubscriptionStatusEnum::ACTIVE);
    }

    private function _getCanceledSubscriptionCount($subscriptionId)
    {
        return $this->_getSubscriptionCount($subscriptionId, \Pley\Enum\SubscriptionStatusEnum::CANCELLED);
    }

    private function _getNonAutorenewSubscriptionCount($subscriptionId)
    {
        return $this->_getSubscriptionCount($subscriptionId, \Pley\Enum\SubscriptionStatusEnum::STOPPED);
    }

    private function _getSubscriptionCount($subscriptionId, $statusId)
    {
        $sql = 'SELECT COUNT(*) AS `count` FROM `profile_subscription` '
            . 'WHERE `subscription_id` = ? AND `status` = ? ';
        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([$subscriptionId, $statusId]);

        $dbRecord = $prepStmt->fetch(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();

        return $dbRecord['count'];
    }
}


class __PBMC_SubscriptionData
{
    /** @var int */
    public $id;
    /** @var string */
    public $name;
    /** @var __PBMC_SubscriptionPlanData[] */
    public $planDataList = [];
    /** @var __PBMC_SubscriptionDailyData */
    public $dailyData = null;
    /** @var int */
    public $canceledCount;
    /** @var int */
    public $nonAutorenewCount;
    /** @var int */
    public $activeCount;

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    public function addPlanData(__PBMC_SubscriptionPlanData $planData)
    {
        $this->planDataList[] = $planData;
    }

    public function setDailyData(__PBMC_SubscriptionDailyData $dailyData)
    {
        $this->dailyData = $dailyData;
    }

    public function getCountTotal()
    {
        $total = 0;
        /* @var $planData __PBMC_SubscriptionPlanData */
        foreach ($this->planDataList as $planData) {
            $total += $planData->count;
        }
        return $total;
    }

    public function getRevenueTotal()
    {
        $total = 0;
        /* @var $planData __PBMC_SubscriptionPlanData */
        foreach ($this->planDataList as $planData) {
            $total += $planData->revenue;
        }

        return money_format('%(n', $total);
    }
}

class __PBMC_SubscriptionPlanData
{
    /** @var int */
    public $count;
    /** @var int */
    public $period;
    /** @var int */
    public $periodUnit;
    /** @var float */
    public $unitPrice;
    /** @var float */
    public $revenue;

    public function __construct($dbRecord)
    {
        $this->count = $dbRecord['subscription_count'];
        $this->period = $dbRecord['period'];
        $this->periodUnit = $dbRecord['period_unit'];
        $this->unitPrice = $dbRecord['unit_price'];
        $this->revenue = $dbRecord['revenue'];
    }

    public function getUnitPrice()
    {
        return money_format('%(n', $this->unitPrice);
    }

    public function getRevenue()
    {
        return money_format('%(n', $this->revenue);
    }
}

class __PBMC_SubscriptionDailyData
{
    protected $items = [];

    public function addStatsItem($dbRecord, $type)
    {
        $this->items[$type] = new __PBMC_SubscriptionDailyDataItem(
            $dbRecord['subscription_count'],
            ($dbRecord['revenue']) ? $dbRecord['revenue'] : 0,
            $type
        );
    }

    public function getStatsItem($type)
    {
        return $this->items[$type];
    }
}

class __PBMC_SubscriptionDailyDataItem
{
    public $type;

    protected $count;

    protected $revenue;

    public function __construct($count = 0, $revenue = 0, $type = null)
    {
        $this->count = $count;
        $this->revenue = $revenue;
        $this->type = $type;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getRevenue()
    {
        return money_format('%(n', $this->revenue);
    }
}


class __PBMC_TotalsData
{
    /** @var int */
    public $canceledCount;
    /** @var int */
    public $activeCount;
    /** @var int */
    public $dailyCount;
    /** @var float */
    public $dailyRevenue;

    public function getDailyRevenue()
    {
        return money_format('%(n', $this->dailyRevenue);
    }
}