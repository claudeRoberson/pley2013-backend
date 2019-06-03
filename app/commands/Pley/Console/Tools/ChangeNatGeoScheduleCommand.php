<?php

namespace Pley\Console\Tools;

/** @copyright Pley (c) 2017, All Rights Reserved */

use Illuminate\Console\Command;
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
use Pley\Entity\Payment\PaymentRetryLog;
use Pley\Repository\Payment\PaymentRetryLogRepository;

//PayPal Stuff
use PayPal\CoreComponentTypes\BasicAmountType;
use PayPal\EBLBaseComponents\AddressType;
use PayPal\EBLBaseComponents\BillingPeriodDetailsType;
use PayPal\EBLBaseComponents\UpdateRecurringPaymentsProfileRequestDetailsType;
use PayPal\EBLBaseComponents\CreditCardDetailsType;
use PayPal\PayPalAPI\UpdateRecurringPaymentsProfileReq;
use PayPal\PayPalAPI\UpdateRecurringPaymentsProfileRequestType;
use PayPal\Service\PayPalAPIInterfaceServiceService;

use PayPal\PayPalAPI\GetRecurringPaymentsProfileDetailsReq;
use PayPal\PayPalAPI\GetRecurringPaymentsProfileDetailsRequestType;


/**
 * The <kbd>GrantCreditToSubscribersCommand</kbd>
 *asd
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ChangeNatGeoScheduleCommand extends Command
{

    use \Pley\Console\ConsoleOutputTrait;
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyTB:Tools:change-nat-geo-schedule';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Change NatGeo schdule to bi-monthly basis';

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

    /**
     * @var \Pley\Billing\PaypalManager
     */
    protected $_paypalManager;

    const STRIPE_NAT_GEO_1_BOX_PLAN_ID = 2006;

    protected $stripeUpgradePlansMap = [
        '2000' => '2006',
        '2001' => '2007',
        '2002' => '2008',
        '2003' => '2006',
        '2004' => '2007',
        '2005' => '2009',
    ];

    protected $payPalNatGeo1BoxPlanIds = [
        'P-00W05512BG047562RXHB73DY',
        'P-32D83244SY7909015AKQ2O5Q'
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
        $this->_paymentRetryLogRepository = \App::make('\Pley\Repository\Payment\PaymentRetryLogRepository');
        $this->_paypalManager = \App::make('\Pley\Billing\PaypalManager');

        $this->_setLogOutput(true);
    }

    public function fire()
    {
        $this->info('Begin...');
        $natGeoProfileSubscriptions = $this->getNatGeoProfileSubscriptions();

        foreach ($natGeoProfileSubscriptions as $profileSubscription) {
            if(in_array($profileSubscription->getId(), [11835, 11838])){
                continue;
            }
            $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findLastByProfileSubscription($profileSubscription->getId());
            if(!$profileSubscriptionPlan){
                continue;
            }
            switch ($profileSubscriptionPlan->getVPaymentSystemId()) {
                case PaymentSystemEnum::STRIPE:
                    $this->updateStripeSubscription($profileSubscriptionPlan);
                    break;
                case PaymentSystemEnum::PAYPAL:
                    //$this->updatePayPalSubscripiton($profileSubscriptionPlan);
                    break;
            }
        }

        $this->info('Switch completed successfully...');
    }

    protected function getNatGeoProfileSubscriptions()
    {
        return $this->_profileSubscriptionDao->findBySubscription(SubscriptionEnum::NATIONAL_GEOGRAPHIC);
    }

    protected function updateStripeSubscription(ProfileSubscriptionPlan $profileSubscriptionPlan)
    {
        $vSubscriptionId = $profileSubscriptionPlan->getVPaymentSubscriptionId();

        $this->info('Processing Stripe subscription ID: ' . $vSubscriptionId);
        $stripeSubscription = \Stripe\Subscription::retrieve($vSubscriptionId);
        if(!array_key_exists($profileSubscriptionPlan->getVPaymentPlanId(), $this->stripeUpgradePlansMap)){
            $this->info('Plan map is not defined, skipping... ' );
            return;
        }

        $newPlanId = $this->stripeUpgradePlansMap[$profileSubscriptionPlan->getVPaymentPlanId()];

        $newTrialEnd = null;
        if($stripeSubscription->trial_end > time()){
            if($newPlanId === self::STRIPE_NAT_GEO_1_BOX_PLAN_ID){
                $newTrialEnd = $stripeSubscription->trial_end + 3600 * 24 * 30 * 1;
            }else{
                $newTrialEnd = $stripeSubscription->trial_end;
            }
        }else{
            $newTrialEnd = 1520251800; // 05 March 2018
        }

        try {
            \Stripe\Subscription::update($vSubscriptionId, [
                'plan' => $newPlanId,
                'trial_end' => $newTrialEnd,
                'prorate' => false,
            ]);
            $this->info('Updated subscription plan to: ' . $newPlanId);
            if ($newPlanId != self::STRIPE_NAT_GEO_1_BOX_PLAN_ID) {
                $stripeSubscription->cancel(['at_period_end' => true]);
                $this->info('Set cancel at period end...');
            }

/*            $sql = 'UPDATE `profile_subscription_plan` SET `v_payment_plan_id` = ?
            WHERE id=?;';*/

/*            $sql = 'UPDATE `profile_subscription_plan` SET `v_payment_plan_id` = ?, `payment_plan_id` = 6
            WHERE id=?;';

            $prepStmt = $this->_dbManager->prepare($sql);
            $prepStmt->execute([$newPlanId, $profileSubscriptionPlan->getId()]);*/
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return;
    }

    protected function updatePayPalSubscripiton(ProfileSubscriptionPlan $profileSubscriptionPlan)
    {
        $vSubscriptionId = $profileSubscriptionPlan->getVPaymentSubscriptionId();

        $this->info('Processing PayPal subscription ID: ' . $profileSubscriptionPlan->getVPaymentSubscriptionId());


        $ba = $this->_paypalManager->getBillingAgreement($vSubscriptionId);

        if(in_array($profileSubscriptionPlan->getVPaymentPlanId(), $this->payPalNatGeo1BoxPlanIds)){
            $billingCycles = 5;
        }else{
            $billingCycles = 1;
        }


        try {
            /* wrap API method calls on the service object with a try catch */
            $this->_paypalManager->updateBillingAgreement($vSubscriptionId, '{}');
            $this->info('Successfully updated subscription plan!');
            //$profileSubscriptionPlan->setVPaymentPlan(PaymentSystemEnum::STRIPE, $newPlanId);
            //$this->_profileSubscriptionPlanDao->save($profileSubscriptionPlan);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            exit;
        }
    }

    protected function getPayPalRecurringPaymentProfile($profileId)
    {
        $getRPPDetailsReqest = new GetRecurringPaymentsProfileDetailsRequestType();
        /*
         * (Required) Recurring payments profile ID returned in the CreateRecurringPaymentsProfile response. 19-character profile IDs are supported for compatibility with previous versions of the PayPal API.
         */
        $getRPPDetailsReqest->ProfileID = $profileId;


        $getRPPDetailsReq = new GetRecurringPaymentsProfileDetailsReq();
        $getRPPDetailsReq->GetRecurringPaymentsProfileDetailsRequest = $getRPPDetailsReqest;
        try {
            //$getRPPDetailsResponse = $this->_ppClassicApiService->GetRecurringPaymentsProfileDetails($getRPPDetailsReq);
        } catch (\Exception $ex) {
            exit;
        }
        //return $getRPPDetailsResponse;
    }
}