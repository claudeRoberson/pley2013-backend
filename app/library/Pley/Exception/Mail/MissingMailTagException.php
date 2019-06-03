<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Exception\Mail;

use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>MissingMailTagException</kbd> is thrown when a tag is required for an Email Template,
 * but no entity was supplied to cover it.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package PleyWorld.Exception.Mail
 * @subpackage Exception
 */
class MissingMailTagException extends \Exception implements ExceptionInterface
{
    public function __construct($templateId, $tagName, \Exception $previous = null)
    {
        $message = "Tag name [{$tagName}] missing for email template [{$templateId}].";
        parent::__construct($message, 0, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
