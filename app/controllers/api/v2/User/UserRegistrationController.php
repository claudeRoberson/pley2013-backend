<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace api\v2\User;

/**
 * The <kbd>UserRegistrationController</kbd> takes care of adding new users.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package api.v2
 */
class UserRegistrationController extends \api\shared\User\SharedUserRegistrationController
{
    /** @var \Pley\Repository\User\UserWaitlistRepository */
    protected $_userWaitlistRepo;

    public function __construct(
            \Pley\Config\ConfigInterface $config,
            \Pley\Mail\AbstractMail $mail,
            \Pley\Db\AbstractDatabaseManager $dbManager,
            \Pley\Subscription\SubscriptionManager $subscriptionMgr,
            \Pley\User\UserBillingManager $userBillingManager,
            \Pley\User\UserSubscriptionManager $userSubscriptionMgr,
            \Pley\Shipping\Impl\EasyPost\ShipmentManager $shipmentMgr,
            \Pley\Dao\User\UserDao $userDao,
            \Pley\Dao\User\UserAddressDao $userAddressDao,
            \Pley\Dao\User\UserProfileDao $userProfileDao,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubscriptionDao,
            \Pley\Dao\Gift\GiftDao $giftDao,
            \Pley\Dao\Gift\GiftPriceDao $giftPriceDao,
            \Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
            \Pley\Dao\User\UserIncompleteRegistrationDao $userIncompleteRegDao,
            \Pley\Coupon\CouponManager $couponManager,
            \Pley\Referral\TokenManager $tokenManager,
            \Pley\Repository\User\UserWaitlistRepository $userWaitlistRepo,
            \Pley\Referral\RewardManager $rewardManager)
    {
        parent::__construct(
            $config, $mail, $dbManager, $subscriptionMgr, $userBillingManager, $userSubscriptionMgr, $shipmentMgr,
            $userDao, $userAddressDao, $userProfileDao, $profileSubscriptionDao,
            $giftDao, $giftPriceDao, $paymentPlanDao, $userIncompleteRegDao,
            $couponManager, $tokenManager, $rewardManager
        );

        $this->_userWaitlistRepo = $userWaitlistRepo;
    }

    // POST /user/register/account
    public function newAccount()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();

        $this->_validateUserData($json['user']);
        $userData = $json['user'];

        // Check that it is not an attempt to a duplicate account.
        $existingUser = $this->_userDao->findByEmail($userData['email']);
        if (!empty($existingUser)) {
            throw new \Pley\Exception\User\RegistrationExistingUserException($existingUser);
        }

        $passHash = \Hash::make($userData['password']);
        $newUser  = \Pley\Entity\User\User::withNew(
            $userData['firstName'], $userData['lastName'], $userData['email'], $passHash
        );

        if (isset($userData['fbUserId'])) { $newUser->setFbToken($userData['fbUserId']); }
        if (isset($userData['country'])) { $newUser->setCountry($userData['country']); }
        if (isset($userData['referrer'])) { $newUser->setReferrer($userData['referrer']); }

        $this->_userDao->save($newUser);

        \Event::fire(\Pley\Enum\EventEnum::USER_ACCOUNT_CREATE, [
            'user' => $newUser
        ]);

        $this->_handleNewIncompleteUser($json, $newUser);

        // Flagging the session as not fresh, so that it will be written by the application
        // stack (look at \Pley\Laravel\Foundation\Session\Middleware)
        // Only User Login and User Registration should do this.
        \Session::set(\Pley\Http\Session\Session::IS_FRESH_KEY, false);

        // Auto-logging user in the session
        \Auth::loginUsingId($newUser->getId());

        $arrayResponse = $this->_getCommonResponse($newUser);
        $jsonResponse  = \Response::json($arrayResponse);

