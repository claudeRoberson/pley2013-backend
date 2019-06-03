<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Exception\Auth;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\OneLineExceptionInterface;
use \Pley\Http\Response\ResponseCode;
use \Pley\Http\Response\SeparateExceptionLogInterface;
use \Pley\Http\Response\Traits\AuthSeparateExceptionLogTrait;

/**
 * The <kbd>NotAuthenticatedException</kbd> represents the exception raised when trying to use a
 * controller that requires an authenticaticated user and the session has not been set or is no
 * longer valid.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Auth.Exception
 * @subpackage Exception
 */
class NotAuthenticatedException extends \RuntimeException 
        implements ExceptionInterface, OneLineExceptionInterface, SeparateExceptionLogInterface
{
    use AuthSeparateExceptionLogTrait;
    
    public function __construct(\Exception $previous = null)
    {
        $message = 'User is not authenticated (invalid session).';
        parent::__construct($message, ExceptionCode::AUTH_NOT_AUTHENTICATED, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_UNAUTHORIZED;
    }
    
    /**
     * Return the one line message from the exception including where it happened and the exception
     * associated to it.
     * @return string
     */
    public function getOneLineMessage()
    {
        $trace = $this->getTrace();
        
        // This Exception is called as part of the BaseController classes so to allow for a one
        // line exception, we need to retrieve the implementation class so the line is more meaningful
        $callerFile = $trace[0]['file'];
        $callerLine = $trace[0]['line'];
        
        return sprintf('%s: \'%s\' in %s:%d',
            get_class($this),    // Class Name
            $this->getMessage(),
            $callerFile,
            $callerLine
        );
    }
}
