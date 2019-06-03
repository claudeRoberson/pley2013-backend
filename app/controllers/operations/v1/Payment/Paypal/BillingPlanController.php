<?php

namespace operations\v1\Payment\Paypal;

use Pley\Billing\PaypalManager;
use Pley\Price\PriceManager;
use Pley;

/** @copyright Pley (c) 2017, All Rights Reserved */

/**
 * The <kbd>BillingPlanController</kbd>
 *
 * @author Vsevolod Yatsiuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class BillingPlanController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Coupon\CouponManager */
    protected $_priceManager;

    /**
     * @var PaypalManager
     */
    protected $_paypalManager;

    /**
     * @var Pley\Dao\Payment\PaymentPlanDao
     */
    protected $_paymentPlanDao;

    /**
     * @var Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao
     */
    protected $_vendorPaymentPlanDao;


    /**
     * PaypalController constructor.
     * @param PriceManager $priceManager
     * @param PaypalManager $paypalManager
     * @param Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao
     * @param Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao
     */
    public function __construct(
        PriceManager $priceManager,
        PaypalManager $paypalManager,
        Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
        Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao
    )
    {
        $this->_priceManager = $priceManager;
        $this->_paypalManager = $paypalManager;
        $this->_paymentPlanDao = $paymentPlanDao;
        $this->_vendorPaymentPlanDao = $vendorPaymentPlanDao;
    }

    // POST /billing/paypal/billing-plans

    /**
     * @return mixed
     */
    public function createBillingPlan()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        $json = \Input::json()->all();

        $rules = [
            'paymentPlanId' => 'required|integer',
            'name' => 'required|string',
            'description' => 'required|string',
            'frequency' => 'required|string',
            'frequencyInterval' => 'required|integer',
            'total' => 'required|numeric',
        ];
        \ValidationHelper::validate($json, $rules);
        $paymentPlan = $this->_paymentPlanDao->find($json['paymentPlanId']);
        $paypalBillingPlan = $this->_paypalManager->createBillingPlan($json, true);

        $this->_paypalManager->upsertVendorPaymentPlan($json, $paymentPlan, $paypalBillingPlan);

        return \Response::json($paypalBillingPlan->toArray());

    }

    // GET /payment/paypal/billing-plans

    /**
     * @return mixed
     */
    public function listBillingPlans()
    {
        \RequestHelper::checkGetRequest();
        $billingPlans = $this->_paypalManager->listBillingPlans(Pley\Enum\Paypal\BillingPlanStatusEnum::ACTIVE);
        return \Response::json($billingPlans->toArray());
    }

    // GET /payment/paypal/billing-plans/{{id}}

    /**
     * @return mixed
     */
    public function getBillingPlanInfo($billingPlanId)
    {
        \RequestHelper::checkGetRequest();
        $billingPlan = $this->_paypalManager->getBillingPlan($billingPlanId);
        return \Response::json($billingPlan->toArray());
    }
}