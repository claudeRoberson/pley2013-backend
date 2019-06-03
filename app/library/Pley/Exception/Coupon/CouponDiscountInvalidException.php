<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Exception\Coupon;

use Pley\Exception\ExceptionCode;
use Pley\Http\Response\ExceptionInterface;
use Pley\Http\Response\ResponseCode;

/**
 * The <kbd>CouponDiscountInvalidException</kbd> represents the exception raised when trying
 * to redeem a coupon, which calculated discount is more than a transaction base amount.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Coupon.Exception
 * @subpackage Exception
 */
class CouponDiscountInvalidException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(\Pley\Entity\Coupon\Coupon $coupon, $type, \Exception $previous = null)
    {
        $message = json_encode([
                'couponId' => $coupon->getId(),
                'type' => $type]
        );
        parent::__construct($message, ExceptionCode::COUPON_DISCOUNT_INVALID, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_CONFLICT;
    }
}
