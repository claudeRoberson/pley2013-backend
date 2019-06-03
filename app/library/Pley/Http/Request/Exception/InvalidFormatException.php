<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Http\Request\Exception;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>InvalidParameterException</kbd> is thrown when a bad request format is sent. (i.e. not
 * JSON body)
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Http.Request.Exception
 * @subpackage Exception
 */
class InvalidFormatException extends \RuntimeException implements ExceptionInterface
{
    /**
     * Creates a new Exception for the giving missing global key.
     * @param string $message
     * @param \Exception $previous
     */
    public function __construct($message, \Exception $previous = null)
    {
        // If a list of string is supplied, merge those strings into a single comma separated string
        if (is_array($message)) {
            $message = implode(',', $message);
        }
        
        $message = 'Invalid Request Format [`' . $message . '`]';
        parent::__construct($message, ExceptionCode::REQUEST_INVALID_FORMAT, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
    
}