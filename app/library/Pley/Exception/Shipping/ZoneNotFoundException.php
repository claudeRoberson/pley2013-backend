<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Exception\Shipping;

use Pley\Entity\User\UserAddress;
use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>ZoneNotFoundException</kbd> represents the exception raised when trying to
 * get a label and the label service is unavailable.
 *
 * @author Vsevolod Yatsuk (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Shipping.Exception
 * @subpackage Exception
 */
class ZoneNotFoundException extends \RuntimeException implements ExceptionInterface
{
    public function __construct($countryCode, \Exception $previous = null)
    {
        $message = sprintf('Sorry, but we cannot ship to specified country: %s', $countryCode);
        parent::__construct($message, ExceptionCode::SHIPPING_ZONE_NOT_FOUND, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}
