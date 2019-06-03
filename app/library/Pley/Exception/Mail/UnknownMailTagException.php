<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Exception\Mail;

use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>UnknownMailTagException</kbd> is thrown when an entity object is added to the Mail Tag
 * Collection but it is an unrecognized object and thus cannot be parametrized for injection on
 * email templates.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package PleyWorld.Exception.Mail
 * @subpackage Exception
 */
class UnknownMailTagException extends \Exception implements ExceptionInterface
{
    public function __construct($classNamespace, \Exception $previous = null)
    {
        $message = "Class [{$classNamespace}] not detected as Mail Tag (hint: check mailTemplate config).";
        parent::__construct($message, 0, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
