<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace api\shared;

/**
 * The <kbd>AbstractBaseController</kbd> is to be used instead of the one provided by Laravel because we
 * do not need to initialize the <kbd>setupLayout</kbd> method.
 * <p>At the same time it also provides a base foundation for all versioned controllers in case
 * we need to do something in particular for all of them.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package api.shared
 * @subpackage shared
 */
abstract class AbstractBaseController extends \Controller
{   
    /**
     * Checks if the user session is valid, otherwise it throws an Not Authenticated Exception.
     * <p>If all service calls need to be authenticated, extend from <kbd>BaseAuthController</kbd>,
     * otherwise, add this call to the beginning of each service call that needs to pass
     * authentication .</p>
     * 
     * @return \Pley\Entity\User\User
     * @throws \Pley\Auth\Exception\NotAuthenticatedException
     */
    protected function _checkAuthenticated()
    {
        $loggedUser = $this->_getLoggedUser();
        if (is_null($loggedUser)) {
            throw new \Pley\Exception\Auth\NotAuthenticatedException();
        }
        
        return $loggedUser;
    }
    
    /**
     * Checks whether the request includes an authenticated user.
     * @return boolean
     */
    protected function _isUserSession()
    {
        return \Auth::check();
    }
    
    /**
     * Returns the logged <kbd>User</kbd> entity, or <kbd>null</kbd> if no user is logged-in in this
     * request.
     * 
     * @return \Pley\Entity\User\User
     */
    protected function _getLoggedUser()
    {
        if (!\Auth::check()) {
            return null;
        }
        
        /* @var $userDao \Pley\Dao\User\UserDao */
        $userDao = \App::make('\Pley\Dao\User\UserDao');
        return $userDao->find(\Auth::id());
    }
    
}