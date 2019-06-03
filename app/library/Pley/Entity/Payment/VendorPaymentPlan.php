<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Payment;

use \Pley\Exception\Entity\ImmutableAttributeException;

/**
 * The <kbd>VendorPaymentPlan</kbd> entity
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Payment
 * @subpackage Entity
 */
class VendorPaymentPlan implements \Pley\Entity\Vendor\VendorPaymentEntityInterface
{
    use \Pley\Entity\Vendor\Payment\VendorPaymentSystemEntityTrait;

    /** @var int */
    protected $_id;
    /** @var int */
    protected $_paymentPlanId;
    /** @var int */
    protected $_shippingZoneId;
    /** @var float */
    protected $_basePrice;
    /** @var float */
    protected $_unitPrice;
    /** @var float */
    protected $_shippingPrice;
    /** @var int */
    protected $_total;
    /** @var string */
    protected $_vPaymentPlanId;


    /**
     * VendorPaymentPlan constructor.
     * @param $id
     * @param $paymentPlanId
     * @param $shippingZoneId
     * @param $basePrice
     * @param $unitPrice
     * @param $shippingPrice
     * @param $total
     * @param $vPaymentSystemId
     * @param $vPaymentPlanId
     */
    public function __construct($id, $paymentPlanId, $shippingZoneId,
                                $basePrice, $unitPrice, $shippingPrice, $total,
                                $vPaymentSystemId, $vPaymentPlanId
    )
    {
        $this->_id               = $id;
        $this->_paymentPlanId    = $paymentPlanId;
        $this->_shippingZoneId   = $shippingZoneId;
        $this->_basePrice        = $basePrice;
        $this->_unitPrice        = $unitPrice;
        $this->_shippingPrice    = $shippingPrice;
        $this->_total            = $total;
        $this->_vPaymentSystemId = $vPaymentSystemId;
        $this->_vPaymentPlanId   = $vPaymentPlanId;
    }

    /** @return int */
    public function getId()
    {
        return $this->_id;
    }

    /** @return int */
    public function setId($id)
    {
        if (isset($this->_id)) {
            throw new ImmutableAttributeException(static::class, '_id');
        }
        $this->_id = $id;
    }

    /** @return int */
    public function getPaymentPlanId()
    {
        return $this->_paymentPlanId;
    }

    /**
     * @return int
     */
    public function getShippingZoneId()
    {
        return $this->_shippingZoneId;
    }

    /**
     * @return float
     */
    public function getBasePrice()
    {
        return $this->_basePrice;
    }
    
    /**
     * @return float
     */
    public function getUnitPrice()
    {
        return $this->_unitPrice;
    }

    /**
     * @return float
     */
    public function getShippingPrice()
    {
        return $this->_shippingPrice;
    }

    /**
     * @return float
     */
    public function getTotal()
    {
        return $this->_total;
    }

    /**
     * @return string
     */
    public function getVPaymentPlanId()
    {
        return $this->_vPaymentPlanId;
    }

    /**
     * @param $vSystemId
     * @param $vPlanId
     */
    public function setVPaymentPlan($vSystemId, $vPlanId)
    {
        $this->_vPaymentSystemId = $vSystemId;
        $this->_vPaymentPlanId   = $vPlanId;
    }
}
