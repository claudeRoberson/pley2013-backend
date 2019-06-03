<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Stripe;

use Pley\Config\ConfigInterface as Config;
use Pley\Dao\Payment\UserPaymentMethodDao;
use Pley\Dao\Profile\ProfileSubscriptionDao;
use Pley\Dao\Profile\ProfileSubscriptionPlanDao;
use Pley\Dao\Profile\ProfileSubscriptionTransactionDao;
use Pley\Entity\Payment\UserPaymentMethod;
use Pley\Entity\Profile\ProfileSubscription;
use Pley\Entity\Profile\ProfileSubscriptionPlan;
use Pley\Entity\Profile\ProfileSubscriptionTransaction;
use Pley\Entity\Stripe\WebhookLog;
use Pley\Entity\Subscription\Subscription;
use Pley\Entity\User\User;
use Pley\Enum\SubscriptionStatusEnum;
use Pley\Enum\TransactionEnum;
use Pley\Exception\Stripe\SubscriptionNotFoundException;
use Pley\Http\Response\ResponseCode;
use Pley\Mail\AbstractMail as Mail;
use Pley\Repository\Stripe\WebhookLogRepository;
use Pley\Repository\User\UserRepository;
use Pley\Stripe\Event as StripeEvent;
use Pley\Subscription\SubscriptionManager;
use Pley\User\UserBillingManager;
use Pley\User\UserSubscriptionManager;

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
     * @var WebhookLogRepository
     */
    protected $_webhookLogRepository;

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
     * @var StripeEvent
     */
    protected $_stripeEvent;

    public function __construct(
        Mail $mailer,
        Config $config,
        UserRepository $userRepository,
        WebhookLogRepository $webhookLogRepository,
        ProfileSubscriptionDao $profileSubscriptionDao,
        ProfileSubscriptionTransactionDao $profileSubscriptionTransactionDao,
        ProfileSubscriptionPlanDao $profileSubscriptionPlanDao,
        UserPaymentMethodDao $userPaymentMethodDao,
        SubscriptionManager $subscriptionManager,
        UserSubscriptionManager $userSubscriptionManager,
        UserBillingManager $userBillingManager
    )
    {
        $this->_mailer = $mailer;
        $this->_config = $config;
        $this->_userRepository = $userRepository;
        $this->_webhookLogRepository = $webhookLogRepository;
        $this->_profileSubscriptionDao = $profileSubscriptionDao;
        $this->_profileSubscriptionTransactionDao = $profileSubscriptionTransactionDao;
        $this->_profileSubscriptionPlanDao = $profileSubscriptionPlanDao;
        $this->_userPaymentMethodDao = $userPaymentMethodDao;
        $this->_subscriptionManager = $subscriptionManager;
        $this->_userSubscriptionManager = $userSubscriptionManager;
        $this->_userBillingMgr = $userBillingManager;
    }

    /**
     * Handles a invoice.payment_succeeded Stripe event
     * @param Event $event
     */
    public function handleRecurringTransaction(StripeEvent $event)
    {
        $this->_validateType($event, StripeEvent::TYPE_INVOICE_PAYMENT_SUCCEEDED);

        $amount = (float)$event->getObjectData('total') / 100;
        $stripeSubscriptionId = $event->getObjectData('subscription');
        $chargeId = $event->getObjectData('charge');
        $createdAt = $event->getObjectData('date');

        if (!$stripeSubscriptionId || !$chargeId) {
            //completely skipping non-subscription based charges or ones with missing chargeId
            return;
        }
        if ($amount === 0) {
            /**
             * ignore so called 'free invoices' they're created when customer is granted with a trial periods in Stripe,
             * which is used within a skip-a-box feature
             */
            return;
        }
        if ($this->_profileSubscriptionTransactionDao->findByChargeId($chargeId) !== null) {
            /**
             * Stop further processing as we already have such a charge transaction in DB
             * Stripe Documentation mentions, duplicate events are possible
             */
            return;
        }
        $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findByVendorSubscriptionId($stripeSubscriptionId);

        if (!$profileSubscriptionPlan) {
            $this->_handleNonExistingSubscription($event, $stripeSubscriptionId);
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
            $user->getVPaymentSystemId(),
            $paymentMethod->getVPaymentMethodId(),
            $chargeId,
            $createdAt,
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

        return;
    }

    /**
     * Handles a invoice.payment_failed Stripe event
     * @param Event $event
     */
    public function handleFailedPayment(StripeEvent $event)
    {
        $this->_validateType($event, StripeEvent::TYPE_INVOICE_PAYMENT_FAILED);

        $stripeSubscriptionId = $event->getObjectData('subscription');
        $amount = (float)$event->getObjectData('total') / 100;
        $isLastPaymentAttemptInPeriod = ($event->getObjectData('next_payment_attempt') === null) ? true : false;
        $chargeId = $event->getObjectData('charge');
        $createdAt = $event->getObjectData('date');

        if (!$stripeSubscriptionId) {
            //completely skipping non-subscription based charges
            return;
        }

        $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findByVendorSubscriptionId($stripeSubscriptionId);
        if (!$profileSubscriptionPlan) {
            $this->_handleNonExistingSubscription($event, $stripeSubscriptionId);
            return;
        }

        if ($this->_profileSubscriptionTransactionDao->findByChargeId($chargeId, TransactionEnum::FAILED) !== null) {
            /**
             * Stop further processing as we already have such a charge transaction in DB
             * Stripe Documentation mentions, duplicate events are possible
             */
            return;
        }

        $profileSubscription = $this->_profileSubscriptionDao->find($profileSubscriptionPlan->getProfileSubscriptionId());
        $subscription = $this->_subscriptionManager->getSubscription($profileSubscription->getSubscriptionId());
        $user = $this->_userRepository->find($profileSubscriptionPlan->getUserId());
        $paymentMethod = $this->_userPaymentMethodDao->find($profileSubscription->getUserPaymentMethodId());
        $newPaymentMethod = null;

        $profileSubscriptionTransaction = ProfileSubscriptionTransaction::withNew(
            $user->getId(),
            $profileSubscription->getUserProfileId(),
            $profileSubscription->getId(),
            $profileSubscriptionPlan->getId(),
            $paymentMethod->getId(),
            \Pley\Enum\TransactionEnum::FAILED,
            $amount,
            $user->getVPaymentSystemId(),
            $paymentMethod->getVPaymentMethodId(),
            $chargeId,
            $createdAt,
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

        $paymentMethodsCollection = $this->_userPaymentMethodDao->findByUser($user->getId());
        if (count($paymentMethodsCollection) > 1) {
            $newPaymentMethod = $this->_changeDefaultPaymentMethod($user);
        }

        if (!$isLastPaymentAttemptInPeriod) {
            $profileSubscription->setStatus(SubscriptionStatusEnum::PAST_DUE);
            $profileSubscriptionPlan->setStatus(SubscriptionStatusEnum::PAST_DUE);

            $mailTagCollection->setCustom('amountDue', number_format($amount, 2));
            $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::SUBSCRIPTION_PAYMENT_ATTEMPT_FAILED;
        } else {
            $profileSubscription->setStatus(SubscriptionStatusEnum::UNPAID);
            $profileSubscriptionPlan->setStatus(SubscriptionStatusEnum::UNPAID);

            //$this->_userSubscriptionManager->skipProfileSubscriptionShipments($profileSubscription, $subscription);
            $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::SUBSCRIPTION_PAYMENT_LEFT_UNPAID;
        }

        if($newPaymentMethod){
            $profileSubscription->setUserPaymentMethodId($newPaymentMethod->getId());
        }

        $this->_profileSubscriptionDao->save($profileSubscription);
        $this->_profileSubscriptionPlanDao->save($profileSubscriptionPlan);

        if(!$newPaymentMethod && !$isLastPaymentAttemptInPeriod){
            //send payment failed email only if there is no alternative payment methods anymore.
            $this->_mailer->send($mailTemplateId, $mailTagCollection, $mailUserTo);
        }

        $this->_logEvent($event);
        return;
    }

    /**
     * Handle <kbd>customer.subscription.deleted</kbd> Stripe event.
     * @param \Pley\Stripe\Event $event
     */
    public function handleSubscriptionCancel(StripeEvent $event)
    {
        $this->_validateType($event, StripeEvent::TYPE_SUBSCRIPTION_CANCEL);

        $stripeSubscriptionId = $event->getObjectData('id');

        $profileSubscriptionPlan = $this->_profileSubscriptionPlanDao->findByVendorSubscriptionId($stripeSubscriptionId);
        if (!$profileSubscriptionPlan) {
            $this->_handleNonExistingSubscription($event, $stripeSubscriptionId);
            return;
        }

        // Since some cancellations can be performed directly by customer service, the Subscription Plan
        // would already be cancelled and thus no need to try to override what's already cancelled
        if ($profileSubscriptionPlan->getStatus() == SubscriptionStatusEnum::CANCELLED) {
            return;
        }

        // Now we can proceed with flagging the subscription as cancelled and send the notification email
        $canceledAt = $event->getObjectData('canceled_at');
        $profileSubscriptionPlan->setEventCancel($canceledAt);

        $profileSubscription = $this->_profileSubscriptionDao->find($profileSubscriptionPlan->getProfileSubscriptionId());
        $profileSubscription->updateWithSubscriptionPlan($profileSubscriptionPlan);

        $this->_profileSubscriptionDao->save($profileSubscription);
        $this->_profileSubscriptionPlanDao->save($profileSubscriptionPlan);
        $this->_userSubscriptionManager->clearReserved($profileSubscription);
    }

    /**
     * Handles event with non-existing subscription. Happens when other systems events
     * are triggered.
     * @param \Pley\Stripe\Event $event
     * @param int $stripeSubscriptionId
     */
    protected function _handleNonExistingSubscription(StripeEvent $event, $stripeSubscriptionId)
    {
        $logEntry = $this->_webhookLogRepository->findByEventId($event->getMetaData('id'));
        if (!$logEntry) {
            $this->_logEvent($event, ResponseCode::HTTP_BAD_REQUEST);
            return;
        }
        /**
         * If such log entry has been found, hence we try to process an event
         * which does not belong to this system. So just respond 200 OK,
         * in order to silence further webhook notifications which are unnecessary.
         */
        return;
    }

    /**
     * Creates a log entry for a processed webhook event
     * @param Event $event
     * @param int $statusCode
     */
    protected function _logEvent(StripeEvent $event, $statusCode = ResponseCode::HTTP_OK)
    {
        $logEntry = new WebhookLog();
        $logEntry->setEventId($event->getMetaData('id'))
            ->setEventType($event->getMetaData('type'))
            ->setResponseStatusSent($statusCode);
        $this->_webhookLogRepository->save($logEntry);
        return;
    }

    /**
     * Closure method to change the default card and update all the subscriptions relationships as a transaction
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\Payment\UserPaymentMethod | null
     */
    protected function _changeDefaultPaymentMethod(User $user)
    {
        $paymentMethodsCollection = $this->_userPaymentMethodDao->findByUser($user->getId());
        $changeToMethod = null;

        foreach ($paymentMethodsCollection as $paymentMethod) {
            if ($this->_getFailedTransactionsNum($paymentMethod) > 0) {
                continue;
            } else {
                $changeToMethod = $paymentMethod;
                break;
            }
        }

        if ($changeToMethod === null) {
            return null;
        }

        // We need to update all the subscriptions that have a payment method associated to them to
        // this new Payment Method.
        $profileSubsList = $this->_profileSubscriptionDao->findByUser($user->getId());
        foreach ($profileSubsList as $profileSubscription) {
            // If it is a gift subscription, ignore it, no need to update
            if ($profileSubscription->getStatus() == \Pley\Enum\SubscriptionStatusEnum::GIFT) {
                continue;
            }

            // Now update the payment method and save
            $profileSubscription->setUserPaymentMethodId($changeToMethod->getId());
            $this->_profileSubscriptionDao->save($profileSubscription);
        }
        $this->_userBillingMgr->setDefaultCard($user, $changeToMethod);
        return $changeToMethod;
    }

    protected function _getFailedTransactionsNum(UserPaymentMethod $paymentMethod)
    {
        $failedTransactions = $this->_profileSubscriptionTransactionDao->findByPaymentMethodId($paymentMethod->getId(), TransactionEnum::FAILED);
        return count($failedTransactions);
    }

    /**
     * Helper method to check that an event is of the inidicated type.
     * @param \Pley\Stripe\Event $event
     * @param string $typeCheck A constant from <kbd>\Pley\Stripe\Event</kbd>
     * @throws \Exception If types don't match
     */
    protected function _validateType(StripeEvent $event, $typeCheck)
    {
        if ($event->getType() != $typeCheck) {
            throw new \Exception(
                "Invalid Type handling, expected `{$typeCheck}`, received `{$event->getType()}`"
            );
        }
    }
}
