<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace api\v1\User;

/**
 * The <kbd>UserRegistrationController</kbd> takes care of adding new users.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package api.v1
 */
class UserRegistrationController extends \api\shared\User\SharedUserRegistrationController
{
    // POST /user/register/account
    public function newAccount()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();

        $this->_validateUserData($json['user']);
        $this->_validateProfileData($json['profile']);
        $verifiedAddress = $this->_validateAddressData($json['address']);

        // Check that it is not an attempt to a duplicate account.
        $existingUser = $this->_userDao->findByEmail($json['user']['email']);
        if (!empty($existingUser)) {
            throw new \Pley\Exception\User\RegistrationExistingUserException($existingUser);
        }

        // Now that we know is a new account (by email) we can proceed to add all the information
        $that = $this;
        /* @var $newAcctObj __URC_NewAccount */
        $newAcctObj = $this->_dbManager->transaction(function() use ($that, $json, $verifiedAddress) {
            $userData    = $json['user'];
            $profileData = $json['profile'];

            return $that->_newAccountClosure($userData, $profileData, $verifiedAddress);
        });

        // Flagging the session as not fresh, so that it will be written by the application
        // stack (look at \Pley\Laravel\Foundation\Session\Middleware)
        // Only User Login and User Registration should do this.
        \Session::set(\Pley\Http\Session\Session::IS_FRESH_KEY, false);

        // Auto-logging user in the session
        \Auth::loginUsingId($newAcctObj->user->getId());

        $arrayResponse = [
            'userId'        => $newAcctObj->user->getId(),
            'userProfileId' => $newAcctObj->profile->getId(),
            'userAddressId' => $newAcctObj->address->getId(),
            'userReferralRewardAmount' => $this->_rewardManager->getTotalPendingAcquisitionRewardAmount($newAcctObj->user->getEmail())
        ];
        $jsonResponse  = \Response::json($arrayResponse);

        return \ResponseHelper::setSessionHeader($jsonResponse);
    }
    
    // Though we are intending to do everything with the Waitlist feature, leaving `subscriptionWithBilling`
    // and `subscriptionWithGiftToken` here for backwards compatibility that allows users to subscribe
    // immediately and start from the nearest available inventory for the selected subscription.
    
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

    // POST /user/register/gift
    public function subscriptionWithGiftToken()
    {
        parent::_subscriptionWithGiftTokenBase();
        return \Response::json(['success' => true]);
    }

    // ---------------------------------------------------------------------------------------------
    // Helper methods for Creating a new Account ---------------------------------------------------

    /**
     * Method to add a new User with its address and profile data into the system.
     * <p>This method is expected to be called within a DB Transaction.</p>
     *
     * @param array                         $userData
     * @param array                         $profileData
     * @param \Pley\Entity\User\UserAddress $verifiedAddress
     * @return __URC_NewAccount
     */
    private function _newAccountClosure(
            array $userData, array $profileData, \Pley\Entity\User\UserAddress $verifiedAddress)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        $userData['country'] = $verifiedAddress->getCountry();
        $user = $this->_newAccountUser($userData);

        $userRegistrationNewAcct = new __URC_NewAccount();
        $userRegistrationNewAcct->user    = $user;
        $userRegistrationNewAcct->profile = $this->_newAccountProfile($user, $profileData);
        $userRegistrationNewAcct->address = $this->_newAccountAddress($user, $verifiedAddress);

        return $userRegistrationNewAcct;
    }

    /**
     * Creates and adds a new User into the system with the supplied data.
     * @param array $userData
     * @return \Pley\Entity\User\User
     */
    private function _newAccountUser(array $userData)
    {
        $passHash = \Hash::make($userData['password']);
        $newUser = \Pley\Entity\User\User::withNew(
            $userData['firstName'], $userData['lastName'], $userData['email'], $passHash
        );
        if (isset($userData['fbUserId'])) {
            $newUser->setFbToken($userData['fbUserId']);
        }
        if (isset($userData['country'])) {
            $newUser->setCountry($userData['country']);
        }
        if (isset($userData['referrer'])) {
            $newUser->setReferrer($userData['referrer']);
        }

        $this->_userDao->save($newUser);

        if (isset($userData['metadata'])) {
            $meta              = $userData['metadata'];
            $userIncompleteReg = new \Pley\Entity\User\UserIncompleteRegistration();
            $userIncompleteReg->setUserId($newUser->getId())
                    ->setSubscriptionId($meta['subscriptionId'])
                    ->setPaymentPlanId($meta['paymentPlanId'])
                    ->setProfileName($meta['profile']['name'])
                    ->setProfileGender($meta['profile']['gender'])
                    ->setProfileShirtSize($meta['profile']['shirtSize']);
            $this->_userIncompleteRegDao->save($userIncompleteReg);
        }
        \Event::fire(\Pley\Enum\EventEnum::USER_ACCOUNT_CREATE, [
            'user' => $newUser
        ]);
        return $newUser;
    }

    /**
     * Creates and adds a new Profile into the system with the supplied data.
     * @param \Pley\Entity\User\User $user
     * @param array $profileData
     * @return \Pley\Entity\User\UserProfile
     */
    private function _newAccountProfile(\Pley\Entity\User\User $user, array $profileData)
    {
        $userProfile = \Pley\Entity\User\UserProfile::withNew(
            $user->getId(),
            $profileData['gender'],
            $profileData['shirtSize'],
            $profileData['firstName'],
            isset($profileData['lastName'])? $profileData['lastName'] : null,
            isset($profileData['birthDate'])? $profileData['birthDate'] : null
        );

        //lets save this userProfile
        $this->_userProfileDao->save($userProfile);

        return $userProfile;
    }

    /**
     * Creates and adds a new Address into the system with the supplied data.
     * <p>It is assumed that the address has passed verification.</p>
     *
     * @param \Pley\Entity\User\User        $user
     * @param \Pley\Entity\User\UserAddress $verifiedAddress
     * @return \Pley\Entity\User\UserAddress
     */
    private function _newAccountAddress(
            \Pley\Entity\User\User $user, \Pley\Entity\User\UserAddress $verifiedAddress)
    {
        // Adding the UserId and Zone to the Verified Address and then saving it
        $verifiedAddress->setUserId($user->getId());
        $this->_shipmentMgr->assignShippingZones($verifiedAddress);

        $this->_userAddressDao->save($verifiedAddress);
        
        return $verifiedAddress;
    }

}


/**
 * Small helper class for registration to return the inserted objects as one object instead of an
 * associative arrays which do not provide autocompletion features on IDE's and are more prone to
 * typo errors harder to debug
 * @author Alejandro Salazar <alejandros@pley.com>
 */
class __URC_NewAccount
{
    /** @var \Pley\Entity\User\User */
    public $user;
    /** @var \Pley\Entity\User\UserProfile */
    public $profile;
    /** @var \Pley\Entity\User\UserAddress */
    public $address;
}
