<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace api\v1\User;

use \Illuminate\Auth\AuthManager;
use \Illuminate\Session\SessionManager;

use \Pley\Http\Request\Exception\InvalidParameterException;
use \Pley\Http\Session\Session as PleySession;

/**
 * The <kbd>UserAuthController</kbd> takes care of authenticating the user for login and logout.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @package api.v1
 */
class UserAuthController extends \api\v1\BaseController
{
    private static $IMPERSONATE_KEY = 'imp';
    
    /** @var \Illuminate\Session\SessionManager */
    protected $_sessionMgr;
    /** @var \Illuminate\Auth\AuthManager */
    protected $_authMgr;
    /** @var \Pley\Dao\User\UserDao */
    protected $_userDao;
    /** @var \Pley\Dao\User\UserProfileDao */
    protected $_userProfileDao;
    /** @var \Pley\Dao\User\UserAddressDao */
    protected $_userAddressDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao **/
    protected $_profileSubsDao;
    /** @var \Pley\Dao\User\UserIncompleteRegistrationDao */
    protected $_userIncompleteRegDao;
    /** @var \Pley\Repository\User\UserWaitlistRepository */
    protected $_userWaitlistRepository;

    public function __construct(SessionManager $sessionMgr, AuthManager $authMgr, 
            \Pley\Dao\User\UserDao $userDao,
            \Pley\Dao\User\UserProfileDao $userProfileDao,
            \Pley\Dao\User\UserAddressDao $userAddressDao,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao,
            \Pley\Dao\User\UserIncompleteRegistrationDao $userIncompleteRegDao,
            \Pley\Repository\User\UserWaitlistRepository $userWaitlistRepository)
    {
        $this->_sessionMgr             = $sessionMgr;
        $this->_authMgr                = $authMgr;
        
        $this->_userDao                = $userDao;
        $this->_userProfileDao         = $userProfileDao;
        $this->_userAddressDao         = $userAddressDao;
        $this->_profileSubsDao         = $profileSubsDao;
        $this->_userIncompleteRegDao   = $userIncompleteRegDao;
        $this->_userWaitlistRepository = $userWaitlistRepository;
    }
    
    public function login()
    {
        \RequestHelper::checkJsonRequest();
        
        // Checking if the impersonate attribute was sent, used by admins to check from a user's
        // perspective, if set, it should have the value of an admins valid session ('pases')
        if (\Input::has(self::$IMPERSONATE_KEY)) {
            $response = $this->_adminLoginAs(\Input::get(self::$IMPERSONATE_KEY));
        } else {
            $response = $this->_userLogin();
        }
        
        return $response;
    }
    
    public function logout()
    {
        \Auth::logout();
        
        $arrayResponse = ['status' => 'success'];
        return \Response::json($arrayResponse);
    }
    
    private function _userLogin()
    {
        // If the user is not already logged in, then check request and credentials
        if (!\Auth::check()) {
            // Getting the JSON input as an assoc array
            $json = \Input::json()->all();
            $this->_validateLogin($json);

            if (isset($json['fb_token'])) {
                if (!$this->_fbAuthAttempt($json['fb_token'], $json)) {
                    throw new \Pley\Exception\Auth\InvalidAuthCredentialsException($json['email']);
                }
                
            } else if (!\Auth::attempt($json)) {
                throw new \Pley\Exception\Auth\InvalidAuthCredentialsException($json['email']);
            }
                        
            // Flagging the session as not fresh, so that it will be written by the application
            // stack (look at \Pley\Laravel\Foundation\Session\Middleware)
            // Only User Login and User Registration should do this.
            \Session::set(PleySession::IS_FRESH_KEY, false);
        }
        
        $user = $this->_userDao->find(\Auth::id());
        
        return $this->_loginResponse($user);
    }
    
    private function _adminLoginAs($adminSession)
    {
        $this->_validateAdminSession($adminSession);
        
        // Getting the JSON input as an assoc array
        $json = \Input::json()->all();
        
        $user = $this->_userDao->find($json['userId']);
        if (empty($user)) {
            throw new \Pley\Exception\Auth\InvalidAuthCredentialsException($json['userId']);
        }
        
        // Auto-logging user in the session
        \Auth::loginUsingId($user->getId());
        
        // Flagging the session as not fresh, so that it will be written by the application
        // stack (look at \Pley\Laravel\Foundation\Session\Middleware)
        // Only User Login and User Registration should do this.
        \Session::set(PleySession::IS_FRESH_KEY, false);
        
        
        return $this->_loginResponse($user);
    }
    
