<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Event;

use \Pley\Config\ConfigInterface as Config;
use Pley\Referral\RewardManager;

/**
 * Event handler for Profile events.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class ProfileEventSubscriber extends AbstractEventSubscriber
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;
    /** @var  @var $_acquisitionManager AcquisitionManager */
    protected $_acquisitionManager;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionManager;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_paymentPlanDao;
    /** @var \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao */
    protected $_vendorPaymentPlanDao;
    /** @var \Pley\Dao\Gift\GiftPriceDao */
    protected $_giftPriceDao;
    /** @var \Pley\Price\PriceManager */
    protected $_priceManager;
    /** @var \Pley\Referral\RewardManager */
    protected $_rewardManager;
    /** @var \Pley\Repository\Coupon\CouponRepository */
    protected $_couponRepository;


    // Because the framework's MailServiceProvider is `deferred` we cannot add it as part of the
    // dependencies of this subscriber which is loaded as part of our Non-Deferred EventServiceProvider
    public function __construct(Config $config,
                                \Pley\Referral\AcquisitionManager $acquisitionManager,
                                \Pley\Subscription\SubscriptionManager $subscriptionManager,
                                \Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
                                \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao,
                                \Pley\Dao\Gift\GiftPriceDao $giftPriceDao,
                                \Pley\Price\PriceManager $priceManager,
                                \Pley\Referral\RewardManager $rewardManager,
                                \Pley\Repository\Coupon\CouponRepository $couponRepository)
    {
        $this->_config = $config;

        $this->_acquisitionManager = $acquisitionManager;
        $this->_subscriptionManager = $subscriptionManager;
        $this->_paymentPlanDao = $paymentPlanDao;
        $this->_vendorPaymentPlanDao = $vendorPaymentPlanDao;
        $this->_giftPriceDao = $giftPriceDao;
        $this->_priceManager = $priceManager;
        $this->_rewardManager = $rewardManager;
        $this->_couponRepository = $couponRepository;
    }

    /**
     * Registers an acquisition in case if referral_token was supplied at the
     * profile subscription purchase
     * @param \Pley\Entity\User\User $user
     * @param \Pley\User\NewSubscriptionResult $newSubsResult
     * @param string $referralToken
     */
    public function handleNewReferralAcquisition(
        \Pley\Entity\User\User $user,
        \Pley\User\NewSubscriptionResult $newSubsResult,
        $referralToken = null)
    {
        $this->_initDeferredDependencies();

        if (isset($referralToken)) {
            $this->_acquisitionManager->registerAcquisition($user, $referralToken);
        }
    }

    /**
     * Logs a referral first subscription and makes all pending acquisitions
     * reward status as REWARDED
     * @param \Pley\Entity\User\User $user
     * @param \Pley\User\NewSubscriptionResult $newSubsResult
     * @param string $referralToken
     */
    public function handleNewReferralSubscription(
        \Pley\Entity\User\User $user,
        \Pley\User\NewSubscriptionResult $newSubsResult,
        $referralToken = null)
    {

        $this->_initDeferredDependencies();
        $this->_rewardManager->setUserRewardStatus($user, \Pley\Enum\Referral\RewardEnum::REWARD_STATUS_REWARDED);
    }

    /**
     * Sends the welcome email for the new subscription created
     * @param \Pley\Entity\User\User $user
     * @param \Pley\User\NewSubscriptionResult $newSubsResult
     * @param string $referralToken
     */
    public function handleWelcomeEmail(\Pley\Entity\User\User $user, \Pley\User\NewSubscriptionResult $newSubsResult)
    {
        $this->_initDeferredDependencies();
        $subscription = $this->_subscriptionManager->getSubscription($newSubsResult->profileSubscription->getSubscriptionId());
        $firstSequenceItem = $newSubsResult->itemSequence[0];

        if (isset($newSubsResult->profileSubsPlan)) {
            $isGift = false;
            $paymentPlan = $this->_paymentPlanDao->find($newSubsResult->profileSubsPlan->getPaymentPlanId());

        } else {
            $isGift = true;
            $giftPrice = $this->_giftPriceDao->find($newSubsResult->gift->getGiftPriceId());
            $paymentPlan = $this->_paymentPlanDao->find($giftPrice->getEquivalentPaymentPlanId());
        }

        //TODO: get shipping zone ID based on user country here
        $vendorPaymentPlan = $this->_vendorPaymentPlanDao->findByPaymentPlan(
            $paymentPlan->getId(), \Pley\Enum\Shipping\ShippingZoneEnum::DEFAULT_ZONE_ID, \Pley\Enum\PaymentSystemEnum::STRIPE);

        $userCurrencyTotal = $this->_priceManager->toCountryCurrency($vendorPaymentPlan->getTotal(), $user->getCountry());
        $userCurrencyCode = $this->_priceManager->getCountryCurrencyCode($user->getCountry());
        $userCurrencySign = $this->_priceManager->getCountryCurrencySign($user->getCountry());

        $formattedTotal = sprintf('%s%.2f %s', $userCurrencySign, $userCurrencyTotal, $userCurrencyCode);

        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($user);
        $mailTagCollection->addEntity($subscription);
        $mailTagCollection->addEntity($paymentPlan);
        $mailTagCollection->setCustom('newSubsResult', $newSubsResult);
        $mailTagCollection->setCustom('sequenceItem', $firstSequenceItem);
        $mailTagCollection->setCustom('isGift', $isGift);
        $mailTagCollection->setCustom('formattedTotal', $formattedTotal);

        $mailTagCollection->setCustom('couponDescription', false);

        if (isset($newSubsResult->profileSubsTransac) && $newSubsResult->profileSubsTransac->getDiscountSourceId()) {
            $coupon = $this->_couponRepository->find($newSubsResult->profileSubsTransac->getDiscountSourceId());
            $mailTagCollection->setCustom('couponDescription', $coupon->getDescription());
        }

        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::WELCOME;

        $mailUserTo = \Pley\Mail\MailUser::withUser($user);

        // Sending a welcome email is important, but if the third party mail provider fails for some
        // reason, until we can add some sort of queue to retry, we don't want to just crash as
        // it would leave the frontend believing the Request failed, when it actually completed but
        // just the email didn't go out, so for now, we just siliently log the exception and continue
        // to return success.
        try {
            $this->_mail->send($mailTemplateId, $mailTagCollection, $mailUserTo);
        } catch (Exception $ex) {
            \Log::error((string)$ex);
        }
    }

    /**
     * Sends the notice email when subscription is reactivated
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Subscription\Subscription $subscription
     */
    public function handleReactivateEmail(
        \Pley\Entity\User\User $user,
        \Pley\Entity\Subscription\Subscription $subscription)
    {
        $this->_initDeferredDependencies();

        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($user);
        $mailTagCollection->addEntity($subscription);

        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::SUBSCRIPTION_REACTIVATE;

        $mailUserTo = \Pley\Mail\MailUser::withUser($user);

        // Sending a welcome email is important, but if the third party mail provider fails for some
        // reason, until we can add some sort of queue to retry, we don't want to just crash as
        // it would leave the frontend believing the Request failed, when it actually completed but
        // just the email didn't go out, so for now, we just siliently log the exception and continue
        // to return success.
        try {
            $this->_mail->send($mailTemplateId, $mailTagCollection, $mailUserTo);
        } catch (Exception $ex) {
            \Log::error((string)$ex);
        }
    }

    /** {@inheritDoc} */
    protected function _getEventToMethodData()
    {
        return [
            [\Pley\Enum\EventEnum::SUBSCRIPTION_CREATE, 'handleNewReferralAcquisition'],
            [\Pley\Enum\EventEnum::SUBSCRIPTION_CREATE, 'handleNewReferralSubscription'],
            [\Pley\Enum\EventEnum::SUBSCRIPTION_CREATE, 'handleWelcomeEmail'],
            [\Pley\Enum\EventEnum::SUBSCRIPTION_REACTIVATE, 'handleReactivateEmail'],
        ];
    }

    protected function _initDeferredDependencies()
    {
        $this->_mail = \App::make('\Pley\Mail\AbstractMail');
    }
}