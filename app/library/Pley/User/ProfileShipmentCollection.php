<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\User;

/**
 * The <kbd>ProfileShipmentCollection</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package 
 * @subpackage
 */
class ProfileShipmentCollection
{
    /** @var \Pley\Entity\Profile\ProfileSubscriptionShipment[] */
    protected $_deliveredList;
    /** @var \Pley\Entity\Profile\ProfileSubscriptionShipment */
    protected $_current;
    /** @var \Pley\Entity\Profile\ProfileSubscriptionShipment[] */
    protected $_pendingList;
    
    /**
     * Creates a new <kbd>ProfileShipmentCollection</kbd> object.
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment[]    $deliveredList
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment|null $current
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment[]    $pendingList
     */
    public function __construct($deliveredList, $current, $pendingList)
    {
        $this->_deliveredList = $deliveredList;
        $this->_current       = $current;
        $this->_pendingList   = $pendingList;
    }

    /** @return \Pley\Entity\Profile\ProfileSubscriptionShipment[] */
    public function getDeliveredList()
    {
        return $this->_deliveredList;
    }

    /** @return \Pley\Entity\Profile\ProfileSubscriptionShipment */
    public function getCurrent()
    {
        return $this->_current;
    }

    /** @return \Pley\Entity\Profile\ProfileSubscriptionShipment[]|null */
    public function getPendingList()
    {
        return $this->_pendingList;
    }

}
