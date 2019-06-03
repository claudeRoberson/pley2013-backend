<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Exception\Coupon;

use Pley\Exception\ExceptionCode;
use Pley\Http\Response\ExceptionInterface;
use Pley\Http\Response\ResponseCode;

/**
 * The <kbd>CouponTypeInvalidException</kbd> represents the exception raised when trying
 * to redeem a coupon, which type ID is not represented by CouponTypeEnum class.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Coupon.Exception
 * @subpackage Exception
 */
class CouponTypeInvalidException extends \Exception implements ExceptionInterface
{
    public function __construct(\Pley\Entity\Coupon\Coupon $coupon, $type, \Exception $previous = null)
    {
        $message = json_encode([
                'couponId' => $coupon->getId(),
                'invalidType' => $type]
        );
        parent::__construct($message, ExceptionCode::COUPON_TYPE_INVALID, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
