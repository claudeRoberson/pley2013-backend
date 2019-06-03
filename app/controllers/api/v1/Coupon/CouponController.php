<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace api\v1\Coupon;

/**
 * The <kbd>CouponController</kbd>
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */

class CouponController extends \api\v1\BaseController
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

    // GET /coupon/code/{code}
    public function getCodeInfo($code, $country = null)
    {
        \RequestHelper::checkGetRequest();

        $coupon = $this->_couponManager->getByCode($code);

        \ValidationHelper::entityExist($coupon, \Pley\Entity\Coupon\Coupon::class);

        $user = $this->_getLoggedUser();

        if ($user) {
            $country = $user->getCountry();
        }

        $isValid = true;
        $validationError = null;

        if(!$coupon->isEnabled()){
            $isValid = false;
            $validationError = 'Coupon is disabled.';
        }
        if($coupon->isExpired()){
            $isValid = false;
            $validationError = 'Coupon is expired.';
        }
        if($this->_couponManager->isMaxUsagesExceeded($coupon)){
            $isValid = false;
            $validationError = 'Coupon max usages exceeded.';
        }

        $response = [
            'coupon' => [
                'code' => $coupon->getCode(),
                'type' => $coupon->getType(),
                'discountAmount' => $this->_priceManager->toCountryCurrency($coupon->getDiscountAmount(), $country),
                'discountCurrencyCode' => $this->_priceManager->getCountryCurrencyCode($country),
                'discountCurrencySign'=> $this->_priceManager->getCountryCurrencySign($country),
                'subscriptionId' => $coupon->getSubscriptionId(),
                'minBoxes' => $coupon->getMinBoxes(),
                'title' => $coupon->getTitle(),
                'subtitle'=> $coupon->getSubtitle(),
                'isValid' => $isValid,
                'validationError' => $validationError
            ]
        ];

        return \Response::json($response);
    }

    // GET /coupon/code/{code}/{countryCode}
    public function getCodeInfoByCountry($code, $countryCode)
    {
        return $this->getCodeInfo($code, $countryCode);
    }
}
