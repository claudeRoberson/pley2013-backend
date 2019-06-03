<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace api\v1\User;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Mail\AbstractMail as Mail;

/**
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class UserPasswordController extends \api\v1\BaseController
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\User\UserDao */
    protected $_userDao;
    /** @var \Pley\Dao\User\UserPasswordResetDao */
    protected $_userPassResetDao;
    
    public function __construct(
            Config $config, Mail $mail,
            \Pley\Db\AbstractDatabaseManager $dbManager,
            \Pley\Dao\User\UserDao $userDao,
            \Pley\Dao\User\UserPasswordResetDao $userPassResetDao)
    {
        $this->_config = $config;
        $this->_mail   = $mail;
        
        $this->_dbManager = $dbManager;

        $this->_userDao          = $userDao;
        $this->_userPassResetDao = $userPassResetDao;
    }
    
    // PUT /user/password/change
    public function change()
    {
        // Change password is for user that knows their password and just wants to change it,
        // as such, they have to be currently logged in
        $user = $this->_checkAuthenticated();
        
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();
        $json = \Input::json()->all();
        
        $this->_validatePasswordChange($json);
        $this->_passwordChangeDelegate($user, $json);
        
        return \Response::json(['success' => true]);
    }
    
    // POST /user/password/reset
    public function resetRequest()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        
        $json = \Input::json()->all();
        
        $rules = ['email' => 'required|email'];
        \ValidationHelper::validate($json, $rules);
        
        $user = $this->_userDao->findByEmail($json['email']);
        \ValidationHelper::entityExist($user, \Pley\Entity\User\User::class);
        
        // Finding if there is an existing non-redeemed entry
        $passwordReset = $this->_userPassResetDao->findByUser($user->getId());
        
        // If there is one, we need to make sure that the request limit is not met, to avoid someone
        // from attacking our servers to spam our users
        if (!empty($passwordReset)) {
            $retryLimit = $this->_config->get('mail.resendLimit.general');
            if ($passwordReset->getRequestCount() >= $retryLimit) {
                throw new \Pley\Exception\Mail\MailRetryLimitException(
                    \Pley\Enum\Mail\MailTemplateEnum::PASSWORD_RESET, $retryLimit
                );
            }
            
            // Since the limit has not been met, we need to increase the request count
            $passwordReset->increaseRequestCount();
            
        // Else, the reset token doesn't exist yet, so we need to create it
        } else {
            $passwordReset = \Pley\Entity\User\UserPasswordReset::withNew(
                \Pley\Util\Token::base36(), $user->getId()
            );
        }
        
        $this->_userPassResetDao->save($passwordReset);
        
        $this->_sendPasswordResetEmail($user, $passwordReset);
        
        return \Response::json(['success' => true]);
    }
    
    // GET /user/password/reset/{$token}
    public function checkToken($token)
    {
        \RequestHelper::checkGetRequest();
        
        $passwordReset = $this->_userPassResetDao->find($token);
        \ValidationHelper::entityExist($passwordReset, \Pley\Entity\User\UserPasswordReset::class);
        
        $user = $this->_userDao->find($passwordReset->getUserId());
        
        if ($passwordReset->isRedeemed()) {
            throw new \Pley\Exception\User\PasswordTokenRedeemedException($user, $passwordReset);
        }
        
        return \Response::json(['success' => true]);
    }
    
    // PUT /user/password/reset/{$token}
    public function resetRedeem($token)
    {
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();
        
        $json = \Input::json()->all();
        
        $this->_validatePasswordChange($json);
        
        $passwordReset = $this->_userPassResetDao->find($token);
        \ValidationHelper::entityExist($passwordReset, \Pley\Entity\User\UserPasswordReset::class);
        
        $user = $this->_userDao->find($passwordReset->getUserId());
        
        if ($passwordReset->isRedeemed()) {
            throw new \Pley\Exception\User\PasswordTokenRedeemedException($user, $passwordReset);
        }
        
        $that = $this;
        $this->_dbManager->transaction(function() use ($that, $user, $passwordReset, $json) {
            $that->_passwordChangeDelegate($user, $json);
            
            $passwordReset->setIsRedeemed();
            $that->_userPassResetDao->save($passwordReset);
        });
        
        return \Response::json(['success' => true]);
    }
    
    /**
     * Helper method to validate a change of password input
     * @param array $requestDataMap
     */
    private function _validatePasswordChange(array $requestDataMap)
    {
        $rules = [
            'password'        => 'required',
            'passwordConfirm' => 'required|same:password'
        ];
        \ValidationHelper::validate($requestDataMap, $rules);
    }
    
    /**
     * Helper method to change the user's password
     * @param \Pley\Entity\User\User $user
     * @param array                              $requestDataMap
     */
    private function _passwordChangeDelegate(\Pley\Entity\User\User $user, array $requestDataMap)
    {
        $passwordHash = \Hash::make($requestDataMap['password']);
        $user->setPassword($passwordHash);
        $this->_userDao->save($user);
       
        $this->_sendPasswordChangeEmail($user);
    }
    
    /**
    * Send changed password to user
    * 
    * @param \Pley\Entity\User\User $user
    */
    private function _sendPasswordChangeEmail(\Pley\Entity\User\User $user)
    {
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($user);

        $mailUserTo = \Pley\Mail\MailUser::withUser($user);

        $this->_mail->send(\Pley\Enum\Mail\MailTemplateEnum::PASSWORD_CHANGE, $mailTagCollection, $mailUserTo);
    }

    /**
     * Helper method to send the Password Reset email to the user
     * @param \Pley\Entity\User\User              $user
     * @param \Pley\Entity\User\UserPasswordReset $userPassReset
     */
    private function _sendPasswordResetEmail(
            \Pley\Entity\User\User $user, \Pley\Entity\User\UserPasswordReset $userPassReset)
    {
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($user);
        $mailTagCollection->addEntity($userPassReset);

        $mailUserTo = \Pley\Mail\MailUser::withUser($user);

        $this->_mail->send(\Pley\Enum\Mail\MailTemplateEnum::PASSWORD_RESET, $mailTagCollection, $mailUserTo);
        
    }
}
