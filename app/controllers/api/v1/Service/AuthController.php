<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace api\v1\Service;

use \Illuminate\Auth\AuthManager;
use \Illuminate\Session\SessionManager;

use \Pley\Http\Request\Exception\InvalidParameterException;
use \Pley\Http\Session\Session as PleySession;

/**
 * The <kbd>AuthController</kbd> takes care of authenticating the user for login and logout.
 *
 * @author Seva Yatsiuk
 * @package api.v1
 */
class AuthController extends \api\v1\BaseController
{
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
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao * */
    protected $_profileSubsDao;

    public function __construct(SessionManager $sessionMgr, AuthManager $authMgr,
                                \Pley\Dao\User\UserDao $userDao,
                                \Pley\Dao\User\UserProfileDao $userProfileDao,
                                \Pley\Dao\User\UserAddressDao $userAddressDao,
                                \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao
    )
    {
        $this->_sessionMgr = $sessionMgr;
        $this->_authMgr = $authMgr;

        $this->_userDao = $userDao;
        $this->_userProfileDao = $userProfileDao;
        $this->_userAddressDao = $userAddressDao;
        $this->_profileSubsDao = $profileSubsDao;
    }

    //POST /service/auth/login
    public function login()
    {
        \RequestHelper::checkJsonRequest();
        $response = $this->_userLogin();

        return $response;
    }

    //POST /service/auth/register
    public function register()
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
        $newUser = \Pley\Entity\User\User::withNew(
            $userData['firstName'], $userData['lastName'], $userData['email'], $passHash
        );
        $newUser->setCountry(null);

        $this->_userDao->save($newUser);

        return $this->_userResponse($newUser);

    }

    public function logout()
    {
        \Auth::logout();

        $arrayResponse = ['status' => 'success'];
        return \Response::json($arrayResponse);
    }

    private function _userLogin()
    {
        // Getting the JSON input as an assoc array
        $json = \Input::json()->all();
        $this->_validateLogin($json);

        if (!\Auth::attempt($json)) {
            throw new \Pley\Exception\Auth\InvalidAuthCredentialsException($json['email']);
        }

        $user = $this->_userDao->find(\Auth::id());

        return $this->_userResponse($user);
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
     * Validates that the user data map contains all the required fields and the types are correct.
     * @param array $userData Map with the user data
     * @throws \Pley\Http\Request\Exception\InvalidParameterException
     */
    protected function _validateUserData(array $userData)
    {
        $userRules = [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string',
        ];
        \ValidationHelper::validate($userData, $userRules);
    }

    /**
     * Creates the login response structure and makes sure to set the session header.
     * @param \Pley\Entity\User\User $user
     * @return \Symfony\Component\HttpFoundation\Response Returns the updated response object
     */
    private function _userResponse(\Pley\Entity\User\User $user)
    {
        $arrayResponse = [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'hasAddress' => false,
            'hasProfile' => false,
            'registrationInit' => true,
        ];

        $addressList = $this->_userAddressDao->findByUser($user->getId());
        $arrayResponse['hasAddress'] = !empty($addressList);

        $profileList = $this->_userProfileDao->findByUser($user->getId());
        if (empty($profileList)) {
            $arrayResponse['hasProfile'] = false;
        } else {
            $arrayResponse['hasProfile'] = !$profileList[0]->isDummy();
        }

        $jsonResponse = \Response::json($arrayResponse);
        return $jsonResponse;
    }
}

