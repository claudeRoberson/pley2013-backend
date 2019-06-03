<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Http\Request\Exception;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>InvalidFormElelemtException</kbd> is thrown when bad request form elelemnt are sent. (i.e.
 * an integer is requested and a string is supplied)
 *
 * @author Igor Shvaratsev (igor.shvartsev@gmail.com)
 * @version 1.0
 * @package Pley.Http.Request.Exception
 * @subpackage Exception
 */
class InvalidFormElementException extends \RuntimeException implements ExceptionInterface
{
    /** @var string */
    protected $_elemName;
    /** @var mixed */
    protected $_elemValue;
    
    /**
     * Creates a new Exception for an invalid form element sent.
     * @param string $elemName
     * @param string $elemValue
     * @param \Exception $previous
     */
    public function __construct($elemName, $elemValue, \Exception $previous = null)
    {
        $this->_elemName  = $elemName;
        $this->_elemValue = $elemValue;
        
        $elemName = ucwords(str_replace(['-','_'],' ',$elemName));
        
        if (empty($elemValue)) {
            $message = 'Missing  ' . $elemName;
        }  else {
            $message = 'Invalid  ' . $elemName;
        }
        
        parent::__construct($message, ExceptionCode::REQUEST_INVALID_PARAMETER, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
    
    /**
     * Returns the parameter name related to this exception.
     * @return string
     */
    public function getParamName()
    {
        return $this->_elemName;
    }
    
    /**
     * Returns the parameter value related to this exception.
     * @return mixed
     */
    public function getParamValue()
    {
        return $this->_elemValue;
    }
    
}