<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Exception\Payment;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;
use \Pley\Http\Response\SeparateExceptionLogInterface;

/**
 * The <kbd>PaymentMethodExpiredException</kbd> is thrown when trying to add/update a payment
 * method with expired data, or a payment method has gone expired.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Payment
 * @subpackage Exception
 */
class PaymentMethodExpiredException extends \RuntimeException 
        implements ExceptionInterface, SeparateExceptionLogInterface
{
    use \Pley\Http\Response\Traits\PaymentSeparateExceptionLogTrait;
    
    public function __construct(
            \Pley\Entity\User\User $user, 
            \Pley\Entity\Payment\UserPaymentMethod $userPaymentMethod = null,
            \Exception $previous = null)
    {
        $jsonArray = ['userId' => $user->getId()];
        if (isset($userPaymentMethod)) {
            $jsonArray['userPaymentMethodId'] = $userPaymentMethod->getId();
        }
        
        $message = json_encode($jsonArray);
        parent::__construct($message, ExceptionCode::PAYMENT_METHOD_EXPIRED, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}
