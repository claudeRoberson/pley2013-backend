<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Exception;

use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>MissingImplementationException</kbd> is primarily thrown when a method is invoked but
 * has no implementation.
 * <p>This is most probably due to a Interface requirement for a method to be present, however, at
 * the moment when the class was created, there was no need for such method.<br/>
 * However, after sometime, some new functionality required the method and this is the way we can
 * quickly find out that implementation needs to be added.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception
 * @subpackage Exception
 */
class MissingImplementationException  extends \Exception implements ExceptionInterface
{
    public function __construct($methodName, \Exception $previous = null)
    {
        $message = $methodName . ' needs implementaiton.';
        parent::__construct($message, 0, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
