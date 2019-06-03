<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Entity\Gift;

/** ♰
 * The <kbd>GiftPrice</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class GiftPrice
{
    /** @var int */
    protected $_id;
    /** @var string */
    protected $_title;
    /** @var float */
    protected $_priceTotal;
    /** @var float */
    protected $_priceUnit;
    /** @var int */
    protected $_equivalentPaymentPlanId;
    
    /** @var \Pley\Entity\Payment\PaymentPlan */
    private $_equivalementPaymentPlan;
    
    public function __construct($id, $title, $priceTotal, $priceUnit, $equivalentPaymentPlanId)
    {
        $this->_id                      = $id;
        $this->_title                   = $title;
        $this->_priceTotal              = $priceTotal;
        $this->_priceUnit               = $priceUnit;
        $this->_equivalentPaymentPlanId = $equivalentPaymentPlanId;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function getPriceTotal()
    {
        return $this->_priceTotal;
    }

    public function getPriceUnit()
    {
        return $this->_priceUnit;
    }

    public function getEquivalentPaymentPlanId()
    {
        return $this->_equivalentPaymentPlanId;
    }
    
    
    public function setEquivalentPaymentPlan(\Pley\Entity\Payment\PaymentPlan $plan)
    {
        $this->_equivalementPaymentPlan = $plan;
    }
    
    /** ♰
     * @return \Pley\Entity\Payment\PaymentPlan
     */
    public function getEquivalentPaymentPlan()
    {
        return $this->_equivalementPaymentPlan;
    }

}
