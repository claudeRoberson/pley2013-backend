<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Payment\Method;

/**
 * The <kbd>Transaction</kbd> represents an event like a charge on a credit card.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Payment.Method
 * @subpackage Payment
 */
class Transaction extends \Pley\Payment\VendorData
{
    /** @var float */
    protected $_amount;
    /** @var int Time since EPOC */
    protected $_transactionAt;
    
    public function __construct($amount, $transactionAt)
    {
        $this->_amount        = $amount;
        $this->_transactionAt = $transactionAt;
    }

    /** @return float */
    public function getAmount()
    {
        return $this->_amount;
    }

    /** @return int Time since EPOC */
    public function getTransactionAt()
    {
        return $this->_transactionAt;
    }

}
