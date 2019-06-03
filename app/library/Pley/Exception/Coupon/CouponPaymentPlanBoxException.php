<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Exception\Coupon;

use Pley\Exception\ExceptionCode;
use Pley\Http\Response\ExceptionInterface;
use Pley\Http\Response\ResponseCode;

/**
 * The <kbd>CouponPaymentPlanBoxException</kbd> represents the exception raised when trying
 * to redeem a coupon which minBoxes greater or equal to payment plan/subscriptions boxes amount.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Coupon.Exception
 * @subpackage Exception
 */
class CouponPaymentPlanBoxException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
        \Pley\Entity\Coupon\Coupon $coupon,
        \Pley\Entity\User\User $user,
        $subscriptionId,
        $paymentPlanId,
        \Exception $previous = null
    ){
        $message = json_encode([
                'couponId'       => $coupon->getId(),
                'userId'         => $user->getId(),
                'subscriptionId' => $subscriptionId,
                'paymentPlanId'  => $paymentPlanId,
            ]);
        parent::__construct($message, ExceptionCode::COUPON_MIN_BOXES_NOT_REACHED, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_CONFLICT;
    }
}
