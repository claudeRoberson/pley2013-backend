<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Http\Response\Formatter\User;

use \Pley\Http\Response\Formatter\FormatterInterface;
use \Pley\Repository\User\UserRepository as UserRepo;
/**
 * The <kbd>UserFormatter</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package 
 * @subpackage
 */
class UserFormatter implements FormatterInterface
{
    
    protected $_userRepo;
    
    public function __construct(
            UserRepo $userRepo)
    {
        $this->_userRepo           = $userRepo;
    }
    
    /**
     * Takes a <kbd>UserFlags</kbd> entity object and returns a formatted array map that can be used
     * for JSON responses.
     * @param \Pley\Entity\User\User $user
     * @return array
     */
    public function format($user)
    {
        
        $arrayResponse = [
            'firstName'               => $user->getFirstName(),
            'lastName'                => $user->getLastName(),
            'email'                   => $user->getEmail(),
        ];
        return $arrayResponse;
    }
}
