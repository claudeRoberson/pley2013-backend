<?php

namespace operations\v1\Marketing\Coupon;

/** @copyright Pley (c) 2017, All Rights Reserved */

/**
 * The <kbd>CouponGridController</kbd> responsible for querying data and performing
 * filtering operations on coupons grid view
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */

class CouponGridController extends \operations\v1\BaseAuthController
{

    /** @var \Pley\Repository\Coupon\CouponRepository */
    protected $couponRepository;

    public function __construct(
        \Pley\Repository\Coupon\CouponRepository $couponRepository
    )
    {
        parent::__construct();
        $this->couponRepository = $couponRepository;
    }

    // GET /marketing/coupon/index
    public function index()
    {
        \RequestHelper::checkGetRequest();

        $coupons = $this->couponRepository->all();
        $response = [];
        foreach ($coupons as $coupon) {
            $response[] = $coupon->toArray();
        }
        return \Response::json($response);
    }

    // GET /marketing/coupon/search/{term}
    public function search($term)
    {
        \RequestHelper::checkGetRequest();

        $coupons = $this->couponRepository->findByTerm($term);
        $response = [];
        foreach ($coupons as $coupon) {
            $response[] = $coupon->toArray();
        }
        return \Response::json($response);
    }
}