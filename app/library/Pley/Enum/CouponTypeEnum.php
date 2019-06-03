<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Enum;

/**
 * The <kbd>CouponTypeEnum</kbd> represents type of coupon discount.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
class CouponTypeEnum extends AbstractEnum
{
    const FIXED         = 1;
    const PERCENTAGE    = 2;
    const INVITE_REDEEM = 3;

    /**
     * Maps and returns the string value for a given coupon type ID.
     * @param int $couponTypeId
     * @return int
     * @throws \UnexpectedValueException If the coupon id is not supported.
     */
    public static function asString($couponTypeId)
    {
        switch ($couponTypeId) {
            case self::FIXED :
                return 'FIXED';
            case self::PERCENTAGE :
                return 'PERCENTAGE';
            case self::INVITE_REDEEM :
                return 'INVITE REDEEM';
            default :
                throw new \UnexpectedValueException("Coupon type ID `{$couponTypeId}` not supported");
        }
    }
}
