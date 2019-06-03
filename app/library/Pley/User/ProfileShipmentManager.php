<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\User;

use \Pley\Db\AbstractDatabaseManager as DatabaseManager;

/** ♰
 * The <kbd>ProfileShipmentManager</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.User
 * @subpackage User
 */
class ProfileShipmentManager
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionShipmentDao */
    protected $_profileSubsShipmentDao;
    
    public function __construct(DatabaseManager $dbManager,
            \Pley\Dao\Profile\ProfileSubscriptionShipmentDao $profileSubscriptionShipmentDao)
    {
        $this->_dbManager = $dbManager;
        $this->_profileSubsShipmentDao = $profileSubscriptionShipmentDao;
    }
    
    /** ♰
     * @param int $shipmentId
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment
     */
    public function getShipment($shipmentId)
    {
        return $this->_profileSubsShipmentDao->find($shipmentId);
    }
    
    /** ♰
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment $shipment
     */
    public function updateShipment(\Pley\Entity\Profile\ProfileSubscriptionShipment $shipment)
    {
        if (empty($shipment->getId())) {
            throw new \Pley\Exception\Entity\EntityNotFoundException(\Pley\Entity\Profile\ProfileSubscriptionShipment::class);
        }
        
        $this->_profileSubsShipmentDao->save($shipment);
    }
    
    /** 
     * Returns the next available shipment for the supplied parameters.
     * @param int $subscriptionId
     * @param int $periodIndex
     * @param int $itemId
     * @param int $shirtSizeId
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment
     */
    public function getNextShipmentToProcess($subscriptionId, $periodIndex, $itemId, $shirtSizeId)
    {
        return $this->_profileSubsShipmentDao->nextShipmentToProcess(
            $subscriptionId, $periodIndex, $itemId, $shirtSizeId
        );
    }

    /**
     * Returns the next available shipment for the supplied parameters.
     * @param int $subscriptionId
     * @param int $periodIndex
     * @param int $itemId
     * @param int $shirtSizeId
     * @param bool $onlyNew
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment[]
     */
    public function getNextShipmentBatchToProcess($subscriptionId, $periodIndex, $itemId, $shirtSizeId, $onlyNew = false)
    {
        if($onlyNew){
            return $this->_profileSubsShipmentDao->nextShipmentBatchNewUsersOnly(
                $subscriptionId, $periodIndex, $itemId, $shirtSizeId
            );
        }
        return $this->_profileSubsShipmentDao->nextShipmentBatchToProcess(
            $subscriptionId, $periodIndex, $itemId, $shirtSizeId
        );
    }

}
