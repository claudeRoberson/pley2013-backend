<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Payment;

/**
 * The <kbd>Subscription</kbd> represents the relationship between a User Profile subscription
 * and the Vendor Subscription.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Payment
 * @subpackage Payment
 */
class Subscription extends VendorData
{
    /** @var \Pley\Entity\User\User */
    protected $_user;
    /** @var int Time since EPOC */
    protected $_periodDateStart;
    /** @var int Time since EPOC */
    protected $_periodDateEnd;
    
    public function __construct(\Pley\Entity\User\User $user)
    {
        $this->_user = $user;
    }

    /**
     * Returns the internal reference to the <kbd>User</kbd> object used to create this Payment Subscripton.
     * @return \Pley\Entity\User\User
     */
    public function getUser()
    {
        return $this->_user;
    }
    
    /** @return int Time since EPOC */
    public function getPeriodDateStart()
    {
        return $this->_periodDateStart;
    }
    
    /** @return int Time since EPOC */
    public function getPeriodDateEnd()
    {
        return $this->_periodDateEnd;
    }
    
    /** @return int Time since EPOC */
    public function getCancelAt()
    {
        return $this->_cancelAt;
    }
    
    /**
     * Updates the reference to the original vendor data.
     * <p>Overriding parent method so that we can set the Period Start and End dates.
     * @param mixed $metadata
     */
    public function setVendorMetadata($metadata)
    {
        parent::setVendorMetadata($metadata);
        
        $this->_periodDateStart = $metadata->current_period_start;
        $this->_periodDateEnd   = $metadata->current_period_end;
        $this->_cancelAt        = $metadata->canceled_at;
    }
}
