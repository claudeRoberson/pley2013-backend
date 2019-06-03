<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Exception\Shipping;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>LabelServiceUnavailableException</kbd> represents the exception raised when trying to
 * get a label and the label service is unavailable.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping.Exception
 * @subpackage Exception
 */
class LabelServiceUnavailableException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(\Exception $previous = null)
    {
        $message = 'Label Service is Unavailable.';
        parent::__construct($message, ExceptionCode::SHIPPING_LABEL_SERVICE_UNAVAILABLE, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_SERVICE_UNAVAILABLE;
    }
}
