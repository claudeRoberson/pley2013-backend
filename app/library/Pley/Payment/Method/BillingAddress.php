<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Payment\Method;

/**
 * The <kbd>BillingAddress</kbd> represents the billing address for a credit card.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Payment.Method
 * @subpackage Payment
 */
class BillingAddress
{
    /** @var string */
    protected $_street1;
    /** @var string */
    protected $_street2;
    /** @var string */
    protected $_city;
    /** @var string */
    protected $_state;
    /** @var string */
    protected $_zipCode;
    /** @var string */
    protected $_country;
    
    public function __construct($street1, $street2, $city, $state, $zipCode, $country)
    {
        $this->_street1 = $street1;
        $this->_street2 = empty($street2)? null : $street2;
        $this->_city    = $city;
        $this->_state   = $state;
        $this->_zipCode = $zipCode;
        $this->_country = $country;
    }

    /** @return string */
    public function getStreet1()
    {
        return $this->_street1;
    }

    /** @return string|null */
    public function getStreet2()
    {
        return $this->_street2;
    }

    /** @return string */
    public function getCity()
    {
        return $this->_city;
    }

    /** @return string */
    public function getState()
    {
        return $this->_state;
    }

    /** @return string */
    public function getZipCode()
    {
        return $this->_zipCode;
    }

    /** @return string */
    public function getCountry()
    {
        return $this->_country;
    }

}
