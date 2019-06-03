<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Exception\Mail;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>UnknownMailTemplateException</kbd> represents the exception raised when trying to use an
 * email template and the id requested is not known.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Mail.Exception
 * @subpackage Exception
 */
class UnknownMailTemplateException extends \Exception implements ExceptionInterface
{
    public function __construct($templateId, \Exception $previous = null)
    {
        $message = 'Unknown email template with id (`'. $templateId .'`).';
        parent::__construct($message, ExceptionCode::MAIL_UNKNOWN_TEMPLATE, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
