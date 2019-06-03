<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Payment\Method;

/**
 * The <kbd>CreditCard</kbd> represents a credit card data.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Payment.Method
 * @subpackage Payment
 */
class CreditCard extends \Pley\Payment\VendorData
{
    /** @var string */
    protected $_number;
    /** @var int */
    protected $_expMonth;
    /** @var int */
    protected $_expYear;
    /** @var int|null */
    protected $_cvv;
    /** @var int|null */
    protected $_brand;
    /** @var int|null */
    protected $_type;
    /** @var \Pley\Payment\Method\BillingAddress|null */
    protected $_billingAddress;
    
    public function __construct($number, $expMonth, $expYear)
    {
        $this->_number    = $number;
        $this->_expMonth = $expMonth;
        $this->_expYear  = $expYear;
    }

    /**
     * Returns either the Full credit card number or the Last 4 digits based on how it was set
     * at construction time.
     * <ul>
     *   <li>The Full number will be returned when using this object to add a new credit card or
     *      to check if a given card is already in the vendor system.</li>
     *   <li>The Last 4 digits will be returned when retrieving this object from the vendor system</li>
     * </ul>
     * @return string
     */
    public function getNumber()
    {
        return $this->_number;
    }

    /** @return int */
    public function getExpirationMonth()
    {
        return $this->_expMonth;
    }

    /** @return int */
    public function getExpirationYear()
    {
        return $this->_expYear;
    }

    /** @return int */
    public function getCVV()
    {
        return $this->_cvv;
    }

    /** @param int $cvv */
    public function setCVV($cvv)
    {
        $this->_cvv = $cvv;
    }
    
    /**
     * Returns the Brand ID for this credit card
     * @return int
     */
    public function getBrand()
    {
        return $this->_brand;
    }

    /**
     * Sets a the credit card's Brand ID (i.e. VISA)
     * @param int $brand A value from <kbd>\Pley\Enum\PaymentMethodBrandEnum</kbd>
     */
    public function setBrand($brand)
    {
        \Pley\Enum\PaymentMethodBrandEnum::validate($brand);
        
        $this->_brand = $brand;
    }

    /**
     * Returns a string indicating the funding type of the card. (i.e. `credit` or `debit`)
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Sets the funding type of the card.
     * @param string $type
     */
    public function setType($type)
    {
        $this->_type = $type;
    }
    
    /** @return \Pley\Payment\Method\BillingAddress|null */
    public function getBillingAddress()
    {
        return $this->_billingAddress;
    }

    /** @param \Pley\Payment\Method\BillingAddress $billingAddress */
    public function setBillingAddress(\Pley\Payment\Method\BillingAddress $billingAddress)
    {
        $this->_billingAddress = $billingAddress;
    }

}
