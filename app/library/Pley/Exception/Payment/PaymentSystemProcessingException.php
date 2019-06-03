<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Exception\Payment;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;
use \Pley\Http\Response\SeparateExceptionLogInterface;

/**
 * The <kbd>PaymentMethodProcessingException</kbd> is thrown when there is an issue processing a request.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Payment
 * @subpackage Exception
 */
class PaymentSystemProcessingException extends \RuntimeException 
        implements ExceptionInterface, SeparateExceptionLogInterface
{
    use \Pley\Http\Response\Traits\PaymentSeparateExceptionLogTrait;
    
    public function __construct(\Exception $previous = null)
    {
        $message = 'Problem processing the current request.';
        parent::__construct($message, ExceptionCode::PAYMENT_METHOD_PROCESSING, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
