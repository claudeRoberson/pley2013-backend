<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Exception\Auth;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\OneLineExceptionInterface;
use \Pley\Http\Response\OneLineExceptionTrait;
use \Pley\Http\Response\ResponseCode;
use \Pley\Http\Response\SeparateExceptionLogInterface;
use \Pley\Http\Response\Traits\AuthSeparateExceptionLogTrait;

/**
 * The <kbd>InvalidAuthCredentialsException</kbd> represents the exception raised when trying to
 * authenticate for a login event and the user credentials are not valid.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Auth.Exception
 * @subpackage Exception
 */
class InvalidAuthCredentialsException extends \RuntimeException 
        implements ExceptionInterface, OneLineExceptionInterface, SeparateExceptionLogInterface
{
    use OneLineExceptionTrait, AuthSeparateExceptionLogTrait;
    
    public function __construct($requestedUser, \Exception $previous = null)
    {
        $message = 'Invalid credentials for requested user (`' . $requestedUser . '`).';
        parent::__construct($message, ExceptionCode::AUTH_INVALID_CREDENTIALS, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}
