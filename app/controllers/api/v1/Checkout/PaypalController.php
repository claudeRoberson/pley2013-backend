<?php

namespace api\v1\Checkout;

use PayPal\Api\Agreement;
use Pley\Entity\User\User;
use Pley\Enum\PaymentSystemEnum;
use Pley\Enum\Paypal\AgreementStateEnum;
use Pley\Enum\Shipping\ShippingZoneEnum;
use Pley\Exception\Payment\PaymentMethodDeclinedException;
use Pley\Exception\Paypal\PaymentDeclinedException;

/** @copyright Pley (c) 2017, All Rights Reserved */

/**
 * Class description goes here
 *
 * @author Vsevolod Yatsiuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class PaypalController extends \api\shared\User\SharedUserRegistrationController
{
    /** @var \Pley\Price\PriceManager */
    protected $_priceManager;
    /**
     * @var \Pley\Billing\PaypalManager
     */
    protected $_paypalManager;
    /**
     * @var \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao
     */
    protected $_vendorPaymentPlanDao;

    /** @var \Pley\Dao\User\UserDao */
    protected $_userDao;

    /** @var \Pley\User\UserSubscriptionManager */
    protected $_userSubscriptionMgr;

    /** @var \Pley\Shipping\AbstractShipmentManager */
    protected $_shipmentMgr;

    /** @var \Pley\Dao\Payment\UserPaymentMethodDao */
    protected $_userPaymentMethodDao;

    /** @var \Pley\Dao\User\UserIncompleteRegistrationDao */
    protected $_userIncompleteRegDao;

    /** @var \Pley\Referral\TokenManager */
    protected $_tokenManager;


    /**
     * PaypalController constructor.
     * @param \Pley\Price\PriceManager $priceManager
     * @param \Pley\Billing\PaypalManager $paypalManager
     * @param \Pley\Dao\User\UserDao $userDao
     * @param \Pley\Dao\User\UserAddressDao $userAddressDao
     * @param \Pley\Dao\User\UserProfileDao $userProfileDao
     * @param \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao
     * @param \Pley\Coupon\CouponManager $couponManager
     * @param \Pley\User\UserSubscriptionManager $userSubscriptionMgr
     * @param \Pley\Shipping\Impl\EasyPost\ShipmentManager $shipmentMgr
     * @param \Pley\Dao\Payment\UserPaymentMethodDao $userPaymentMethodDao
     */
    public function __construct(
        \Pley\Price\PriceManager $priceManager,
        \Pley\Billing\PaypalManager $paypalManager,
        \Pley\Dao\User\UserDao $userDao,
        \Pley\Dao\User\UserAddressDao $userAddressDao,
        \Pley\Dao\User\UserProfileDao $userProfileDao,
        \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao,
        \Pley\Coupon\CouponManager $couponManager,
        \Pley\User\UserSubscriptionManager $userSubscriptionMgr,
        \Pley\Shipping\Impl\EasyPost\ShipmentManager $shipmentMgr,
        \Pley\Dao\Payment\UserPaymentMethodDao $userPaymentMethodDao,
        \Pley\Dao\User\UserIncompleteRegistrationDao $userIncompleteRegDao,
        \Pley\Referral\TokenManager $tokenManager
    )
    {
        $this->_priceManager = $priceManager;
        $this->_paypalManager = $paypalManager;
        $this->_userDao = $userDao;
        $this->_vendorPaymentPlanDao = $vendorPaymentPlanDao;
        $this->_couponManager = $couponManager;
        $this->_userSubscriptionMgr = $userSubscriptionMgr;
        $this->_shipmentMgr = $shipmentMgr;
        $this->_userProfileDao = $userProfileDao;
        $this->_userAddressDao = $userAddressDao;
        $this->_userPaymentMethodDao = $userPaymentMethodDao;
        $this->_userIncompleteRegDao = $userIncompleteRegDao;
        $this->_tokenManager  = $tokenManager;
    }

    //POST /api/v1/checkout/paypal/init-subscription

    /**
     * @return mixed
     */
    public function initSubscription()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();

        \ValidationHelper::validate($json, [
            'subscriptionId' => 'required',
            'paymentPlanId' => 'required'
        ]);

        $subscriptionId = $json['subscriptionId'];
        $paymentPlanId = $json['paymentPlanId'];

        if (!$this->_userSubscriptionMgr->isCompatibleSubscription($subscriptionId, $paymentPlanId)) {
            throw new \Exception('Incompatible Payment Plan for Subscription');
        }

        $vendorPaymentPlan = $this->_vendorPaymentPlanDao->findByPaymentPlan(
            $paymentPlanId,
            ShippingZoneEnum::DEFAULT_ZONE_ID,
            PaymentSystemEnum::PAYPAL
        );

        if (!$vendorPaymentPlan) {
            throw new \Exception('Missing payment plan for specified subscription');
        }

        //load and validate coupon if code has been provided
        $coupon = null;

        $couponCode = (isset($json['couponCode'])) ? $json['couponCode'] : null;
        if ($couponCode) {
            $coupon = $this->_couponManager->validateCouponCode(
                $couponCode,
                \Pley\Entity\User\User::dummy(),
                $subscriptionId,
                $paymentPlanId);
        }

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

        $paypalBillingPlanId = $vendorPaymentPlan->getVPaymentPlanId();

        $response = [];

        $agreement = $this->_paypalManager->createBillingAgreement(
            $paypalBillingPlanId,
            $subscriptionId,
            $paymentPlanId,
            $coupon);

        $response['agreementUrl'] = $agreement->getApprovalLink();

        preg_match('/token=(.*)/', $agreement->getApprovalLink(), $matches);

        $response['token'] = $matches[1];

        return \Response::json($response);
    }

    //POST /api/v1/checkout/paypal/execute-subscription

    /**
     * @return mixed
     */
    public function executeSubscription()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        $json = \Input::json()->all();

        \ValidationHelper::validate($json, [
            'paymentToken' => 'required',
            'subscriptionId' => 'required',
            'paymentPlanId' => 'required',
            'userId' => 'required',
            'userAddressId' => 'required',
            'userProfileId' => 'required'
        ]);

        $paymentToken = $json['paymentToken'];
        $subscriptionId = $json['subscriptionId'];
        $paymentPlanId = $json['paymentPlanId'];

        $coupon = null;

        $couponCode = (isset($json['couponCode'])) ? $json['couponCode'] : null;
        if ($couponCode) {
            $coupon = $this->_couponManager->validateCouponCode(
                $couponCode,
                \Pley\Entity\User\User::dummy(),
                $subscriptionId,
                $paymentPlanId);
        }

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

        $user = null;
        $address = null;
        $profile = null;

        $agreement = $this->_paypalManager->executeBillingAgreement($paymentToken);

        if($agreement->getState() !== AgreementStateEnum::ACTIVE){
            throw new PaymentDeclinedException($agreement->getId());
        }

        if (isset($json['userId']) && isset($json['userAddressId']) && isset($json['userProfileId'])) {
            $user = $this->_userDao->find($json['userId']);
            $address = $this->_userAddressDao->find($json['userAddressId']);
            $profile = $this->_userProfileDao->find($json['userProfileId']);
            $this->_setUserVendorPaymentData($user, $agreement);
        } else {
            $user = $this->_preCreateAccountFromAgreement($agreement);
            $address = $this->_preCreateAddress($user, $agreement);
            $profile = $this->_preCreateProfile($user);
        }
        $preCreatedPaymentMethod = $this->_preCreatePaymentMethod($user, $agreement);

        try {
            $newSubsResult = $this->_userSubscriptionMgr->addPaypalSubscription(
                $user,
                $profile,
                $preCreatedPaymentMethod,
                $subscriptionId,
                $paymentPlanId,
                $agreement,
                $address,
                $coupon
            );
            $this->_registrationCompleted($user);

        } catch (\Exception $e) {
            throw $e;
        }

        $this->_triggerNewSubscriptionEvent($user, $newSubsResult, $referralToken);

        $response = [
            'success' => true,
            'shippingAddress' => $this->_mapAgreementDataToResponse($agreement),
            'user' => $this->_mapUserDataToResponse($user),
            'userProfileId' => $profile->getId(),
            'userAddressId' => $address->getId(),
            'userPaymentMethodId' => $preCreatedPaymentMethod->getId(),
            'profileSubscriptionId' => $newSubsResult->profileSubscription->getId()
        ];

        return \Response::json($response);
    }

    //POST /api/v1/checkout/paypal/complete-registration

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function completeRegistration()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        $json = \Input::json()->all();

        $preCreatedUser = $this->_userDao->find($json['user']['userId']);
        $preCreatedAddress = $this->_userAddressDao->find($json['address']['addressId']);

        if (!$preCreatedUser) {
            throw new \Exception('Cannot find user to complete registration.');
        }
        if (!$preCreatedAddress) {
            throw new \Exception('Cannot find address to complete registration.');
        }

        $this->_validateProfileData($json['profile']);
        $verifiedAddress = $this->_validateAddressData($json['address']);
        $verifiedAddress->setId($preCreatedAddress->getId());

        $profileData = $json['profile'];
        $userData = $json['user'];

        $user = $this->_updateAccount($preCreatedUser, $userData);
        $profile = $this->_updateAccountProfile($user, $profileData);
        $address = $this->_updateAccountAddress($user, $verifiedAddress);

        \Session::set(\Pley\Http\Session\Session::IS_FRESH_KEY, false);

        // Auto-logging user in the session
        \Auth::loginUsingId($user->getId());

        $arrayResponse = [
            'userId' => $user->getId(),
            'userProfileId' => $profile->getId(),
            'userAddressId' => $address->getId(),
        ];
        $jsonResponse = \Response::json($arrayResponse);

        return \ResponseHelper::setSessionHeader($jsonResponse);
    }

    /**
     * Creates and adds a new User into the system with the supplied data.
     * @param Agreement $agreement
     * @return \Pley\Entity\User\User
     */
    private function _preCreateAccountFromAgreement(Agreement $agreement)
    {
        $payerInfo = $agreement->getPayer()->getPayerInfo();
        $passHash = \Hash::make(str_random(10));

        $existingUser = $this->_userDao->findByEmail($payerInfo->getEmail());
        if (!empty($existingUser)) {
            return $existingUser;
        }

        $newUser = \Pley\Entity\User\User::withNew(
            $payerInfo->getFirstName(), $payerInfo->getLastName(), $payerInfo->getEmail(), $passHash
        );
        if (isset($userData['country'])) {
            $newUser->setCountry($payerInfo->getCountryCode());
        }
        $newUser->setVPaymentAccount(PaymentSystemEnum::PAYPAL, $payerInfo->getPayerId());
        $this->_userDao->save($newUser);
        return $newUser;
    }

    /**
     * @param \Pley\Entity\User\User $user
     * @param Agreement $agreement
     * @return \Pley\Entity\User\UserAddress
     */
    private function _preCreateAddress(\Pley\Entity\User\User $user, Agreement $agreement)
    {
        $shippingAddress = $agreement->getShippingAddress();

        $userAddress = new \Pley\Entity\User\UserAddress(
            null,
            $user->getId(),
            $shippingAddress->getLine1(),
            $shippingAddress->getLine2(),
            $shippingAddress->getCity(),
            $shippingAddress->getState(),
            $shippingAddress->getCountryCode(),
            $shippingAddress->getPostalCode(),
            ShippingZoneEnum::DEFAULT_ZONE_ID,
            0,
            time(),
            time()
        );

        $this->_userAddressDao->save($userAddress);
        return $userAddress;
    }

    /**
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\User\UserProfile
     */
    private function _preCreateProfile(\Pley\Entity\User\User $user)
    {
        $dummyUserProfile = \Pley\Entity\User\UserProfile::withNew(
            $user->getId(),
            null,
            null,
            null,
            null,
            null
        );
        $this->_userProfileDao->save($dummyUserProfile);
        return $dummyUserProfile;
    }

    /**
     * @param User $user
     * @param Agreement $agreement
     * @return \Pley\Entity\Payment\UserPaymentMethod
     */
    private function _preCreatePaymentMethod(\Pley\Entity\User\User $user, Agreement $agreement)
    {
        $paymentMethod = \Pley\Entity\Payment\UserPaymentMethod::withNew(
            $user->getId(), PaymentSystemEnum::PAYPAL, $agreement->getId()
        );
        $this->_userPaymentMethodDao->save($paymentMethod);
        return $paymentMethod;
    }

    /**
     * @param Agreement $agreement
     * @return array
     */
    protected function _mapAgreementDataToResponse(Agreement $agreement)
    {
        $shippingAddress = $agreement->getShippingAddress();
        $response = [
            "street1" => $shippingAddress->getLine1(),
            "street2" => $shippingAddress->getLine2(),
            "country" => $shippingAddress->getCountryCode(),
            "city" => $shippingAddress->getCity(),
            "state" => $shippingAddress->getState(),
            "zipCode" => $shippingAddress->getPostalCode()
        ];
        return $response;
    }

    /**
     * @param User $user
     * @return array
     */
    protected function _mapUserDataToResponse(User $user)
    {
        $response = [
            "userId" => $user->getId(),
            "email" => $user->getEmail(),
            "firstName" => $user->getFirstName(),
            "lastName" => $user->getLastName()
        ];
        return $response;
    }

    /**
     * @param User $user
     * @param Agreement $agreement
     * @throws \Exception
     */

    private function _setUserVendorPaymentData(\Pley\Entity\User\User $user, Agreement $agreement)
    {
        if ($user->getVPaymentSystemId()) {
            return;
        }
        try {
            $payerInfo = $agreement->getPayer()->getPayerInfo();
            $user->setVPaymentAccount(PaymentSystemEnum::PAYPAL, $payerInfo->getPayerId());
            $this->_userDao->save($user);
        } catch (\Exception $e) {
            throw new \Exception('There was an issue with PayPal payment: ' . $e->getMessage());
        }
    }

    /**
     * Creates and adds a new Profile into the system with the supplied data.
     * @param \Pley\Entity\User\User $user
     * @param array $userData
     * @return \Pley\Entity\User\User
     */

    private function _updateAccount(\Pley\Entity\User\User $user, array $userData)
    {
        $user->setPassword(\Hash::make($userData['password']));
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['firstName']);
        $user->setLastName($userData['lastName']);
        $this->_userDao->save($user);

        return $user;
    }

    /**
     * Updates a profile in a system with the supplied data.
     * @param \Pley\Entity\User\User $user
     * @param array $profileData
     * @return \Pley\Entity\User\UserProfile
     */
    private function _updateAccountProfile(\Pley\Entity\User\User $user, array $profileData)
    {
        $userProfile = $this->_userProfileDao->find($profileData['profileId']);
        $userProfile->setFirstName($profileData['firstName']);
        $userProfile->setLastName(isset($profileData['lastName']) ? $profileData['lastName'] : null);
        $userProfile->setTypeShirtSizeId($profileData['shirtSize']);
        $userProfile->setGender($profileData['gender']);
        $userProfile->setBirthDate(isset($profileData['birthDate']) ? $profileData['birthDate'] : null);
        $this->_userProfileDao->save($userProfile);
        return $userProfile;
    }

    /**
     * Updates Address into the system with the supplied data.
     * <p>It is assumed that the address has passed verification.</p>
     *
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\User\UserAddress $verifiedAddress
     * @return \Pley\Entity\User\UserAddress
     */
    private function _updateAccountAddress(
        \Pley\Entity\User\User $user, \Pley\Entity\User\UserAddress $verifiedAddress)
    {
        // Adding the UserId and Zone to the Verified Address and then saving it
        $verifiedAddress->setUserId($user->getId());
        $this->_shipmentMgr->assignShippingZones($verifiedAddress);

        $this->_userAddressDao->save($verifiedAddress);

        return $verifiedAddress;
    }
}