<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\NatGeo\Exception;

/**
 * The <kbd>AbstractBaseException</kbd> class contains the base behavior of most common exceptions
 * thrown by the NatGeo Requests.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.NatGeo.Exception
 * @subpackage NatGeo
 * @subpackage Exception
 */
abstract class AbstractBaseException extends \RuntimeException
{
    public function __construct($dataMap, $previous = null)
    {
        $message = $this->_getMessageString();
        if (!empty($dataMap)) {
            $message .= ' ' . json_encode($dataMap);
        }
        
        parent::__construct($message, $this->_getExceptionCode(), $previous);
    }
    
    /**
     * Return the message that will be thrown with this exception
     * @return string
     */
    protected abstract function _getMessageString();
    
    /**
     * Return the ExceptionCode that is associated with this Exception
     * @return int
     */
    protected abstract function _getExceptionCode();
}
