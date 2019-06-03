<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Exception\Coupon;

use Pley\Exception\ExceptionCode;
use Pley\Http\Response\ExceptionInterface;
use Pley\Http\Response\ResponseCode;

/**
 * The <kbd>CouponNotFoundException</kbd> represents the exception raised when trying
 * to load coupon which does not exist.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Coupon.Exception
 * @subpackage Exception
 */
class CouponNotFoundException extends \RuntimeException implements ExceptionInterface
{
    public function __construct($couponCode, \Pley\Entity\User\User $user, \Exception $previous = null)
    {
        $message = json_encode([
            'couponCodeNotFound' => $couponCode,
            'userId' => $user->getId()
        ]);
        parent::__construct($message, ExceptionCode::COUPON_NOT_FOUND, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_CONFLICT;
    }
}