        return \ResponseHelper::setSessionHeader($jsonResponse);
    }

    // POST /user/register/profile
    public function addProfile()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        // Check that session is valid, and get the user entity if so
        $user = $this->_checkAuthenticated();

        $json = \Input::json()->all();

        $this->_validateProfileData($json['profile']);
        $profileData = $json['profile'];

        // Since registration is broken into parts, it could be that a Subscription was purchased
        // before a profile was created, as such, a dummy user would've been created to compansate
        // for the dependancy needed on subscriptions, if that is the case, we need to update the
        // dummy, otherwise, this is a call before purchase and as such a new profile can be created
        $profileList = $this->_userProfileDao->findByUser($user->getId());
        if (!empty($profileList)) {
            $userProfile = $profileList[0];
            if (!$userProfile->isDummy()) {
                throw \Exception(
                    'This should not happen unless some other code is adding complete users before registration'
                );
            }

            $userProfile->setGender($profileData['gender']);
            $userProfile->setTypeShirtSizeId($profileData['shirtSize']);
            $userProfile->setFirstName($profileData['firstName']);

            if (isset($profileData['lastName'])){ $userProfile->setLastName($profileData['lastName']); }
            if (isset($profileData['birthDate'])){ $userProfile->setBirthDate($profileData['birthDate']); }

        } else {
            $userProfile = \Pley\Entity\User\UserProfile::withNew(
                $user->getId(),
                $profileData['gender'],
                $profileData['shirtSize'],
                $profileData['firstName'],
                isset($profileData['lastName'])? $profileData['lastName'] : null,
                isset($profileData['birthDate'])? $profileData['birthDate'] : null
            );
        }

        //lets save this userProfile
        $this->_userProfileDao->save($userProfile);

        $this->_handleUpdateIncompleteUser($user);

        $arrayResponse = $this->_getCommonResponse($user);
        return \Response::json($arrayResponse);
    }

    // POST /user/register/address
    public function addAddress()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        // Check that session is valid, and get the user entity if so
        $user = $this->_checkAuthenticated();

        $json = \Input::json()->all();

        $verifiedAddress = $this->_validateAddressData($json['address']);

        // Adding the UserId and Zone to the Verified Address and then saving it
        $verifiedAddress->setUserId($user->getId());
        $this->_shipmentMgr->assignShippingZones($verifiedAddress);

        $this->_userAddressDao->save($verifiedAddress);

        // Since steps can be separated and have billing happen before address is supplied, so, 
        // subscriptions will start but won't have an address, so we need to add the address once
        // supplied
        $profileSubsList = $this->_profileSubsDao->findByUser($user->getId());
        foreach ($profileSubsList as $profileSubs) {
            if (empty($profileSubs->getUserAddressId())) {
                $profileSubs->setUserAddressId($verifiedAddress->getId());
                $this->_profileSubsDao->save($profileSubs);
            }
        }

        $arrayResponse = $this->_getCommonResponse($user);
        return \Response::json($arrayResponse);
    }

    // POST /user/register/waitlist-billing
    public function waitlistBilling()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        // Check that session is valid, and get the user entity if so
        $user = $this->_checkAuthenticated();

        $json = \Input::json()->all();

        $creditCard = $this->_validateBillingData($user, $json);

        $subscriptionId = $json['subscriptionId'];
        $paymentPlanId  = $json['paymentPlanId'];

        // This should not happen unless somebody misconfigured the DB or is someone is trying to
        // hack the API call with incorrect data, that is why we throw a base exception instead of
        // a specialized exception.
        if (!$this->_userSubscriptionMgr->isCompatibleSubscription($subscriptionId, $paymentPlanId)) {
            throw new \Exception('Incompatible Payment Plan for Subscription');
        }
        //load and validate coupon if code has been provided
        $coupon = null;
        $couponCode = (isset($json['couponCode'])) ? $json['couponCode'] : null;
        if ($couponCode) {
            $coupon = $this->_couponManager->validateCouponCode($couponCode, $user, $subscriptionId, $paymentPlanId);
        }

        // If this registration is a result of a referral, then we need to check for the invite coupon
        // to try and give the bigger discount upon creating the subscription
        // This first charge is the reason why it cannot be delegated till later as an event
        $referralToken = (isset($json['referralToken'])) ? $json['referralToken'] : null;
        if ($referralToken) {
            $token = $this->_tokenManager->findByToken($referralToken);
            if($token && $token->isActive()){
                $acquisitionCoupon = $this->_couponManager->getAcquisitionCoupon($token);
                // If A coupon was not supplied, then we just directly use the Invite Coupon
                if (!isset($coupon)) {
                    $coupon = $acquisitionCoupon;
                }
            }
        }

        // Frontend sends the Receive Newsletter flag as part of the Billing registration, so we
        // need to update it to the User's selection.
        $user->setIsReceiveNewsletter($json['isReceiveNewsletter']);

        // To ensure that no ghost Vendor accounts are created upon failed attempts to create the 
        // first subscription on a rejected charge and reverting the whole transaction, we make the
        // account creation separate so that the user is always related to the Vendor account.
        if (empty($user->getVPaymentAccountId())) {
            $this->_userBillingMgr->addUserAccount($user);
        }

        $that = $this;
        $this->_dbManager->transaction(
            function() use ($that, $user, $creditCard, $paymentPlanId, $subscriptionId, $coupon, $referralToken) {
                $that->_waitlistBillingClosure(
                    $user, $creditCard, $paymentPlanId, $subscriptionId, $coupon, $referralToken
                );
                $this->_registrationCompleted($user);
            }
        );

        return \Response::json([
            'success'              => true,
            'userId'               => $user->getId(),
            'isInventoryAvailable' => $this->_isInventoryAvailable($subscriptionId),
        ]);
    }

    // POST /user/register/waitlist-gift
    public function waitlistGift()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $user = $this->_checkAuthenticated();

        $json = \Input::json()->all();
        \ValidationHelper::validate($json, ['token' => 'required|string']);

        $gift = $this->_giftDao->findByToken(strtolower($json['token']));
        \ValidationHelper::entityExist($gift, \Pley\Entity\Gift\Gift::class);

        /**
         * Additional validation, because gift becomes redeemed only after we release it, so checking
         * if waitlist entries with this gift id already exists
         */

        $existingWaitlistEntries = $this->_userWaitlistRepo->findWaitlistByGift($gift->getId());

        if ($gift->isRedeemed() || !empty($existingWaitlistEntries)) {
            throw new \Pley\Exception\Gift\GiftRedeemedException($user, $gift);
        }

        $that = $this;
        $this->_dbManager->transaction(function() use ($that, $user, $gift) {
            $that->_waitlistGiftClosure($user, $gift);
            $this->_registrationCompleted($user);
        });

        return \Response::json([
            'success'              => true,
            'userId'               => $user->getId(),
            'isInventoryAvailable' => $this->_isInventoryAvailable($gift->getSubscriptionId()),
        ]);
    }

    // Though we are intending to do everything with the Waitlist feature, leaving `subscriptionWithBilling`
    // here for backwards compatibility that allows users to subscribe immediately and start from
    // the nearest available inventory for the selected subscription.

    // POST /user/register/billing
    public function subscriptionWithBilling()
    {
        $user = $this->_checkAuthenticated();

        parent::_subscriptionWithBillingBase();

        return \Response::json([
            'success' => true,
            'userId'  => $user->getId(),
        ]);
    }

    // Fallback method to provide direct gift purchase without creating a waitlist entry
    // POST /user/register/gift
    public function subscriptionWithGiftToken()
    {
        parent::_subscriptionWithGiftTokenBase();
        return \Response::json(['success' => true]);
    }

    // ---------------------------------------------------------------------------------------------
    // Helper methods for Creating a new Account ---------------------------------------------------

    private function _getCommonResponse(\Pley\Entity\User\User $user)
    {
        $arrayResponse = [
            'userId'        => $user->getId(),
            'userProfileId' => null,
            'userAddressId' => null,
        ];

        $profileList = $this->_userProfileDao->findByUser($user->getId());
        $addressList = $this->_userAddressDao->findByUser($user->getId());

        if (!empty($profileList)) {
            // Even if we have profiles, it may actually be that we are retrieving the Dummy profile
            // that was created for upon purchase but before profile registration.
            // As such, Only if it is NOT the Dummy profile, then we can use the ID
            /* @var $profile \Pley\Entity\User\UserProfile */
            $profile = $profileList[0];
            if (!empty($profile->getFirstName())) {
                $arrayResponse['userProfileId'] = $profileList[0]->getId();
            }
        }
        if (!empty($addressList)) {
            $arrayResponse['userAddressId'] = $addressList[0]->getId();
        }

        return $arrayResponse;
    }

    private function _handleNewIncompleteUser(array $requestInput, \Pley\Entity\User\User $user)
    {
        $userIncompleteReg = $this->_userIncompleteRegDao->findByUser($user->getId());
        // If there is no object, it is because the user probably already completed the paid part
        // of registration, so, though it may potentially be incomplete if it is missing Profile
        // or Address, in this terms, it is complete as the user has converted.
        if (empty($userIncompleteReg)) {
            $userIncompleteReg = new \Pley\Entity\User\UserIncompleteRegistration();
            $userIncompleteReg->setUserId($user->getId());
        }

        $userMeta = isset($requestInput['metadata'])? $requestInput['metadata'] : null;
        if (isset($userMeta)) {
            $userIncompleteReg->setPaymentPlanId($userMeta['paymentPlanId'])
                    ->setSubscriptionId($userMeta['subscriptionId']);
        }

        $this->_userIncompleteRegDao->save($userIncompleteReg);
    }

    private function _handleUpdateIncompleteUser(\Pley\Entity\User\User $user)
    {
        $userIncompleteReg = $this->_userIncompleteRegDao->findByUser($user->getId());
        // If there is no object, it is because the user probably already completed the paid part
        // of registration, so, though it may potentially be incomplete if it is missing Profile
        // or Address, in this terms, it is complete as the user has converted.
        if (empty($userIncompleteReg)) {
            return;
        }

        $profileList = $this->_userProfileDao->findByUser($user->getId());
        if (!empty($profileList)) {
            $userProfile = $profileList[0];
            $userIncompleteReg->setProfileGender($userProfile->getGender())
                    ->setProfileName($userProfile->getFirstName())
                    ->setProfileShirtSize($userProfile->getTypeShirtSizeId());
        }

        $this->_userIncompleteRegDao->save($userIncompleteReg);
    }

    // ---------------------------------------------------------------------------------------------
    // Helper methods for Finishing registration ---------------------------------------------------

    private function _waitlistBillingClosure(\Pley\Entity\User\User $user,
            \Pley\Payment\Method\CreditCard $creditCard, $paymentPlanId, $subscriptionId,
            \Pley\Entity\Coupon\Coupon $coupon = null, $referralToken = null)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        // Creating the Payment Account and Method so we can then create the first subscription

        $paymentMethod = $this->_userBillingMgr->addCard($user, $creditCard);

        // Since this is registration, this is also the first card added to the vendor payment system
        // and as such, the Default automatically, so no need to make a new call to the vendor to
        // set this Card as the Default one, just update the DB relationship.
        $user->setDefaultPaymentMethodId($paymentMethod->getId());
        $this->_userDao->save($user);

        $userProfile = $this->_getUserProfile($user);
        $userAddress = $this->_getUserAddress($user);

        $userWaitlist = new \Pley\Entity\User\UserWaitlist();
        $userWaitlist->setUserId($user->getId())
                ->setPaymentPlanId($paymentPlanId)
                ->setSubscriptionId($subscriptionId)
                ->setUserProfileId($userProfile->getId())
                ->setUserAddressId($userAddress->getId())
                ->setStatus(\Pley\Enum\WaitlistStatusEnum::ACTIVE);
        if (isset($coupon)) {
            $userWaitlist->setCouponId($coupon->getId());
        }
        if (isset($referralToken)) {
            $userWaitlist->setReferralToken($referralToken);
        }
        $this->_userWaitlistRepo->saveWaitlist($userWaitlist);

        $this->_triggerWaitlistCreateEvent($userWaitlist, $user);
    }

    private function _waitlistGiftClosure(\Pley\Entity\User\User $user, \Pley\Entity\Gift\Gift $gift)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        $userProfile = $this->_getUserProfile($user);
        $userAddress = $this->_getUserAddress($user);

        $userWaitlist = new \Pley\Entity\User\UserWaitlist();
        $userWaitlist->setUserId($user->getId())
                ->setGiftId($gift->getId())
                ->setSubscriptionId($gift->getSubscriptionId())
                ->setUserProfileId($userProfile->getId())
                ->setUserAddressId($userAddress->getId())
                ->setStatus(\Pley\Enum\WaitlistStatusEnum::ACTIVE);
        $this->_userWaitlistRepo->saveWaitlist($userWaitlist);

        $this->_giftDao->save($gift);
        $this->_sendGiftRedeemedEmail($gift);

        $this->_triggerWaitlistCreateEvent($userWaitlist, $user);
    }

    /**
     * Retruns the UserProfile object to use for the supplied user waitlist object
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\User\UserProfile
     */
    private function _getUserProfile(\Pley\Entity\User\User $user)
    {
            $userProfileList = $this->_userProfileDao->findByUser($user->getId());
            if (empty($userProfileList)) {
                $userProfile = \Pley\Entity\User\UserProfile::withDummy($user->getId());
                $this->_userProfileDao->save($userProfile);
            } else {
                $userProfile = $userProfileList[0];
            }
        return $userProfile;
    }

    /**
     * Retruns the UserAddress object to use for the supplied user waitlist object
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\User\UserAddress|null
     */
    private function _getUserAddress(\Pley\Entity\User\User $user)
    {
        $userAddressList = $this->_userAddressDao->findByUser($user->getId());
        $userAddress = empty($userAddressList) ? null : $userAddressList[0];
        return $userAddress;
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