    /**
     * Helper function to validate login input
     * @param array $credentials
     * @throws \Pley\Http\Request\Exception\InvalidParameterException
     */
    private function _validateLogin($credentials)
    {
        \ValidationHelper::validate($credentials, ['email' => 'required|email']);
    }
    
    /**
     * Helper method to validate an admin session if one was supplied to allow an admin user on
     * impersonating a client user.
     * <p>For this to happen and provide enough security on this controller, we need to verify
     * that the session is a valid one and that is also flagged as admin session which can only
     * be done by the Admin Login controller (BackendUserAuthController).</p>
     * 
     * @param int $adminSession
     * @throws \Pley\Http\Request\Exception\InvalidParameterException
     */
    private function _validateAdminSession($adminSession)
    {
        $adminSessionId = \Crypt::decrypt($adminSession);
        
        // Checking if the admin session string supplied is a valid session 
        if (!\Session::isValidId($adminSessionId)) {
            throw new InvalidParameterException([self::$IMPERSONATE_KEY => $adminSession]);
        }
        
        /* @var $sesDriver \Illuminate\Session\Store */
        $sesDriver  = $this->_sessionMgr->driver();
        /* @var $authDriver \Illuminate\Auth\Guard */
        $authDriver = $this->_authMgr->driver();

        // It seems there isn't easy way within Laravel to create a plain session object that we
        // could initialize with a specific id, so we are retrieving the default one created and
        // we clone it so that we can temporarily initialize with the supplied session id
        /* @var $adminSesDriver \Illuminate\Session\Store */
        $adminSesDriver = clone ($sesDriver);
        $adminSesDriver->setId($adminSessionId);
        $adminSesDriver->start();
        
        $adminUsrId = $adminSesDriver->get($authDriver->getName());
        $isAdmin    = $adminSesDriver->get(PleySession::IS_ADMIN_KEY);
        
        // If the session does not have a user ID or is not flagged as an admin session, is invalid
        if (empty($adminUsrId) || empty($isAdmin)) {
            throw new InvalidParameterException([self::$IMPERSONATE_KEY => $adminSession]);
        }
    }
    
    private function _fbAuthAttempt($fbToken, $input)
    {
        $user = $this->_userDao->findByFbToken($fbToken);
        
        if (empty($user)) {
            return false;
        }
        
        $isValidAttempt = $user->getEmail() == $input['email'];
        if ($isValidAttempt) {
            \Auth::loginUsingId($user->getId());
        }
        
        return $isValidAttempt;
    }
    
    /**
     * Creates the login response structure and makes sure to set the session header.
     * @param \Pley\Entity\User\User $user
     * @return \Symfony\Component\HttpFoundation\Response Returns the updated response object
     */
    private function _loginResponse(\Pley\Entity\User\User $user)
    {
        $arrayResponse = [
            'firstName'        => $user->getFirstName(),
            'lastName'         => $user->getLastName(),
            'email'            => $user->getEmail(),
            'hasAddress'       => false,
            'hasProfile'       => false,
            'registrationInit' => true,
        ];
        
        $addressList = $this->_userAddressDao->findByUser($user->getId());
        $arrayResponse['hasAddress'] = !empty($addressList);
        
        $profileList = $this->_userProfileDao->findByUser($user->getId());
        if (empty($profileList)) { $arrayResponse['hasProfile'] = false; }
        else { $arrayResponse['hasProfile'] = !$profileList[0]->isDummy(); }
        
        // Since Registration of Account,Profile and Address is done in one shot, to know if it is
        // complete, all we need to look at is if the user has subscriptions
        $subsList = $this->_profileSubsDao->findByUser($user->getId());
        $waitlistItems = $this->_userWaitlistRepository->findWaitlistByUser($user->getId());

        if (empty($subsList) && empty($waitlistItems)) {
            $arrayResponse['registrationInit'] = false;

            $incompleteUserReg = $this->_userIncompleteRegDao->findByUser($user->getId());
            if (empty($incompleteUserReg)) {
                return;
            }
            $arrayResponse['registrationInit'] = [
                'subscriptionId' => $incompleteUserReg->getSubscriptionId(),
                'paymentPlanId'  => $incompleteUserReg->getPaymentPlanId(),
                'profile' => [
                    'name'      => $incompleteUserReg->getProfileName(),
                    'gender'    => $incompleteUserReg->getProfileGender(),
                    'shirtSize' => $incompleteUserReg->getProfileShirtSize(),
                ]
            ];
        }
        
        $jsonResponse = \Response::json($arrayResponse);
        return \ResponseHelper::setSessionHeader($jsonResponse);
    }
}

