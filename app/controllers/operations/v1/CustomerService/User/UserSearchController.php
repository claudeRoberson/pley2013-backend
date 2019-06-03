<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace operations\v1\CustomerService\User;

/** ♰
 * The <kbd>UserSearchController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class UserSearchController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Dao\User\UserDao */
    protected $_userDao;
    /** @var \Pley\Repository\User\UserRepository */
    protected $_userRepo;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    
    public function __construct(
            \Pley\Dao\User\UserDao $userDao,
            \Pley\Repository\User\UserRepository $userRepo,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao)
    {
        parent::__construct();
        
        $this->_userDao        = $userDao;
        $this->_userRepo       = $userRepo;
        $this->_profileSubsDao = $profileSubsDao;
    }
    
    // POST /cs/user/search/recent
    public function getCurrent()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        
        // Getting the JSON input as an assoc array
        $json = \Input::json()->all();
        
        $rules = ['tzMinutesOffset'  => 'required|integer'];
        \ValidationHelper::validate($json, $rules);
        
        $tzMinutesOffset = -$json['tzMinutesOffset'];
        
        $userList = $this->_userDao->getRecentSignupList($tzMinutesOffset);
        
        $responseArray = ['userList' => []];
        foreach ($userList as $user) {
            $responseArray['userList'][] = $this->_parseUser($user);
        }
        
        return \Response::json($responseArray);
    }
    
    // POST /cs/user/search
    public function search()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        
        // Getting the JSON input as an assoc array
        $json = \Input::json()->all();
        
        $rules = ['input'  => 'required|string'];
        \ValidationHelper::validate($json, $rules);
        
        $userList = $this->_userRepo->customerServiceSearch($json['input']);
        
        $responseArray = ['userList' => []];
        foreach ($userList as $user) {
            $responseArray['userList'][] = $this->_parseUser($user);
        }
        
        return \Response::json($responseArray);
    }
    
    private function _parseUser(\Pley\Entity\User\User $user)
    {
        $subscriptionList = $this->_profileSubsDao->findByUser($user->getId());
        
        $responseArray = [
            'user' => [
                'id'            => $user->getId(),
                'firstName'     => $user->getFirstName(),
                'lastName'      => $user->getLastName(),
                'email'         => $user->getEmail(),
                'createdAt'     => $user->getCreatedAt(),
                'vendor' => [
                    'billing' => [
                        'systemId'  => $user->getVPaymentSystemId(),
                        'accountId' => $user->getVPaymentAccountId(),
                    ],
                ],
                'hasSubscriptions' => !empty($subscriptionList)
            ]
            
        ];
        
        return $responseArray;
    }
}
