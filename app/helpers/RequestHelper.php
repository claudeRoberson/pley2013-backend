<?php /** @copyright Pley (c) 2014, All Rights Reserved */

use \Illuminate\Support\Facades\Request;

use \Pley\Http\Request\Exception\InvalidFormatException;
use \Pley\Http\Request\Exception\InvalidParameterException;

use \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * The <kbd>RequestHelper</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class RequestHelper
{
    /**
     * Checks if the request is a JSON Request, otherwise throws an exception.
     * <p>This helper function is just to reduce the amount of similar calls to check the request
     * content type and throw an exception if it is not a JSON type.</p>
     * 
     * @throws \Pley\Http\Request\Exception\InvalidFormatException
     */
    public static function checkJsonRequest()
    {
        if (!Request::isJson()) {
            throw new InvalidFormatException('Not JSON');
        }
    }
    
    /**
     * Checks if it is a GET Request
     * <p>This helper function is just to reduce the amount of similar calls to check the request
     * method and throw an exception if the expected one.</p>
     * 
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public static function checkGetRequest()
    {
        static::_isMethod('get');
    }
    
    /**
     * Checks if it is a POST Request
     * <p>This helper function is just to reduce the amount of similar calls to check the request
     * method and throw an exception if the expected one.</p>
     * 
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public static function checkPostRequest()
    {
        static::_isMethod('post');
    }
    
    /**
     * Checks if it is a PUT Request
     * <p>This helper function is just to reduce the amount of similar calls to check the request
     * method and throw an exception if the expected one.</p>
     * 
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public static function checkPutRequest()
    {
        static::_isMethod('put');
    }
    
    /**
     * Checks if it is a DELETE Request
     * <p>This helper function is just to reduce the amount of similar calls to check the request
     * method and throw an exception if the expected one.</p>
     * 
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public static function checkDeleteRequest()
    {
        static::_isMethod('delete');
    }
    
    /**
     * Checks if the given variable is an Integer value, otherwise throws an exception.
     * 
     * @param mixed  $var     The variable that holds the value to check
     * @param string $varName Name used if an exception needs to be thrown
     * @throws \Pley\Http\Request\Exception\InvalidParameterException
     */
    public static function checkInteger($var, $varName)
    {
        if (!is_numeric($var) || ((int)$var) != $var) {
            throw new InvalidParameterException([$varName => $var]);
        }
    }
    
    /**
     * Helper function to retrieve the Pagination variables from the request body after validation.
     * <p>Most common way to use is:<br/>
     * <pre>list($page, $count) = RequestHelper::getPagination($jsonInput);</pre>
     * @param array $jsonInput Array must contain a <kbd>`page`</kbd> and a <kbd>`count`</kbd> element.
     * @param int   $defaultPageCount (Optional)<br/>Used in case the supplied count input is invalid
     * @return array First position contains the page start, Second position the page count.
     */
    public static function getPagination($jsonInput, $defaultPageCount = 20)
    {
        $validationRules = [
            'page'  => 'required|integer',
            'count' => 'required|integer',
        ];
        \ValidationHelper::validate($jsonInput, $validationRules);
        
        $page  = $jsonInput['page'] < 0 ? 0 : (int)$jsonInput['page'];
        $count = $jsonInput['count'] < 0 ? $defaultPageCount : (int)$jsonInput['count'];
        
        return [$page, $count];
    }
    
    /**
     * Checks for a specific Request Method, will throw an exception if the request does not match
     * the method requested.
     * @param type $methodName
     * @throws MethodNotAllowedHttpException
     */
    protected static function _isMethod($methodName)
    {
        if (!Request::isMethod($methodName)) {
            throw new MethodNotAllowedHttpException([$methodName]);
        }
    }
}
