<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Exception\Payment;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>PaymentMethodExistsException</kbd> is thrown when trying to add an existing Payment Method.
 * 
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Payment
 * @subpackage Exception
 */
class PaymentMethodExistsException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(\Pley\Entity\User\User $user, \Exception $previous = null)
    {
        $message = json_encode(['userId' => $user->getId()]);
        parent::__construct($message, ExceptionCode::PAYMENT_METHOD_EXISTS, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}
