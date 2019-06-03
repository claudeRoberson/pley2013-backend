<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace operations\v1;

use \Pley\Exception\Auth\NotAuthenticatedException;

/**
 * The <kbd>BaseAuthController</kbd> Provides common functionality for controllers requiring 
 * an authentication layer.
 * <p>The constructor provides such validation and exposes a protected variable to obtain the
 * ID of the backend user that is authenticated.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @see http://laravel.com/docs/controllers#resource-controllers
 */
class BaseAuthController extends \BaseController
{
    /**
     * The backend user id retrieved after authentication is passed
     * @var int
     */
    protected $_opsUserId;
    /**
     * The backend user object retrieved after authentication is passed
     * @var \Pley\Entity\Operations\OperationsUser
     */
    protected $_opsUser;
    
    public function __construct()
    {
        if (!\Auth::check()) {
            throw new NotAuthenticatedException();
        }
        
        $this->_opsUserId = \Auth::id();
        
        /* @var $opsUserDao \Pley\Dao\Operations\OperationsUserDao */
        $opsUserDao     = \App::make('\Pley\Dao\Operations\OperationsUserDao');
        $this->_opsUser = $opsUserDao->find(\Auth::id());
    }
}
