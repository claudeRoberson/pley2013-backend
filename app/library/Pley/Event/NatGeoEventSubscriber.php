<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Event;

use \Pley\Db\AbstractDatabaseManager as DatabaseManager;

/**
 * The <kbd>NatGeoEventSubscriber</kbd> Handles events that require NatGeo specific attention
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Event
 * @subpackage Event
 */
class NatGeoEventSubscriber extends AbstractEventSubscriber
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    
    public function __construct(DatabaseManager $dbManager)
    {
        $this->_dbManager = $dbManager;
    }
    
    public function handleGrantMission(\Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubscriptionShipment)
    {
        // This handlng is specific to National Geographic subscriptions, so, any other progress
        // for a different subscription should be ignored.
        if ($profileSubscriptionShipment->getSubscriptionId() != \Pley\Enum\SubscriptionEnum::NATIONAL_GEOGRAPHIC) {
            return;
        }
        
        $natGeo = new \Pley\NatGeo\NatGeo();
        
        $ngUserId = $profileSubscriptionShipment->getProfileSubscriptionId();
        
        // Retrieve NatGeo user, or create it if it doesn't exist yet
        try {
            $ngUser = $natGeo->getUser($ngUserId);
            
        } catch(\Pley\NatGeo\Exception\UserDoesntExistException $ude) {
            $natGeo->addUser($ngUserId);
            $ngUser = $natGeo->getUser($ngUserId);
        }
        
        $missionId = $this->_getMissionForItem($profileSubscriptionShipment->getItemId());
        
        // If missionId is empty, either there is no matching mission for the supplied item, or the
        // shipment has not been assigned an item yet.
        if (empty($missionId)) { 
            return;
        }
        
        // Since we now have a valid mission, let's check if the mission has been granted already
        $existingMissionMap = $ngUser->getMissionsMap();
        if (isset($existingMissionMap[$missionId])) {
            return;
        }
        
        // Now we know the user does not have the mission, so we grant it
        $natGeo->addMission($ngUserId, $missionId);
    }
    
    /**
     * Returns the matching Mission for the supplied ItemID, or <kbd>null</kbd> if there is no matching mission.
     * @param int $itemId
     * @return int
     */
    protected function _getMissionForItem($itemId)
    {
        // If item ID is not supplied or has the value of 0 or null, then the shipment has not been 
        // assigned an item for shipping and thus we cannot check if there is a mission related to 
        // it that needs to be unlocked
        if (empty($itemId)) {
            return null;
        }
        
        $prepSql  = 'SELECT `mission_id` FROM `item_x_ng_mission` WHERE `item_id` = ?';
        $prepStmt = $this->_dbManager->prepare($prepSql);
        $prepStmt->execute([$itemId]);
        
        $dbResult = $prepStmt->fetch(\PDO::FETCH_ASSOC);
        $prepStmt->closeCursor();
        
        // Check if the supplied Item does have a mission attached to it.
        if (empty($dbResult)) {
            return null;
        }
        
        return $dbResult['mission_id'];
    }
    
    /** {@inheritDoc} */
    protected function _getEventToMethodData()
    {
        return [
            [\Pley\Enum\EventEnum::SHIPMENT_PROGRESS, 'handleGrantMission'],
        ];
    }
}
