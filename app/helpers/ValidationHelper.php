<?php /** @copyright Pley (c) 2015, All Rights Reserved */

use \Pley\Http\Request\Exception\InvalidParameterException;
use \Pley\Repository\Exception\EntityNotFoundException;

/**
 * The <kbd>ValidationHelper</kbd> class provides a simple method to abstract the common validation
 * methods needed on many controllers.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class ValidationHelper
{
    /**
     * Helper method to abstract the common validation methods repeated across controllers.
     * 
     * @param array $input Map with the key/value pairs
     * @param array $rules Map of the Laravel validation rules
     * @throws InvalidParameterException
     */
    public static function validate($input, $rules)
    {
        $validator = \Validator::make($input, $rules);
        if ($validator->fails()) {
            $paramKeyList = $validator->messages()->all(':key');
            
            $errKeyValMap = [];
            foreach ($paramKeyList as $paramKey) {
                // If the key is not set on the input, the entry is missing
                if (!isset($input[$paramKey])) {
                    $errKeyValMap[$paramKey] = '_MISS_NODE_';
                    
                // If the parameter key has a period, it means that it maps to an array entry
                // as such, we need to get the value that all the tree nodes refer to.
                } else if (strpos($paramKey, '.') !== false) {
                    $paramKeyNodeList = explode('.', $paramKey);
                    $paramValue       = $input[$paramKeyNodeList[0]];
                    for ($i = 1; $i < count($paramKeyNodeList); $i++) {
                        $paramValue = $paramValue[$paramKeyNodeList[$i]];
                    }
                    $errKeyValMap[$paramKey] = $paramValue;
                    
                // Otherwise, it is a straight value, so just get it.
                } else {
                    $errKeyValMap[$paramKey] = $input[$paramKey];
                }
            }

            throw new InvalidParameterException($errKeyValMap);
        }
    }
    
    /**
     * Helper method validate the user supplied password against the logged in user.
     * @param string $password
     * @throws InvalidAuthCredentialsException
     */
    public static function validateCredentials(\Pley\Entity\User\User $user, $password)
    {
        $credentials  = ['password' => $password];
        
        /* @var $authProvider \Illuminate\Auth\UserProviderInterface */
        $authProvider = \Auth::getProvider();
        
        $authUser = $authProvider->retrieveById($user->getId());
        $isValid  = $authProvider->validateCredentials($authUser, $credentials);
        
        if (!$isValid) {
            throw new Pley\Exception\Auth\InvalidAuthCredentialsException($user->getId());
        }
    }
    
    /**
     * Helper method to check commond validation against a non-NULL entity object, otherwise throw
     * an exception with the related class associated to the expected entity.
     * @param object $entity
     * @param string $class
     * @throws \Pley\Repository\Exception\EntityNotFoundException
     */
    public static function entityExist($entity, $class)
    {
        if (!isset($entity)) {
            throw new EntityNotFoundException($class);
        }
    }
}
