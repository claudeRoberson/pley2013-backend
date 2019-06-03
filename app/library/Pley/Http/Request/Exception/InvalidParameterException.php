<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Http\Request\Exception;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>InvalidParameterException</kbd> is thrown when bad request parameters are sent. (i.e.
 * an integer is requested and a string is supplied)
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Http.Request.Exception
 * @subpackage Exception
 */
class InvalidParameterException extends \RuntimeException implements ExceptionInterface
{   
    /**
     * Creates a new Exception for an invalid parameter sent.
     * @param string $paramName
     * @param string $paramValue
     * @param \Exception $previous
     */
    public function __construct($errDataMap, \Exception $previous = null)
    {
        $jsonErrData = json_encode($errDataMap);
        
        $message = 'Invalid Request Parameter ' . $jsonErrData;
        parent::__construct($message, ExceptionCode::REQUEST_INVALID_PARAMETER, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
    
}