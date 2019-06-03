<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Exception\User;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>PaymentMethodDeleteException</kbd> is triggered when trying to remove a payment method
 * that is active (i.e. The default payment method).
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.User
 * @subpackage Exception
 */
class PaymentMethodDeleteException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
            \Pley\Entity\User\User $user, \Pley\Entity\Payment\UserPaymentMethod $paymentMethod, \Exception $previous = null)
    {
        $message = json_encode(['userId' => $user->getId(), 'userPaymentMethodId' => $paymentMethod->getId()]);
        parent::__construct($message, ExceptionCode::USER_PAYMENT_METHOD_DELETE, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}
