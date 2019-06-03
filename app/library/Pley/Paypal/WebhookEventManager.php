<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Paypal;

use Pley\Config\ConfigInterface as Config;
use Pley\Dao\Payment\UserPaymentMethodDao;
use Pley\Dao\Profile\ProfileSubscriptionDao;
use Pley\Dao\Profile\ProfileSubscriptionPlanDao;
use Pley\Dao\Profile\ProfileSubscriptionTransactionDao;
use Pley\Dao\User\UserProfileDao;
use Pley\Entity\Paypal\PaypalWebhookLog;
use Pley\Entity\Profile\ProfileSubscriptionTransaction;
use Pley\Enum\Paypal\WebhookEventTypeEnum;
use Pley\Enum\SubscriptionCancelSourceEnum;
use Pley\Enum\SubscriptionStatusEnum;
use Pley\Mail\AbstractMail as Mail;
use Pley\Repository\User\UserRepository;
use Pley\Subscription\SubscriptionManager;
use Pley\User\UserBillingManager;
use Pley\User\UserSubscriptionManager;
use Pley\Billing\PaypalManager;
use Pley\Repository\Paypal\PaypalWebhookLogRepository;

/**
 * Manager class for webhook initiated operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class WebhookEventManager
{
    /**
     * @var Mail
     */
    protected $_mailer;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var UserRepository
     */
    protected $_userRepository;
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
     * @var \Pley\Dao\User\UserProfileDao
     */
    protected $_userProfileDao;

    /**
     * @var UserPaymentMethodDao
     */
    protected $_userPaymentMethodDao;

    /**
     * @var SubscriptionManager
     */
    protected $_subscriptionManager;

    /**
     * @var UserSubscriptionManager
     */
    protected $_userSubscriptionManager;

    /**
     * @var \Pley\User\UserBillingManager
     */
    protected $_userBillingMgr;

    /**
     * @var \Pley\Billing\PaypalManager
     */
    protected $_paypalManager;

    /**
     * @var \Pley\Repository\Paypal\PaypalWebhookLogRepository
     */
    protected $_paypalWebhookLogRepository;


    public function __construct(
        Mail $mailer,
        Config $config,
        UserRepository $userRepository,
        ProfileSubscriptionDao $profileSubscriptionDao,
        ProfileSubscriptionTransactionDao $profileSubscriptionTransactionDao,
        ProfileSubscriptionPlanDao $profileSubscriptionPlanDao,
        UserProfileDao $userProfileDao,
        UserPaymentMethodDao $userPaymentMethodDao,
        SubscriptionManager $subscriptionManager,
        UserSubscriptionManager $userSubscriptionManager,
        UserBillingManager $userBillingManager,
        PaypalManager $paypalManager,
        PaypalWebhookLogRepository $paypalWebhookLogRepository
    ) {
        $this->_mailer = $mailer;
        $this->_config = $config;
        $this->_userRepository = $userRepository;
        $this->_profileSubscriptionDao = $profileSubscriptionDao;
        $this->_userProfileDao = $userProfileDao;
        $this->_profileSubscriptionTransactionDao = $profileSubscriptionTransactionDao;
        $this->_profileSubscriptionPlanDao = $profileSubscriptionPlanDao;
        $this->_userPaymentMethodDao = $userPaymentMethodDao;
        $this->_subscriptionManager = $subscriptionManager;
        $this->_userSubscriptionManager = $userSubscriptionManager;
        $this->_userBillingMgr = $userBillingManager;
        $this->_paypalManager = $paypalManager;
        $this->_paypalWebhookLogRepository = $paypalWebhookLogRepository;
    }

    public function handleSuccessfulPayment(\PayPal\Api\WebhookEvent $event)
    {
        $paymentData = $event->getResource()->toArray();
        $chargeId = $paymentData['id'];
        $paypalSubscriptionId = $paymentData['billing_agreement_id'];
        $createdAtTime = \Pley\Util\DateTime::paypalDateToTime($event->getCreateTime());
        $amount = (float)$paymentData['amount']['total'];

        $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findByVendorSubscriptionId($paypalSubscriptionId);

        if (!$profileSubscriptionPlan) {
            $this->_logEvent($event, WebhookEventTypeEnum::TYPE_SUBSCRIPTION_NOT_FOUND);
            throw new \Exception(sprintf('Subscription ID: [%s] does not exist from webhook ID: [%s]',
                $paypalSubscriptionId, $event->getId()));
        }

        $initialTransaction = $this->_profileSubscriptionTransactionDao->findByChargeId($paypalSubscriptionId);

        if ($initialTransaction !== null) {
            /**
             * Paypal sends a webhook even for a very first transaction (initial payment), and we don't have
             * this Paypal payment ID at the stage of subscription setup, so at that stage we just assigned a billing_agreement_id
             * to a v_payment_transaction_id at the stage of the subscription creation
             *
             * This produces the following case:
             *
             * IF transaction is found with v_payment_transaction_id === $paypalSubscriptionId
             * THEN this is a first transaction and we should update transaction id to a correct one.
             *
             * This is a workaround, and there should be a better way to do it.
             *
             */
            if ($initialTransaction->getTransactionAt() >= time() - 3600 * 24) {
                /*
                 * Only transaction, which is not older than a day can be
                 * considered as the very first one and makes TXN ID updated here.
                 * If transaction, was found, but it's older than a day, then proceed with a
                 * standard flow as we're dealing with a valid recurring payment in this case.
                 *
                 * */
                $initialTransaction->setVPaymentTransactionId($chargeId);
                $this->_profileSubscriptionTransactionDao->save($initialTransaction);
                $this->_logEvent($event);

                return;
            }
        }

        if ($this->_profileSubscriptionTransactionDao->findByChargeId($chargeId) !== null) {
            /**
             * Stop further processing as we already have such a charge transaction in DB.
             * Duplicate webhooks are possible.
             */
            return;
        }

        $profileSubscription = $this->_profileSubscriptionDao->find($profileSubscriptionPlan->getProfileSubscriptionId());
        $subscription = $this->_subscriptionManager->getSubscription($profileSubscription->getSubscriptionId());
        $user = $this->_userRepository->find($profileSubscriptionPlan->getUserId());
        $paymentMethod = $this->_userPaymentMethodDao->find($profileSubscription->getUserPaymentMethodId());

        $profileSubscriptionTransaction = ProfileSubscriptionTransaction::withNew(
            $user->getId(),
            $profileSubscription->getUserProfileId(),
            $profileSubscription->getId(),
            $profileSubscriptionPlan->getId(),
            $paymentMethod->getId(),
            \Pley\Enum\TransactionEnum::CHARGE,
            $amount,
            $paymentMethod->getVPaymentSystemId(),
            $paymentMethod->getVPaymentMethodId(),
            $chargeId,
            $createdAtTime,
            $amount,
            0,
            null,
            null
        );
        $this->_profileSubscriptionTransactionDao->save($profileSubscriptionTransaction);

        $numBoxes = $this->_subscriptionManager->getSubscriptionBoxCount(
            $subscription->getId(),
            $profileSubscriptionPlan->getPaymentPlanId());

        $this->_userSubscriptionManager->reservedToPaid($profileSubscription, $numBoxes);
        $activePeriodIdx = $this->_subscriptionManager->getActivePeriodIndex($subscription);
        $this->_userSubscriptionManager->queueShipment($profileSubscription, $subscription, $activePeriodIdx);

        $profileSubscription->setStatus(SubscriptionStatusEnum::ACTIVE);
        $profileSubscriptionPlan->setStatus(SubscriptionStatusEnum::ACTIVE);
        $this->_profileSubscriptionDao->save($profileSubscription);
        $this->_profileSubscriptionPlanDao->save($profileSubscriptionPlan);

        $this->_logEvent($event);
    }

    public function handleFailedPayment(\PayPal\Api\WebhookEvent $event)
    {
        $paymentData = $event->getResource()->toArray();
        $chargeId = $paymentData['id'];
        $paypalSubscriptionId = $paymentData['billing_agreement_id'];
        $createdAtTime = \Pley\Util\DateTime::paypalDateToTime($event->getCreateTime());
        $amount = (float)$paymentData['amount']['total'];

        $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findByVendorSubscriptionId($paypalSubscriptionId);

        if (!$profileSubscriptionPlan) {
            $this->_logEvent($event, WebhookEventTypeEnum::TYPE_SUBSCRIPTION_NOT_FOUND);
            throw new \Exception(sprintf('Subscription ID: [%s] does not exist', $paypalSubscriptionId));
        }

        if ($this->_profileSubscriptionTransactionDao->findByChargeId($chargeId,
                \Pley\Enum\TransactionEnum::FAILED) !== null
        ) {
            /**
             * Stop further processing as we already have such a charge transaction in DB
             */
            return;
        }

        $profileSubscription = $this->_profileSubscriptionDao->find($profileSubscriptionPlan->getProfileSubscriptionId());
        $subscription = $this->_subscriptionManager->getSubscription($profileSubscription->getSubscriptionId());
        $user = $this->_userRepository->find($profileSubscriptionPlan->getUserId());
        $paymentMethod = $this->_userPaymentMethodDao->find($profileSubscription->getUserPaymentMethodId());

        $profileSubscriptionTransaction = ProfileSubscriptionTransaction::withNew(
            $user->getId(),
            $profileSubscription->getUserProfileId(),
            $profileSubscription->getId(),
            $profileSubscriptionPlan->getId(),
            $paymentMethod->getId(),
            \Pley\Enum\TransactionEnum::FAILED,
            $amount,
            $paymentMethod->getVPaymentSystemId(),
            $paymentMethod->getVPaymentMethodId(),
            $chargeId,
            $createdAtTime,
            $amount,
            0,
            null,
            null
        );
        $this->_profileSubscriptionTransactionDao->save($profileSubscriptionTransaction);

        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($user);
        $mailTagCollection->addEntity($subscription);
        $mailUserTo = \Pley\Mail\MailUser::withUser($user);

        $profileSubscription->setStatus(SubscriptionStatusEnum::PAST_DUE);
        $profileSubscriptionPlan->setStatus(SubscriptionStatusEnum::PAST_DUE);

        $mailTagCollection->setCustom('amountDue', number_format($amount, 2));
        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::SUBSCRIPTION_PAYMENT_ATTEMPT_FAILED;

        $this->_profileSubscriptionDao->save($profileSubscription);
        $this->_profileSubscriptionPlanDao->save($profileSubscriptionPlan);

        $this->_logEvent($event);

        $this->_mailer->send($mailTemplateId, $mailTagCollection, $mailUserTo);

    }

    public function handleSubscriptionCancel(\PayPal\Api\WebhookEvent $event)
    {
        $subscriptionData = $event->getResource()->toArray();
        $paypalSubscriptionId = $subscriptionData['id'];

        $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findByVendorSubscriptionId($paypalSubscriptionId);

        if (!$profileSubscriptionPlan) {
            $this->_logEvent($event, WebhookEventTypeEnum::TYPE_SUBSCRIPTION_NOT_FOUND);
            throw new \Exception(sprintf('Subscription ID: [%s] does not exist', $paypalSubscriptionId));
        }

        // Since some cancellations can be performed directly by customer service, the Subscription Plan
        // would already be cancelled and thus no need to try to override what's already cancelled
        if ($profileSubscriptionPlan->getStatus() == SubscriptionStatusEnum::CANCELLED) {
            return;
        }

        $canceledAtTime = \Pley\Util\DateTime::paypalDateToTime($subscriptionData['agreement_details']['next_billing_date'],
            'Y-m-d\TH:i:s\Z');

        $profileSubscriptionPlan->stopAutoRenewal(SubscriptionCancelSourceEnum::PAYMENT_SYSTEM, $canceledAtTime);

        $profileSubscription = $this->_profileSubscriptionDao->find($profileSubscriptionPlan->getProfileSubscriptionId());
        $profileSubscription->updateWithSubscriptionPlan($profileSubscriptionPlan);

        $this->_profileSubscriptionDao->save($profileSubscription);
        $this->_profileSubscriptionPlanDao->save($profileSubscriptionPlan);
        $this->_userSubscriptionManager->clearReserved($profileSubscription);

        $this->_logEvent($event);

        $subscription = $this->_subscriptionManager->getSubscription($profileSubscription->getSubscriptionId());
        $user = $this->_userRepository->find($profileSubscriptionPlan->getUserId());
        $userProfile = $this->_userProfileDao->find($profileSubscription->getUserProfileId());

        // Sending notification email
        $mailOptions = ['subjectName' => $subscription->getName()];
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($user);
        $mailTagCollection->addEntity($subscription);
        $mailTagCollection->addEntity($userProfile);

        $mailUserTo = \Pley\Mail\MailUser::withUser($user);

        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::SUBSCRIPTION_CANCEL;
        $this->_mailer->send($mailTemplateId, $mailTagCollection, $mailUserTo, $mailOptions);
    }

    public function initWebhookEvent($headers, $body)
    {
        return $this->_paypalManager->getWebhookEvent($headers, $body);
    }

    protected function _logEvent(\PayPal\Api\WebhookEvent $event, $type = null)
    {
        $eventType = ($type) ? $type : $event->getEventType();
        $paypalWebhookLog = new PaypalWebhookLog();
        $paypalWebhookLog->setEventId($event->getId())
            ->setEventType($eventType)
            ->setEventPayload($event->toJSON());
        $this->_paypalWebhookLogRepository->save($paypalWebhookLog);
    }
}
