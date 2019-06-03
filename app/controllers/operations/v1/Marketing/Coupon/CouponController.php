<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace operations\v1\Marketing\Coupon;

/**
 * The <kbd>CouponController</kbd> responsible on making CRUD operations on a coupon entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class CouponController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Repository\Coupon\CouponRepository */
    protected $_couponRepository;
    /** @var \Pley\Dao\User\UserCouponRedemptionDao */
    protected $_userCouponRedemptionDao;

    public function __construct(
        \Pley\Repository\Coupon\CouponRepository $couponRepository,
        \Pley\Dao\User\UserCouponRedemptionDao $userCouponRedemptionDao
    )
    {
        parent::__construct();
        $this->_couponRepository        = $couponRepository;
        $this->_userCouponRedemptionDao = $userCouponRedemptionDao;
    }

    // POST /marketing/coupon
    public function create()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $coupon = new \Pley\Entity\Coupon\Coupon();
        $coupon->fill(\Input::json('coupon'));

        $this->_couponRepository->save($coupon);
        return \Response::json($coupon, 201);
    }

    // GET /marketing/coupon
    public function get($id)
    {
        \RequestHelper::checkGetRequest();
        $coupon = $this->_couponRepository->find($id);
        \ValidationHelper::entityExist($coupon, \Pley\Entity\Coupon\Coupon::class);
        $response = $coupon->toArray();
        $response['redemptionsCount'] = $this->_userCouponRedemptionDao->getRedemptionsCount($coupon);
        return \Response::json($response);
    }

    // PUT /marketing/coupon/{id}
    public function update($id)
    {
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();

        $coupon = $this->_couponRepository->find($id);
        \ValidationHelper::entityExist($coupon, \Pley\Entity\Coupon\Coupon::class);

        $coupon->fill(\Input::json('coupon'));
        $this->_couponRepository->save($coupon);

        return \Response::json($coupon);
    }

    // DELETE /marketing/coupon/{id}
    public function delete($id)
    {
        $coupon = $this->_couponRepository->find($id);
        return \Response::json($coupon);
    }
}