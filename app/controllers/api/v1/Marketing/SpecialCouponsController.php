<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace api\v1\Marketing;

/**
 * The <kbd>SpecialCouponsController</kbd>
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */

class SpecialCouponsController extends \api\v1\BaseController
{
    /** @var \Pley\Coupon\CouponManager */
    protected $_couponManager;
    /** @var \Pley\Coupon\CouponManager */
    protected $_priceManager;

    public function __construct(
        \Pley\Coupon\CouponManager $couponManager,
        \Pley\Price\PriceManager $priceManager
    )
    {
        $this->_couponManager = $couponManager;
        $this->_priceManager = $priceManager;
    }

    // GET /marketing/special-coupons
    public function getSpecialCoupons()
    {
        \RequestHelper::checkGetRequest();

        $response = [
            'coupons' => []
        ];

        $coupons = $this->_couponManager->getSpecialCoupons();

        foreach ($coupons as $coupon) {
            if($this->_couponManager->isMaxUsagesExceeded($coupon)){
                continue;
            }
            $response['coupons'][] = [
                'code' => $coupon->getCode(),
                'type' => $coupon->getType(),
                'discountAmount' => $this->_priceManager->toCountryCurrency($coupon->getDiscountAmount()),
                'discountCurrencyCode' => $this->_priceManager->getCountryCurrencyCode(),
                'discountCurrencySign' => $this->_priceManager->getCountryCurrencySign(),
                'subscriptionId' => $coupon->getSubscriptionId(),
                'expiresAt' => $coupon->getExpiresAt(),
                'minBoxes' => $coupon->getMinBoxes(),
                'title' => $coupon->getTitle(),
                'subtitle' => $coupon->getSubtitle(),
                'isValid' => $coupon->isEnabled() && !$coupon->isExpired(),
            ];
        }
        return \Response::json($response);
    }
}
