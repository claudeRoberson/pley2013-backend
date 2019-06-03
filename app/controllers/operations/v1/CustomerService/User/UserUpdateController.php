<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace operations\v1\CustomerService\User;

use \Pley\Db\AbstractDatabaseManager as DatabaseManager;

/**
 * The <kbd>UserUpdateController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class UserUpdateController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\User\UserDao */
    protected $_userDao;
    /** @var \Pley\Payment\PaymentManagerFactory */
    protected $_paymentMgrFactory;
    
    public function __construct(DatabaseManager $dbManager,
            \Pley\Dao\User\UserDao $userDao,
             \Pley\Payment\PaymentManagerFactory $paymentMgrFactory)
    {
        parent::__construct();
        
        $this->_dbManager         = $dbManager;
        $this->_userDao           = $userDao;
        $this->_paymentMgrFactory = $paymentMgrFactory;
    }
    
    // PUT /cs/user/{userId}/email
    public function updateEmail($userId)
    {
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();
        
        // Getting the JSON input as an assoc array
        $json = \Input::json()->all();
        
        $newEmail = $json['email'];
        $status = $this->_validateEmailChange($userId, $newEmail);
        
        // If the validation returns false, it means that the email of the user is the same as the
        // one supplied so no change is needed
        if ($status === false) {
            return \Response::json(['success' => true]);
        }
        
        $that = $this;
        $this->_dbManager->transaction(function() use ($that, $userId, $newEmail) {
            $that->_updateEmailClosure($userId, $newEmail);
        });
        
        return \Response::json(['success' => true]);
    }
    
    private function _validateEmailChange($userId, $newEmail)
    {
        $rules = ['email' => 'required|string'];
        \ValidationHelper::validate(['email' => $newEmail], $rules);
        
        $targetUser   = $this->_userDao->find($userId);
        if ($targetUser->getEmail() == $newEmail) {
            return false;
        }
        
        $existingUser = $this->_userDao->findByEmail($newEmail);
        if (!empty($existingUser)) {
            throw new \Pley\Exception\User\ExistingUserException($existingUser);
        }
        
        return true;
    }
    
    private function _updateEmailClosure($userId, $newEmail)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $user = $this->_userDao->find($userId);
        
        $user->setEmail($newEmail);
        $this->_userDao->save($user);
        
        // If the user does not have a Vendor Payment System assigned yet, then just finish and return
        if (empty($user->getVPaymentSystemId())) {
            return true;
        }
        
        // Otherwise, we need to update the Vendor Payment System account as well
        $paymentMgr = $this->_paymentMgrFactory->getManager($user->getVPaymentSystemId());
        $paymentMgr->updateUserEmail($user);
        
        return true;
    }
}
