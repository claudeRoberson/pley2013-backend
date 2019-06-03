<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Exception\Coupon;

use Pley\Exception\ExceptionCode;
use Pley\Http\Response\ExceptionInterface;
use Pley\Http\Response\ResponseCode;

/**
 * The <kbd>CouponMaxUsagesExceededException</kbd> represents the exception raised when trying
 * to redeem a coupon which redemptions per user sum has exceeded the usagesPerUser limit set.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Coupon.Exception
 * @subpackage Exception
 */
class CouponMaxUsagesPerUserExceededException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
        \Pley\Entity\Coupon\Coupon $coupon,
        \Pley\Entity\User\User $user,
        \Exception $previous = null
    )
    {
        $message = json_encode([
            'couponId' => $coupon->getId(),
            'userId' => $user->getId()
        ]);
        parent::__construct($message, ExceptionCode::COUPON_MAX_USAGES_PER_USER_EXCEEDED, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_CONFLICT;
    }
}
