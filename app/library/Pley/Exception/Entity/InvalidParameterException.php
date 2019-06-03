<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Exception\Entity;

use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>InvalidParameterException</kbd> is thrown when bad parameters is sent to a method.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Entity
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
    public function __construct($paramName, \Exception $previous = null)
    {
        $message = 'Invalid Method Parameter [`' . $paramName . '`]';
        parent::__construct($message, 0, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}