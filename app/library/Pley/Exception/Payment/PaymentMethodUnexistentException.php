<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Exception\Payment;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;
use \Pley\Http\Response\SeparateExceptionLogInterface;

/**
 * The <kbd>PaymentMethodUnexistentException</kbd> is thrown when trying to get an Payment Method that 
 * does not exist in the user's account.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Payment
 * @subpackage Exception
 */
class PaymentMethodUnexistentException extends \RuntimeException 
        implements ExceptionInterface, SeparateExceptionLogInterface
{
    use \Pley\Http\Response\Traits\PaymentSeparateExceptionLogTrait;
    
    public function __construct(
            \Pley\Entity\User\User $user, 
            \Pley\Entity\Payment\UserPaymentMethod $userPaymentMethod,
            \Exception $previous = null)
    {
        $message = json_encode([
            'userId'              => $user->getId(),
            'userPaymentMethodId' => $userPaymentMethod->getId()
        ]);
        parent::__construct($message, ExceptionCode::PAYMENT_METHOD_DOES_NOT_EXIST, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}
