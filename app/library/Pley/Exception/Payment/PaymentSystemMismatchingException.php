<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Exception\Payment;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>PaymentSystemMismatchingException</kbd> When the User assigned vendor payment system does
 * not match the implementation of execution.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Payment
 * @subpackage Exception
 */
class PaymentSystemMismatchingException extends \Exception implements ExceptionInterface
{
    public function __construct($vSystemIdA, $vSystemIdB, \Exception $previous = null)
    {
        $message = json_encode(['ID_1' => $vSystemIdA, 'ID_2' => $vSystemIdB]);
        parent::__construct($message, ExceptionCode::PAYMENT_MISMATCHING_SYSTEM, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
