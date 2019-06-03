<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Exception;

use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>UnsupportedOperationException</kbd> is thrown when an invalid operation is requested.
 * <p>Examples could be, unsupported comparison operation from a configuration file, trying to 
 * request a method that is available through an interface, but the specific implementation forbids
 * the use of such method, etc.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception
 * @subpackage Exception.
 */
class UnsupportedOperationException extends \Exception implements ExceptionInterface
{
    public function __construct($message = null, \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
