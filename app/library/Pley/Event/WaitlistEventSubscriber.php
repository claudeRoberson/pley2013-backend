<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Event;

use \Pley\Config\ConfigInterface as Config;
use Pley\Enum\WaitlistStatusEnum;

/**
 * Event handler for Profile events.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class WaitlistEventSubscriber extends AbstractEventSubscriber
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;
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
    /** @var \Pley\Repository\User\UserWaitlistRepository */
    protected $_userWaitlistRepo;
    /** @var \Pley\Repository\User\UserRepository */
    protected $_userRepo;


    // Because the framework's MailServiceProvider is `deferred` we cannot add it as part of the
    // dependencies of this subscriber which is loaded as part of our Non-Deferred EventServiceProvider
    public function __construct(Config $config,
                                \Pley\Subscription\SubscriptionManager $subscriptionManager,
                                \Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
                                \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao,
                                \Pley\Dao\Gift\GiftPriceDao $giftPriceDao,
                                \Pley\Price\PriceManager $priceManager,
                                \Pley\Repository\User\UserWaitlistRepository $userWaitlistRepo,
                                \Pley\Repository\User\UserRepository $userRepo)
    {
        $this->_config = $config;

        $this->_subscriptionManager = $subscriptionManager;
        $this->_paymentPlanDao = $paymentPlanDao;
        $this->_vendorPaymentPlanDao = $vendorPaymentPlanDao;
        $this->_giftPriceDao = $giftPriceDao;
        $this->_priceManager = $priceManager;

        $this->_userWaitlistRepo = $userWaitlistRepo;
        $this->_userRepo = $userRepo;
    }

    /**
     * Sends the email for the new waitlist entry created
     * @param \Pley\Entity\User\User $user
     */
    public function handleWaitlistCreatedEmail(\Pley\Entity\User\UserWaitlist $userWaitlist, \Pley\Entity\User\User $user)
    {
        $this->_initDeferredDependencies();

        $subscription = $this->_subscriptionManager->getSubscription($userWaitlist->getSubscriptionId());

        $mailOptions = ['subjectName' => $subscription->getName()];
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($user);
        $mailTagCollection->addEntity($subscription);

        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::WAITLIST_CREATED;

        $mailUserTo = \Pley\Mail\MailUser::withUser($user);

        try {
            $this->_mail->send($mailTemplateId, $mailTagCollection, $mailUserTo, $mailOptions);
        } catch (\Exception $ex) {
            \Log::error((string)$ex);
        }
    }

    /**
     * Handles the unsuccessful waitlist release attempt operation
     * @param \Pley\Entity\User\User $user
     */
    public function handleWaitlistPaymentFailed(\Pley\Entity\User\UserWaitlist $userWaitlist)
    {
        $this->_initDeferredDependencies();
        $subscription = $this->_subscriptionManager->getSubscription($userWaitlist->getSubscriptionId());
        $user = $this->_userRepo->find($userWaitlist->getUserId());

        $mailOptions = ['subjectName' => $subscription->getName()];
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($user);
        $mailTagCollection->addEntity($subscription);

        $userWaitlist->setNotificationCount($userWaitlist->getNotificationCount() + 1);

        $this->_userWaitlistRepo->saveWaitlist($userWaitlist);
        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::WAITLIST_PAYMENT_FAILED;
        $mailUserTo = \Pley\Mail\MailUser::withUser($user);

        try {
            $this->_mail->send($mailTemplateId, $mailTagCollection, $mailUserTo, $mailOptions);
        } catch (\Exception $ex) {
            \Log::error((string)$ex);
        }
    }

    /** {@inheritDoc} */
    protected function _getEventToMethodData()
    {
        return [
            [\Pley\Enum\EventEnum::WAITLIST_CREATE, 'handleWaitlistCreatedEmail'],
            [\Pley\Enum\EventEnum::WAITLIST_PAYMENT_FAILED, 'handleWaitlistPaymentFailed'],
        ];
    }

    protected function _initDeferredDependencies()
    {
        $this->_mail = \App::make('\Pley\Mail\AbstractMail');
    }
}