<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console\Tools;

use \Illuminate\Console\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Input\InputOption;

/**
 * The <kbd>StripeBillingDateAdjustCommand</kbd> allows us to adjust any changes on the billing dates
 * of registered people should the Subscription first box should ship at a later date or moved sooner
 * than the originally expected.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class StripeBillingDateAdjustCommand extends Command
{
    use \Pley\Console\ConsoleOutputTrait;
    
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:StripeDateAdjust';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Cronjob to Adjust Billing dates on Stripe after subscription release but charge date changed.';
    
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionManager;
    /** @var \Pley\User\UserSubscriptionManager */
    protected $_userSubscriptionMgr;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_paymentPlanDao;
    
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        
        $this->_dbManager           = \App::make('\Pley\Db\AbstractDatabaseManager');
        $this->_subscriptionManager = \App::make('\Pley\Subscription\SubscriptionManager');
        $this->_userSubscriptionMgr = \App::make('\Pley\User\UserSubscriptionManager');
        $this->_paymentPlanDao      = \App::make('\Pley\Dao\Payment\PaymentPlanDao');

        $this->_setLogOutput(true);
    }
    
    protected function getOptions()
    {
        return [
            [
                'subscriptionId',
                null,
                InputOption::VALUE_REQUIRED, 
                'Indicates the Subscription ID to be processed for Batch Label purchasing.'
            ],
        ];
    }
    
    public function fire()
    {
        $startTime = microtime(true);
        
        $subscriptionId = $this->input->getOption('subscriptionId');
        
        $subscription = $this->_subscriptionManager->getSubscription($subscriptionId);
        if (empty($subscription)) {
            throw new \Exception("No such subscription with ID {$subscriptionId}");
        }
        
        $this->line('Starting check/update process ----------------------------------------------');
        
        $subsPlanList = $this->_getPaidRecords();
        
        $toCheckCount = count($subsPlanList);
        $updatedCount = 0;
        
        $progressPrinter = new \Pley\Console\Util\ProgressPrinter();
        foreach ($subsPlanList as $subscriptionPlan) {
            $progressPrinter->step();
            
            $isUserUpdated = $this->_processPlan($subscription, $subscriptionPlan);
            
            if ($isUserUpdated) {
                $updatedCount++;
            }
        }
        $progressPrinter->finish();
        
        $endTime     = microtime(true);
        $elapsedTime = $endTime - $startTime;
        
        $this->line('Stats:');
        $this->line("Updated {$updatedCount} of {$toCheckCount} users to check.");
        $this->line(sprintf('Elapsed Time: %.3f secs', $elapsedTime));
    }
    
    /** @return __StripeBillingDateAdjustCommand_SubscriptionPlan[] */
    private function _getPaidRecords()
    {
        $sql = 'SELECT '
             .    '`id`, '
             .    '`user_id`, '
             .    '`payment_plan_id`, '
             .    '`v_payment_subscription_id`, '
             .    '`is_auto_renew`, '
             .    '`cancel_at`, '
             .    '`updated_at` '
             . 'FROM `profile_subscription_plan` '
             . 'WHERE `status` <> ? ';
        
        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute([\Pley\Enum\SubscriptionStatusEnum::CANCELLED]);
        
        $resultSet = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $prepStmt->rowCount();
        $prepStmt->closeCursor();
        
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = new __StripeBillingDateAdjustCommand_SubscriptionPlan($resultSet[$i]);
        }
        
        return $resultSet;
    }
    
    /**
     * Main method that will adjust the billing start date
     * @param \Pley\Entity\Subscription\Subscription            $subscription
     * @param __StripeBillingDateAdjustCommand_SubscriptionPlan $subscriptionPlan
     */
    private function _processPlan($subscription, $subscriptionPlan)
    {
        $stripeSubscription = \Stripe\Subscription::retrieve($subscriptionPlan->vPaymentSubscriptionId);
        $paymentPlan        = $this->_getPaymentPlan($subscriptionPlan->paymentPlanId);
        
        $trialEnd   = $stripeSubscription->trial_end;
        $nextCharge = $this->_userSubscriptionMgr->getFirstRecurringChargeDate($subscription, $paymentPlan);
        
        $isUserUpdated = false;
        
        if ($nextCharge != $trialEnd) {
            $isUserUpdated = true;
            
            $stripeSubscription->trial_end = $nextCharge;
            $stripeSubscription->save();
            
            if (!$subscriptionPlan->isAutoRenew) {
                $this->_updateCancelAt($subscriptionPlan, $nextCharge);
                $stripeSubscription->cancel(['at_period_end' => true]);
            }
        }
        
        return $isUserUpdated;
    }
    
    /**
     * Returns the PaymentPlan object for the supplied PlanId
     * @param int $planId
     * @return \Pley\Entity\Payment\PaymentPlan
     */
    private function _getPaymentPlan($planId)
    {
        if (!isset($this->_paymentPlanCache)) {
            $this->_paymentPlanCache = [];
        }
        
        if (!isset($this->_paymentPlanCache[$planId])) {
            $this->_paymentPlanCache[$planId] = $this->_paymentPlanDao->find($planId);
        }
        
        return $this->_paymentPlanCache[$planId];
    }
    
    /**
     * Update the Cancel At timestamp from the database to match that of the new next charge date
     * @param __StripeBillingDateAdjustCommand_SubscriptionPlan $subscriptionPlan
     * @param int                                               $nextChargeTimestamp
     */
    private function _updateCancelAt($subscriptionPlan, $nextChargeTimestamp)
    {
        if (!isset($this->_updatePstmt)) {
            $sql = 'UPDATE `profile_subscription_plan` '
                 . 'SET `cancel_at` = ?, '
                 .     '`updated_at` = ? '
                 . 'WHERE `id` = ?';
            $this->_updatePstmt = $this->_dbManager->prepare($sql);
        }
        
        $bindings = [
            \Pley\Util\DateTime::date($nextChargeTimestamp),
            $subscriptionPlan->updatedAt,
            $subscriptionPlan->id,
        ];
        
        $this->_updatePstmt->execute($bindings);
        $this->_updatePstmt->closeCursor();
    }
}




/** Helper class to parse DB data into an object */
class __StripeBillingDateAdjustCommand_SubscriptionPlan
{
    public $id;
    public $userId;
    public $paymentPlanId;
    public $vPaymentSubscriptionId;
    public $isAutoRenew;
    public $cancelAt;
    public $updatedAt;
    
    public function __construct($dbRecord)
    {
        $this->id                     = $dbRecord['id'];
        $this->userId                 = $dbRecord['user_id'];
        $this->paymentPlanId          = $dbRecord['payment_plan_id'];
        $this->vPaymentSubscriptionId = $dbRecord['v_payment_subscription_id'];
        $this->isAutoRenew            = $dbRecord['is_auto_renew'] == 1;
        $this->cancelAt               = \Pley\Util\Time\DateTime::strToTime($dbRecord['cancel_at']);
        $this->updatedAt              = $dbRecord['updated_at'];
    }
}
