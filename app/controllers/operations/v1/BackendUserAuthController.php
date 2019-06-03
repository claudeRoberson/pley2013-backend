<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace operations\v1;

use \Pley\Exception\Auth\InvalidAuthCredentialsException;
use \Pley\Http\Session\Session as PleySession;

/**
 * The <kbd>BackendUserAuthController</kbd> takes care of authenticating the backend user for
 * login and logout.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @package api.v1
 */
class BackendUserAuthController extends \BaseController
{
    public function login()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        
        // If the user is not already logged in, then check request and credentials
        if (!\Auth::check()) {
            
            // Getting the JSON input as an assoc array
            $json = \Input::json()->all();
            
            $validationRules = [
                'email'    => 'required',
                'password' => 'required',
            ];
            \ValidationHelper::validate($json, $validationRules);
            
            if (!\Auth::attempt($json)) {
                $userName = $json['email'];
                unset($json['email']);
                $json['username'] = $userName;
                if (!\Auth::attempt($json)) {
                    throw new InvalidAuthCredentialsException($userName);
                }
            }
            
            // Flagging the session as not fresh, so that it will be written by the application
            // stack (look at \Pley\Laravel\Foundation\Session\Middleware)
            // Only Backend User Login and Backend User Registration should do this.
            \Session::set(PleySession::IS_FRESH_KEY, false);
            
            // Flagging this session as an admin session
            \Session::set(PleySession::IS_ADMIN_KEY, true);
        }
        
        /* @var $opsUserDao \Pley\Dao\Operations\OperationsUserDao */
        $opsUserDao = \App::make('\Pley\Dao\Operations\OperationsUserDao');
        $opsUser    = $opsUserDao->find(\Auth::id());
        
        $jsonResponse = $this->_successJson($opsUser);
        return \ResponseHelper::setSessionHeader($jsonResponse, 'tbases');
    }
    
    public function logout()
    {
        \Auth::logout();
        return $this->_successJson();
    }
    
    /** @return  \Illuminate\Http\JsonResponse */
    private function _successJson(\Pley\Entity\Operations\OperationsUser $opUser)
    {
        $arrayResponse = ['status' => 'success'];
        if (isset($opUser)) {
            $arrayResponse['user'] = [
                'id'          => $opUser->getId(),
                'username'    => $opUser->getUsername(),
                'firstName'   => $opUser->getFirstName(),
                'lastName'    => $opUser->getLastName(),
                'email'       => $opUser->getEmail(),
                'role'        => $opUser->getRole(),
                'warehouseId' => $opUser->getWarehouseId(),
            ];
        }
        return \Response::json($arrayResponse);
    }
    
}
