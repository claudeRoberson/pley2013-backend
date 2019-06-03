<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace api\v2\User\Profile;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Mail\AbstractMail as Mail;

/**
 * The <kbd>ProfileSubscriptionController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class ProfileSubscriptionController extends \api\shared\AbstractBaseAuthController
{
    /** @var \Pley\Config\ConfigInterface */
    private $_config;
    /** @var \Pley\Mail\AbstractMail */
    private $_mail;
    /** @var \Pley\Subscription\SubscriptionManager */
    private $_subscriptionMgr;
    /** @var \Pley\User\UserSubscriptionManager */
    private $_userSubscriptionMgr;
    /** @var \Pley\Dao\User\UserProfileDao */
    private $_userProfileDao;
    /** @var \Pley\Dao\User\UserAddressDao */
    private $_userAddressDao;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    private $_paymentPlanDao;
    /** @var \Pley\Dao\Gift\GiftDao */
    private $_giftDao;
    /** @var \Pley\Dao\Gift\GiftPriceDao */
    private $_giftPriceDao;
    /** @var \Pley\Coupon\CouponManager */
    private $_couponManager;
    /** @var \Pley\Repository\User\UserWaitlistRepository */
    protected $_userWaitlistRepo;

    public function __construct(Config $config, Mail $mail,
                                \Pley\Subscription\SubscriptionManager $subscriptionMgr,
                                \Pley\User\UserSubscriptionManager $userSubscriptionMgr,
                                \Pley\Dao\User\UserProfileDao $userProfileDao,
                                \Pley\Dao\User\UserAddressDao $userAddressDao,
                                \Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
                                \Pley\Dao\Gift\GiftDao $giftDao,
                                \Pley\Dao\Gift\GiftPriceDao $giftPriceDao,
                                \Pley\Coupon\CouponManager $couponManager,
                                \Pley\Repository\User\UserWaitlistRepository $userWaitlistRepo)
    {
        parent::__construct();

        $this->_config = $config;
        $this->_mail = $mail;
        $this->_subscriptionMgr = $subscriptionMgr;
        $this->_userSubscriptionMgr = $userSubscriptionMgr;

        $this->_userProfileDao = $userProfileDao;
        $this->_userAddressDao = $userAddressDao;
        $this->_paymentPlanDao = $paymentPlanDao;
        $this->_giftDao = $giftDao;
        $this->_giftPriceDao = $giftPriceDao;

        $this->_couponManager = $couponManager;

        $this->_userWaitlistRepo = $userWaitlistRepo;
    }

    // POST /user/profile/waitlist-billing
    public function addWaitlistBilling()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();

        $validationRules = [
            'subscriptionId' => 'required|integer',
            'paymentPlanId' => 'required|integer',
            'profileId' => 'required|integer',
            'addressId' => 'required|integer',
        ];
        \ValidationHelper::validate($json, $validationRules);

        $subscriptionId = $json['subscriptionId'];
        $paymentPlanId = $json['paymentPlanId'];

        // This should not happen unless somebody misconfigured the DB or is someone is trying to
        // hack the API call with incorrect data, that is why we throw a base exception instead of
        // a specialized exception.
        if (!$this->_userSubscriptionMgr->isCompatibleSubscription($subscriptionId, $paymentPlanId)) {
            throw new \Exception('Incompatible Payment Plan for Subscription');
        }

        $userProfile = $this->_userProfileDao->find($json['profileId']);
        $userAddress = $this->_userAddressDao->find($json['addressId']);
        \ValidationHelper::entityExist($userProfile, \Pley\Entity\User\UserProfile::class);
        \ValidationHelper::entityExist($userAddress, \Pley\Entity\User\UserAddress::class);

        // This is just a validation to make sure the data supplied is intrinsically related.
        if ($userProfile->getUserId() != $this->_user->getId() || $userAddress->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }
        //load and validate coupon if code has been provided
        $coupon = null;
        $couponCode = (isset($json['couponCode'])) ? $json['couponCode'] : null;
        if ($couponCode) {
            $coupon = $this->_couponManager->validateCouponCode(
                $couponCode, $this->_user, $subscriptionId, $paymentPlanId
            );
        }

        $userWaitlist = new \Pley\Entity\User\UserWaitlist();
        $userWaitlist->setUserId($this->_user->getId())
            ->setUserProfileId($userProfile->getId())
            ->setUserAddressId($userAddress->getId())
            ->setPaymentPlanId($paymentPlanId)
            ->setSubscriptionId($subscriptionId)
            ->setStatus(\Pley\Enum\WaitlistStatusEnum::ACTIVE);


        if (isset($coupon)) {
            $userWaitlist->setCouponId($coupon->getId());
        }

        $this->_userWaitlistRepo->saveWaitlist($userWaitlist);
        $this->_triggerWaitlistCreateEvent($userWaitlist, $this->_user);

        return \Response::json([
            'success' => true,
            'userId' => $this->_user->getId(),
            'isInventoryAvailable' => $this->_isInventoryAvailable($subscriptionId),
        ]);
    }

    // POST /user/profile/waitlist-gift
    public function addWaitlistGift()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();

        \ValidationHelper::validate($json, [
            'token' => 'required|string',
            'profileId' => 'required|integer',
            'addressId' => 'required|integer',
        ]);

        $userProfile = $this->_userProfileDao->find($json['profileId']);
        $userAddress = $this->_userAddressDao->find($json['addressId']);
        $gift = $this->_giftDao->findByToken(strtolower($json['token']));
        \ValidationHelper::entityExist($userProfile, \Pley\Entity\User\UserProfile::class);
        \ValidationHelper::entityExist($userAddress, \Pley\Entity\User\UserAddress::class);
        \ValidationHelper::entityExist($gift, \Pley\Entity\Gift\Gift::class);

        // This is just a validation to make sure the data supplied is intrinsically related.
        if ($userProfile->getUserId() != $this->_user->getId() || $userAddress->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }

        /**
         * Additional validation, because gift becomes redeemed only after we release it, so checking
         * if waitlist entries with this gift id already exists
         */

        $existingWaitlistEntries = $this->_userWaitlistRepo->findWaitlistByGift($gift->getId());

        if ($gift->isRedeemed() || !empty($existingWaitlistEntries)) {
            throw new \Pley\Exception\Gift\GiftRedeemedException($this->_user, $gift);
        }

        $userWaitlist = new \Pley\Entity\User\UserWaitlist();
        $userWaitlist->setUserId($this->_user->getId())
            ->setUserProfileId($userProfile->getId())
            ->setUserAddressId($userAddress->getId())
            ->setGiftId($gift->getId())
            ->setSubscriptionId($gift->getSubscriptionId())
            ->setStatus(\Pley\Enum\WaitlistStatusEnum::ACTIVE);

        $this->_userWaitlistRepo->saveWaitlist($userWaitlist);

        $this->_triggerWaitlistCreateEvent($userWaitlist, $this->_user);

        $this->_giftDao->save($gift);

        $this->_sendGiftRedeemedEmail($gift);

        return \Response::json(['success' => true]);
    }

    // DELETE /user/profile/waitlist/{id}
    public function cancelWaitlist($id)
    {
        \RequestHelper::checkDeleteRequest();
        $userWaitlist = $this->_userWaitlistRepo->findWaitlist($id);
        \ValidationHelper::entityExist($userWaitlist, \Pley\Entity\User\UserWaitlist::class);

        // This is just a validation to make sure the data supplied is intrinsically related.
        if ($userWaitlist->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }
        $this->_userWaitlistRepo->cancelWaitlist($userWaitlist);
        return \Response::json(['success' => true]);
    }

    /**
     * Returns if there is available inventory for the supplied subscription
     * @param int $subscriptionId
     * @return boolean
     */
    protected function _isInventoryAvailable($subscriptionId)
    {
        $subscription = $this->_subscriptionMgr->getSubscription($subscriptionId);
        $itemSequence = $this->_subscriptionMgr->getFullItemSequence($subscription);

        // We first assume the subscription is IN-ORDER
        $sequenceItem = $itemSequence[0];
        // But we need to check the pull type to retrieve the correct sequence item if not the default
        if ($subscription->getItemPullType() == \Pley\Enum\SubscriptionItemPullEnum::BY_SCHEDULE) {
            $schedIdx = $this->_subscriptionMgr->getActivePeriodIndex($subscription);
            $sequenceItem = $itemSequence[$schedIdx];
        }

        return $sequenceItem->hasAvailableSubscriptionUnits();
    }

    /**
     * Sends the gift redemption email to the gift sender after the subscription has been added.
     * @param \Pley\Entity\Gift\Gift $gift
     */
    private function _sendGiftRedeemedEmail(\Pley\Entity\Gift\Gift $gift)
    {
        $giftPrice = $this->_giftPriceDao->find($gift->getGiftPriceId());
        $subscription = $this->_subscriptionMgr->getSubscription($gift->getSubscriptionId());
        $paymentPlan = $this->_paymentPlanDao->find($giftPrice->getEquivalentPaymentPlanId());

        $mailOptions = ['subjectName' => ucfirst($gift->getToFirstName()) . ' ' . ucfirst($gift->getToLastName())];
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($gift);
        $mailTagCollection->addEntity($giftPrice);
        $mailTagCollection->addEntity($subscription);
        $mailTagCollection->addEntity($paymentPlan);
        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::GIFT_REDEEMED;

        $displayName = $gift->getFromFirstName() . ' ' . $gift->getFromLastName();
        $mailUserTo = new \Pley\Mail\MailUser($gift->getFromEmail(), $displayName);

        $this->_mail->send($mailTemplateId, $mailTagCollection, $mailUserTo, $mailOptions);
    }

    /**
     * Helper method to trigger events related to waitlist creation
     * @param \Pley\Entity\User\UserWaitlist $userWaitlist
     * @param \Pley\Entity\User\User $user
     */
    private function _triggerWaitlistCreateEvent(\Pley\Entity\User\UserWaitlist $userWaitlist, \Pley\Entity\User\User $user)
    {
        \Event::fire(\Pley\Enum\EventEnum::WAITLIST_CREATE, [
            'userWaitlist' => $userWaitlist,
            'user' => $user
        ]);
    }
}
