<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Exception\Payment;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;
use \Pley\Http\Response\SeparateExceptionLogInterface;

/**
 * The <kbd>PaymentMethodZipException</kbd> is thrown when trying to create a credit card but the
 * billing address Zip code does not match what the card's.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Payment
 * @subpackage Exception
 */
class PaymentMethodZipException extends \RuntimeException 
        implements ExceptionInterface, SeparateExceptionLogInterface
{
    use \Pley\Http\Response\Traits\PaymentSeparateExceptionLogTrait;
    
    public function __construct(\Pley\Entity\User\User $user, \Exception $previous = null)
    {
        $message = json_encode(['userId' => $user->getId()]);
        parent::__construct($message, ExceptionCode::PAYMENT_METHOD_BAD_ZIP, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}
