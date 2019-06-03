<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace api\shared\User;

/**
 * The <kbd>SharedUserRegistrationController</kbd> class provides common functionality between the 
 * different UserRegistration controller versions.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 */
abstract class SharedUserRegistrationController extends \api\shared\AbstractBaseController
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionMgr;
    /** @var \Pley\User\UserBillingManager */
    protected $_userBillingMgr;
    /** @var \Pley\User\UserSubscriptionManager */
    protected $_userSubscriptionMgr;
    /** @var \Pley\Shipping\AbstractShipmentManager */
    protected $_shipmentMgr;
    /** @var \Pley\Dao\User\UserDao */
    protected $_userDao;
    /** @var \Pley\Dao\User\UserAddressDao */
    protected $_userAddressDao;
    /** @var \Pley\Dao\User\UserProfileDao */
    protected $_userProfileDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    /** @var \Pley\Dao\Gift\GiftDao */
    protected $_giftDao;
    /** @var \Pley\Dao\Gift\GiftPriceDao */
    protected $_giftPriceDao;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_paymentPlanDao;
    /** @var \Pley\Dao\User\UserIncompleteRegistrationDao */
    protected $_userIncompleteRegDao;
    /** @var \Pley\Coupon\CouponManager */
    protected $_couponManager;
    /** @var \Pley\Referral\TokenManager */
    protected $_tokenManager;
    /** @var \Pley\Referral\RewardManager */
    protected $_rewardManager;


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
            \Pley\Referral\RewardManager $rewardManager)
    {
        $this->_config              = $config;
        $this->_mail                = $mail;
        $this->_dbManager           = $dbManager;
        $this->_subscriptionMgr     = $subscriptionMgr;
        $this->_userBillingMgr      = $userBillingManager;
        $this->_userSubscriptionMgr = $userSubscriptionMgr;
        $this->_shipmentMgr         = $shipmentMgr;

        $this->_userDao              = $userDao;
        $this->_userAddressDao       = $userAddressDao;
        $this->_userProfileDao       = $userProfileDao;
        $this->_profileSubsDao       = $profileSubscriptionDao;
        $this->_giftDao              = $giftDao;
        $this->_giftPriceDao         = $giftPriceDao;
        $this->_paymentPlanDao       = $paymentPlanDao;
        $this->_userIncompleteRegDao = $userIncompleteRegDao;

        $this->_couponManager = $couponManager;
        $this->_tokenManager  = $tokenManager;
        $this->_rewardManager = $rewardManager;
    }
    
    // Though we are intending to do everything with the Waitlist feature, leaving `_subscriptionWithBillingBase`
    // here for backwards compatibility that allows users to subscribe immediately and start from 
    // the nearest available inventory for the selected subscription.
    // POST /user/register/billing
    protected function _subscriptionWithBillingBase()
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

        // Validating that it is not a duplicate attempt of registration for billing.
        $profileSubList = $this->_profileSubsDao->findByUser($user->getId());
        if (!empty($profileSubList)) {
            throw new \Pley\Exception\User\RegistrationExistingSubscriptionException($user);
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
        $newSubsResult = $this->_dbManager->transaction(
            function() use ($that, $user, $creditCard, $coupon, $paymentPlanId, $subscriptionId) {
                $newSubsResult = $that->_billingRegistrationClosure(
                    $user, $creditCard, $paymentPlanId, $subscriptionId, $coupon
                );

                $this->_registrationCompleted($user);

                return $newSubsResult;
            }
        );

        $this->_triggerNewSubscriptionEvent($user, $newSubsResult, $referralToken);
        
        return $newSubsResult;
    }

    protected function _subscriptionWithGiftTokenBase(){
        // Check that session is valid, and get the user entity if so
        $user = $this->_checkAuthenticated();

        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();

        \ValidationHelper::validate($json, ['token' => 'required|string']);

        $profileSubList = $this->_profileSubsDao->findByUser($user->getId());
        if (!empty($profileSubList)) {
            throw new \Pley\Exception\User\RegistrationExistingSubscriptionException($user);
        }

        $gift = $this->_giftDao->findByToken(strtolower($json['token']));
        \ValidationHelper::entityExist($gift, \Pley\Entity\Gift\Gift::class);
        if ($gift->isRedeemed()) {
            throw new \Pley\Exception\Gift\GiftRedeemedException($user, $gift);
        }

        $that = $this;
        $newSubsResult = $this->_dbManager->transaction(function() use ($that, $user, $gift) {
            $newSubsResult = $that->_giftRegistrationClosure($user, $gift);

            $this->_registrationCompleted($user);
            $this->_sendGiftRedeemedEmail($gift);

            return $newSubsResult;
        });

        $this->_triggerNewSubscriptionEvent($user, $newSubsResult);
    }
    
    // ---------------------------------------------------------------------------------------------
    // Helper methods for Creating a new Account ---------------------------------------------------

    /**
     * Validates that the user data map contains all the required fields and the types are correct.
     * @param array $userData Map with the user data
     * @throws \Pley\Http\Request\Exception\InvalidParameterException
     */
    protected function _validateUserData(array $userData)
    {
        $userRules = [
            'firstName' => 'required|string',
            'lastName'  => 'required|string',
            'email'     => 'required|email',
            'password'  => 'required|string',
            'fbUserId'  => 'sometimes',
            'metadata'  => 'sometimes|array',
            'referrer'  => 'sometimes|string',
        ];
        \ValidationHelper::validate($userData, $userRules);

        if (isset($userData['metadata'])) {
            $metadataRules = [
                'subscriptionId' => 'required|integer',
                'paymentPlanId'  => 'required|integer',
                'profile'        => 'required|array',
            ];
            \ValidationHelper::validate($userData['metadata'], $metadataRules);

            $metadataProfileRules = [
                'name'      => 'required|alpha_space_dash',
                'gender'    => 'required|in:male,female',
                'shirtSize' => 'required|integer',
            ];
            \ValidationHelper::validate($userData['metadata']['profile'], $metadataProfileRules);
        }
    }

    /**
     * Validates that the profile data map contains all the required fields and the types are correct.
     * @param array $profileData Map with the user data
     * @throws \Pley\Http\Request\Exception\InvalidParameterException
     */
    protected function _validateProfileData(array $profileData)
    {
        $profileRules = [
            'gender'    => 'required|in:male,female',
            'firstName' => 'required|string',
            'shirtSize' => 'required|integer',
            'lastName'  => 'sometimes|string',
            'birthDate' => 'sometimes|date|date_format:Y-m-d',
        ];
        \ValidationHelper::validate($profileData, $profileRules);
    }

    /**
     * Validates that the address data map contains all the required fields and the types are correct.
     * @param array $addressData Map with the user data
     * @param \Pley\Entity\User\UserAddress The Shipping verified address object
     * @throws \Pley\Http\Request\Exception\InvalidParameterException
     */
    protected function _validateAddressData(array $addressData)
    {
        $addressRules = [
            'street1'  => 'required',
            'street2'  => 'sometimes',
            'phone'    => 'sometimes',
            'city'     => 'required|alpha_dot_space',
            'state'    => 'sometimes',
            'country'  => 'required|alpha_space',
            'zip'      => 'required'
        ];
        \ValidationHelper::validate($addressData, $addressRules);

        $inputUserAddress = \Pley\Entity\User\UserAddress::forVerification(
            $addressData['street1'],
            $addressData['street2'],
            (isset($addressData['phone'])) ? $addressData['phone']: null,
            $addressData['city'],
            $addressData['state'],
            $addressData['country'],
            $addressData['zip']
        );
        $this->_shipmentMgr->validateSupportedDestination($inputUserAddress);
        $verifiedAddress = $this->_shipmentMgr->verifyAddress($inputUserAddress);
        $this->_shipmentMgr->validateSupportedDestination($verifiedAddress);

        return $verifiedAddress;
    }
    
    /**
     * Checks that the Credit Card data is correct and valid.
     * @param \Pley\Entity\User\User $user
     * @param array                  $billingData Map with the Credit Card data
     * @return \Pley\Payment\Method\CreditCard
     */
    protected function _validateBillingData(\Pley\Entity\User\User $user, array $billingData)
    {
        $billingRules = [
            'ccNumber'            => 'required',
            'cvv'                 => 'required',
            'expMonth'            => 'required',
            'expYear'             => 'required',
            'paymentPlanId'       => 'required|integer',
            'subscriptionId'      => 'required|integer',
            'isReceiveNewsletter' => 'required|boolean',
            'billingAddress'      => 'required',
        ];
        \ValidationHelper::validate($billingData, $billingRules);

        $creditCard = new \Pley\Payment\Method\CreditCard(
            $billingData['ccNumber'], $billingData['expMonth'], $billingData['expYear']
        );
        $creditCard->setCVV($billingData['cvv']);

        $billingAddressData = $billingData['billingAddress'];

        // Billing Address can be either an Integer representing an User stored Address ID
        // or it can be a map with the specific billing address
        if (!is_numeric($billingAddressData)) {
            $billingAddressRules = [
                'street1'  => 'sometimes',
                'street2'  => 'sometimes',
                'city'     => 'sometimes|alpha_dot_space',
                'state'    => 'sometimes|alpha',
                'country'  => 'sometimes|alpha_space',
                'zip'      => 'required'
            ];
            \ValidationHelper::validate($billingAddressData, $billingAddressRules);

            $creditCard->setBillingAddress(new \Pley\Payment\Method\BillingAddress(
                $billingAddressData['street1'],
                $billingAddressData['street2'],
                $billingAddressData['city'],
                $billingAddressData['state'],
                $billingAddressData['zip'],
                $billingAddressData['country']
            ));

        } else {
            $userAddressId = $billingAddressData;
            $userAddress   = $this->_userAddressDao->find($userAddressId);

            // This should not happen unless somebody is trying to forge an API call, so it is more
            // of a validation check than an actual runtime error check
            if ($user->getId() != $userAddress->getUserId()) {
                throw new \Exception('Mismatching Relationship between User and Address');
            }

            $creditCard->setBillingAddress(new \Pley\Payment\Method\BillingAddress(
                $userAddress->getStreet1(),
                $userAddress->getStreet2(),
                $userAddress->getCity(),
                $userAddress->getState(),
                $userAddress->getZipCode(),
                $userAddress->getCountry()
            ));
        }

        // Now validating with the Payment Vendor that the card is good to go.
        $this->_userBillingMgr->valdiateCard($user, $creditCard);

        return $creditCard;
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
     * Triggers the Event related to creating a new subscription
     * @param \Pley\User\NewSubscriptionResult $newSubsResult
     */
    protected function _triggerNewSubscriptionEvent(
            \Pley\Entity\User\User $user, \Pley\User\NewSubscriptionResult $newSubsResult, $referralToken = null)
    {
        $eventDataMap = [
            'user'                  => $user,
            'newSubscriptionResult' => $newSubsResult,
        ];
        if (isset($referralToken)) {
            $eventDataMap['referralToken'] = $referralToken;
        }
        
        \Event::fire(\Pley\Enum\EventEnum::SUBSCRIPTION_CREATE, $eventDataMap);
    }
    
    /**
     * If there is an entry for Incomplete User, remove it.
     * <p>This record is not needed if the user finished registration.</p>
     * @param \Pley\Entity\User\User $user
     */
    protected function _registrationCompleted(\Pley\Entity\User\User $user)
    {
        $incompleteReg = $this->_userIncompleteRegDao->findByUser($user->getId());
        if (isset($incompleteReg)) {
            $this->_userIncompleteRegDao->delete($incompleteReg);
        }
    }
    
    
    // ---------------------------------------------------------------------------------------------
    // Helper methods for Finishing registration ---------------------------------------------------
    
    /**
     * Closure to Add the user into the vendor payment system and create the first subscription as
     * a transaction.
     *
     * @param \Pley\Entity\User\User          $user
     * @param \Pley\Payment\Method\CreditCard $creditCard
     * @param int                             $paymentPlanId
     * @param int                             $subscriptionId
     * @param \Pley\Entity\Coupon\Coupon      $coupon         (Optional)
     * @return \Pley\User\NewSubscriptionResult Object containing data created for subscription
     */
    private function _billingRegistrationClosure(
            \Pley\Entity\User\User $user,
            \Pley\Payment\Method\CreditCard $creditCard,
            $paymentPlanId, $subscriptionId, \Pley\Entity\Coupon\Coupon $coupon = null)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        // Creating the Payment Account and Method so we can then create the first subscription

        $paymentMethod = $this->_userBillingMgr->addCard($user, $creditCard);

        // Since this is registration, this is also the first card added to the vendor payment system
        // and as such, the Default automatically, so no need to make a new call to the vendor to
        // set this Card as the Default one, just update the DB relationship.
        $user->setDefaultPaymentMethodId($paymentMethod->getId());
        $this->_userDao->save($user);

        // Retrieving the first and virtually only profile stored so far (two things can happen
        // if full account is registered before billing, we'll have a profile, but if account is
        // incomplete before billing, we may not have a user profile)
        $userProfileList = $this->_userProfileDao->findByUser($user->getId());
        if (empty($userProfileList)) {
            $userProfile = \Pley\Entity\User\UserProfile::withDummy($user->getId());
            $this->_userProfileDao->save($userProfile);
        } else {
            $userProfile = $userProfileList[0];
        }

        // Retrieving the first address (same concept as with user profile)
        $userAddressList = $this->_userAddressDao->findByUser($user->getId());
        $userAddress     = !empty($userAddressList)? $userAddressList[0] : null;

        // Creating the Paid subscription and getting the list of Items added as a result
        try {
            $newSubsResult = $this->_userSubscriptionMgr->addPaidSubscription(
                $user, $userProfile, $paymentMethod, $subscriptionId, $paymentPlanId, $userAddress, $coupon
            );

        // If any failure occured while trying to set up the first paid subscription, we need to
        // remove the card added into the Vendor Billing system to avoid issues like:
        // * The same card is retried but cannot be added because it exists in the vendor but not in our system
        // * A new card is added, but since the first one was the default, the second one won't be used
        //   and a misleading bad charge error occurs when trying a default card that is not the default.
        } catch (\Exception $e) {
            // Since deleting a vendor card is not a desired behavior on the system, and this is an
            // exception to that behavior, we need to use reflection to access the hidden method to
            // do this.
            $refClass = new \ReflectionClass(get_class($this->_userBillingMgr));
            $refMethod = $refClass->getMethod('_deleteFirstCard');
            $refMethod->setAccessible(true);
            $refMethod->invoke($this->_userBillingMgr, $user, $paymentMethod);

            // Now that we have reverted the added card, we can proceed to propagate the exception
            throw $e;
        }

        return $newSubsResult;
    }

    /**
     * Closure to Add a gift Non-Recurring subscription for the supplied user.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Gift\Gift $gift
     * @return \Pley\User\NewSubscriptionResult Object containing data created for subscription
     */
    private function _giftRegistrationClosure(\Pley\Entity\User\User $user, \Pley\Entity\Gift\Gift $gift)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        // Retrieving the first and virtually only profile stored so far (as this method is only
        // allowed to be called during registration which only happens once during the User's
        // lifetime, which guarantees only one profile so far.)
        $userProfileList = $this->_userProfileDao->findByUser($user->getId());
        $userProfile     = $userProfileList[0];

        // Retrieving the first address (same restrictions as with profile, so first is guaranteed)
        $userAddressList = $this->_userAddressDao->findByUser($user->getId());
        $userAddress     = $userAddressList[0];

        // Creating the Paid subscription and getting the list of Items added as a result
        $newSubsResult = $this->_userSubscriptionMgr->addGiftSubscription(
            $user, $userProfile, $gift, $userAddress
        );

        return $newSubsResult;
    }

    /**
     * Sends the gift redemption email to the gift sender after the subscription has been added.
     * @param \Pley\Entity\Gift\Gift $gift
     */
    protected function _sendGiftRedeemedEmail(\Pley\Entity\Gift\Gift $gift)
    {
        $giftPrice    = $this->_giftPriceDao->find($gift->getGiftPriceId());
        $subscription = $this->_subscriptionMgr->getSubscription($gift->getSubscriptionId());
        $paymentPlan  = $this->_paymentPlanDao->find($giftPrice->getEquivalentPaymentPlanId());

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
}
