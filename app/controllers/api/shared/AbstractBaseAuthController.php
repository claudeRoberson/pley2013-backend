<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace api\shared;

/**
 * The <kbd>AbstractBaseAuthController</kbd> is to be used instead of the one provided by Laravel because we
 * do not need to initialize the <kbd>setupLayout</kbd> method.
 * <p>At the same time it also provides a base foundation for all versioned controllers in case
 * we need to do something in particular for all of them.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package api.shared
 * @subpackage shared
 */
class AbstractBaseAuthController extends AbstractBaseController
{
    /**
     * The User entity object after authentication is passed
     * @var \Pley\Entity\User\User
     */
    protected $_user;
    
    public function __construct()
    {   
        if (!\Auth::check()) {
            throw new \Pley\Exception\Auth\NotAuthenticatedException();
        }
        
        /* @var $userDao \Pley\Dao\User\UserDao */
        $userDao     = \App::make('\Pley\Dao\User\UserDao');
        $this->_user = $userDao->find(\Auth::id());
    }
}
