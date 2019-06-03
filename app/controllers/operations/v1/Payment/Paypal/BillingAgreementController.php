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
class BillingAgreementController extends \operations\v1\BaseAuthController
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
    ) {
        $this->_priceManager = $priceManager;
        $this->_paypalManager = $paypalManager;
        $this->_paymentPlanDao = $paymentPlanDao;
        $this->_vendorPaymentPlanDao = $vendorPaymentPlanDao;
    }

    // GET /payment/paypal/agreements/{{id}}

    /**
     * @return mixed
     */
    public function getBillingAgreementInfo($agreementId)
    {
        \RequestHelper::checkGetRequest();
        $billingAgreement = $this->_paypalManager->getBillingAgreement($agreementId);
        return \Response::json($billingAgreement->toArray());
    }
}