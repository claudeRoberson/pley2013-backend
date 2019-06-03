<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Exception\Mail;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>MailRetryLimitException</kbd> represents the exception raised when trying to retry
 * sending an email but the limit for that specific email has been reached.
 * <p>This is to prevent malicious users to use our system as a way to spam out clients.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Mail.Exception
 * @subpackage Exception
 */
class MailRetryLimitException extends \RuntimeException implements ExceptionInterface
{
    public function __construct($templateId, $limit, \Exception $previous = null)
    {
        $message = 'Cannot resend email template `'. $templateId .'` more than  ' . $limit . ' times.';
        parent::__construct($message, ExceptionCode::MAIL_RESEND_LIMIT, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}
