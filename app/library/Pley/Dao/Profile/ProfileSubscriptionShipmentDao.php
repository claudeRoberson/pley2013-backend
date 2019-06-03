<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Dao\Profile;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Traits\CacheDaoTrait;
use \Pley\Entity\Profile\ProfileSubscriptionShipment;
use Pley\Enum\Shipping\ShipmentStatusEnum;

/** ♰
 * The <kbd>ProfileSubscriptionShipmentDao</kbd> class provides implementation to interact with the 
 * Profile Subscription Shipment table in the DB and Cache.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.Profile
 * @subpackage Dao
 */
class ProfileSubscriptionShipmentDao extends AbstractDbDao implements DbDaoInterface, CacheDaoInterface
{
    use CacheDaoTrait; // Provides implementation for CacheDaoInterface

    private static $LOCK_FOR_UDATE = true;
    
    /** @var string */
    protected $_tableName = 'profile_subscription_shipment';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'user_id', 'user_profile_id', 'profile_subscription_id', 'subscription_id', 
            'type_shipment_source_id', 'shipment_source_id', 
            'schedule_index', 'item_sequence_index', 'item_id', 'status', 'type_shirt_size_id',
            'carrier_id', 'carrier_service_id', 'carrier_rate', 'label_url', 'tracking_no', 
            'v_ship_id', 'v_ship_tracker_id',
            'shipped_at', 'delivered_at', 'label_purchase_at',
            'street_1', 'street_2', 'city', 'state', 'zip', 'country', 'shipping_zone_id',
            'shipping_zone_usps',
            'label_lease',
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /** 
     * Retrieve the a ProfileShipment by its ID.
     * @param int $id
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment
     */
    public function find($id)
    {   
        $entity = $this->_find($id);
        return $entity;
    }
    
    /** 
     * Retrieve the a ProfileShipment by its ID while locking it. (DB transaction needed)
     * @param int $id
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment
     */
    public function lockFind($id)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $entity = $this->_find($id, static::$LOCK_FOR_UDATE);
        return $entity;
    }
    
    /**
     * Returns a list of <kbd>ProfileSubscriptionShipment</kbd> objects for the supplied profile ID.
     * @param int $profileSubscriptionId
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment[]
     */
    public function findByProfileSubscription($profileSubscriptionId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `profile_subscription_id` = ? '
                  . 'ORDER BY `id` DESC ';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$profileSubscriptionId];

        $pstmt->execute($bindings);
        
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        // in-place replacement of array to object representation
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }
        
        return $resultSet;
    }

    /** 
     * Retrieve the a ProfileShipment by the label vendor shipment ID.
     * @param int $vShipId
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment
     */
    public function findByVendorShipId($vShipId)
    {
        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                 . 'WHERE `v_ship_id` = ? ';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$vShipId];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $entity = $this->_toEntity($dbRecord);
        
        return $entity;
    }
    
    /**
     * Returns the Profile shipment object for the supplied profile on the given shipping period if
     * one exists.
     * @param int $profileSubscriptionId
     * @param int $periodIndex
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment
     */
    public function findByPeriod($profileSubscriptionId, $periodIndex)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `profile_subscription_id` = ? '
                  .   'AND `schedule_index` = ? ';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$profileSubscriptionId, $periodIndex];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $entity   = $this->_toEntity($dbRecord);
        
        return $entity;
    }

    /**
     * Returns a list of customer shipped shipments
     * @param int $profileSubscriptionId
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment[]
     */
    public function findProfileSubscriptionShipped($profileSubscriptionId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `profile_subscription_id` = ? AND `status` <> ? ';
        $bindings = [$profileSubscriptionId, ShipmentStatusEnum::PREPROCESSING];

        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute($bindings);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $pstmt->rowCount();
        $pstmt->closeCursor();

        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }
        return $resultSet;
    }
    
    /**
     * Returns a list of Profile shipment objects for the supplied shipping period any exists.
     * @param int $subscriptionId
     * @param int $periodIndex
     * @param int $itemId      (Optional)<br/>Additional filter to retrieve shipments.
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment[]
     */
    public function findAllByPeriod($subscriptionId, $periodIndex, $itemId = null)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `subscription_id` = ? AND `schedule_index` = ? ';
        $bindings = [$subscriptionId, $periodIndex];
        
        if (isset($itemId)) {
            $prepSql   .= ' AND `item_id` = ? ';
            $bindings[] = $itemId;
        }
        
        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute($bindings);
        
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }
        
        return $resultSet;
    }
    
    /**
     * Returns a list of all not yet shipped <kbd>ProfileSubscriptionShipment</kbd> objects for the 
     * supplied profile ID.
     * @param int $profileSubscriptionId
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment[]
     */
    public function findNotShipped($profileSubscriptionId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `profile_subscription_id` = ? AND `status` = ? '
                  . 'ORDER BY `id` DESC ';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [
            $profileSubscriptionId,
            \Pley\Enum\Shipping\ShipmentStatusEnum::PREPROCESSING,
        ];

        $pstmt->execute($bindings);
        
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        // in-place replacement of array to object representation
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }
        
        return $resultSet;
    }
    
    /**
     * Returns a list Item IDs for shipments that match the supplied schedule index.
     * @param int $subscriptionId
     * @param int $periodIndex
     * @return int[]
     */
    public function getItemIdListByPeriod($subscriptionId, $periodIndex, $status = null)
    {
        $prepSql  = "SELECT DISTINCT `item_id` FROM `{$this->_tableName}` "
                  . 'WHERE `subscription_id` = ? '
                  .   'AND `schedule_index` = ? '
                  .   'AND `item_id` IS NOT NULL ';
        $bindings = [$subscriptionId, $periodIndex];
        
        if (isset($status)) {
            $prepSql .= 'AND `status` = ? ';
            $bindings[] = $status;
        }
        
        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute($bindings);
        
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        
        $itemIdList = [];
        foreach ($resultSet as $dbRecord) {
            $itemIdList[] = $dbRecord['item_id'];
        }
        
        return $itemIdList;
    }
    
    /** 
     * Returns the next available shipment for the supplied parameters.
     * @param int $subscriptionId
     * @param int $periodIndex
     * @param int $itemId
     * @param int $shirtSizeId
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment
     */
    public function nextShipmentToProcess($subscriptionId, $periodIndex, $itemId, $shirtSizeId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `status` = 1 '
                  .   'AND `subscription_id` = ? '
                  .   'AND `schedule_index` = ? '
                  .   'AND `item_id` = ? '
                  .   'AND `type_shirt_size_id` = ? '
                  . 'ORDER BY `shipping_zone_id` ASC '
                  . 'LIMIT 1';
        
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$subscriptionId, $periodIndex, $itemId, $shirtSizeId];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $entity = $this->_toEntity($dbRecord);
        
        return $entity;
    }

    /**
     * Returns the next available shipment batch for the supplied parameters.
     * @param int $subscriptionId
     * @param int $periodIndex
     * @param int $itemId
     * @param int $shirtSizeId
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment[]
     */
    public function nextShipmentBatchToProcess($subscriptionId, $periodIndex, $itemId, $shirtSizeId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `status` = 1 '
            .   'AND `subscription_id` = ? '
            .   'AND `schedule_index` = ? '
            .   'AND `item_id` = ? '
            .   'AND `type_shirt_size_id` = ? '
            . 'ORDER BY `shipping_zone_id` ASC '
            . 'LIMIT 20';

        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$subscriptionId, $periodIndex, $itemId, $shirtSizeId];

        $pstmt->execute($bindings);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $pstmt->rowCount();
        $pstmt->closeCursor();

        // in-place replacement of array to object representation
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }
        return $resultSet;
    }

    /**
     * Returns the next available shipment batch for new users (very first shipment).
     * @param int $subscriptionId
     * @param int $periodIndex
     * @param int $itemId
     * @param int $shirtSizeId
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment[]
     */
    public function nextShipmentBatchNewUsersOnly($subscriptionId, $periodIndex, $itemId, $shirtSizeId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `status` = ? '
            .   'AND `subscription_id` = ? '
            .   'AND `schedule_index` = ? '
            .   'AND `item_id` = ? '
            .   'AND `type_shirt_size_id` = ? '
            .   'AND `profile_subscription_id` NOT IN (
            SELECT `profile_subscription_id` FROM `profile_subscription_shipment`
WHERE `status` <> ? and `subscription_id` = ? GROUP BY `profile_subscription_id`) '
            . 'ORDER BY `shipping_zone_id` ASC '
            . 'LIMIT 20';

        $pstmt    = $this->_prepare($prepSql);
        $bindings = [ShipmentStatusEnum::PREPROCESSING, $subscriptionId, $periodIndex, $itemId, $shirtSizeId, ShipmentStatusEnum::PREPROCESSING, $subscriptionId];

        $pstmt->execute($bindings);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $pstmt->rowCount();
        $pstmt->closeCursor();

        // in-place replacement of array to object representation
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }
        return $resultSet;
    }
    
    /** ♰
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubsShipment
     */
    public function save(ProfileSubscriptionShipment $profileSubsShipment)
    {
        if (empty($profileSubsShipment->getId())) {
            $this->_insert($profileSubsShipment);
        } else {
            $this->_update($profileSubsShipment);
        }
    }
    
    /**
     * Remove a Profile Shipment
     * <p>This should basically only be invoked as a result of a CustomerService full 
     * subscription cancellation</p>
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubsShipment
     */
    public function delete(ProfileSubscriptionShipment $profileSubsShipment)
    {
        $prepSql  = "DELETE FROM `{$this->_tableName}` WHERE `id` = ?";
        $pstmt    = $this->_prepare($prepSql);
        
        $bindings = [$profileSubsShipment->getId()];
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }
    
    /** ♰
     * @param array $dbRecord
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new ProfileSubscriptionShipment(
            $dbRecord['id'],
            $dbRecord['user_id'],
            $dbRecord['user_profile_id'],
            $dbRecord['profile_subscription_id'],
            $dbRecord['subscription_id'],
            $dbRecord['type_shipment_source_id'],
            $dbRecord['shipment_source_id'],
            $dbRecord['schedule_index'],
            $dbRecord['item_sequence_index'],
            $dbRecord['item_id'],
            $dbRecord['status'],
            $dbRecord['type_shirt_size_id'],
            $dbRecord['carrier_id'],
            $dbRecord['carrier_service_id'],
            $dbRecord['carrier_rate'],
            $dbRecord['label_url'],
            $dbRecord['tracking_no'],
            $dbRecord['v_ship_id'],
            $dbRecord['v_ship_tracker_id'],
            \Pley\Util\Time\DateTime::strToTime($dbRecord['shipped_at']),
            \Pley\Util\Time\DateTime::strToTime($dbRecord['delivered_at']),
            \Pley\Util\Time\DateTime::strToTime($dbRecord['label_purchase_at']),
            $dbRecord['street_1'],
            $dbRecord['street_2'],
            $dbRecord['city'],
            $dbRecord['state'],
            $dbRecord['zip'],
            $dbRecord['country'],
            $dbRecord['shipping_zone_id'],
            $dbRecord['shipping_zone_usps'],
            $dbRecord['label_lease']
        );
    }
    
    /**
     * Helper function to retrieve the a ProfileShipment by its ID and provide the capability to either
     * lock the record while doing so.
     * @param int     $id
     * @param boolean $lockForUpdate (Optional)<br/>Default <kbd>FALSE</kbd><br/>Used to Lock the
     *      record for reading if needed within a transaction to prevent a race condition.
     * @return \Pley\Entity\Profile\ProfileSubscriptionShipment|null
     */
    private function _find($id, $lockForUpdate = false)
    {
        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                 . 'WHERE `id` = ? ';
        
        if ($lockForUpdate) {
            $prepSql .= 'FOR UPDATE ';
        }
        
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$id];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $entity = $this->_toEntity($dbRecord);
        
        return $entity;
    }
    
    /** ♰
     * @param \Pley\Entity\Subscription\Subscription $profileSubsShipment
     */
    private function _insert(ProfileSubscriptionShipment $profileSubsShipment)
    {
        $prepSql  = "INSERT INTO `{$this->_tableName}` ("
                  .     '`user_id`, '
                  .     '`user_profile_id`, '
                  .     '`profile_subscription_id`, '
                  .     '`subscription_id`, '
                  .     '`type_shipment_source_id`, '
                  .     '`shipment_source_id`, '
                  .     '`schedule_index`, '
                  .     '`item_sequence_index`, '
                  .     '`status`, '
                  .     '`created_at` '
                  . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $pstmt    = $this->_prepare($prepSql);
        
        $bindings = [
            $profileSubsShipment->getUserId(),
            $profileSubsShipment->getProfileId(),
            $profileSubsShipment->getProfileSubscriptionId(),
            $profileSubsShipment->getSubscriptionId(),
            $profileSubsShipment->getShipmentSourceType(),
            $profileSubsShipment->getShipmentSourceId(),
            $profileSubsShipment->getScheduleIndex(),
            $profileSubsShipment->getItemSequenceIndex(),
            $profileSubsShipment->getStatus(),
        ];
        
        $pstmt->execute($bindings);
        
        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $profileSubsShipment->setId($id);
    }
    
    /** ♰
     * @param \Pley\Entity\Subscription\Subscription $profileSubsShipment
     */
    private function _update(ProfileSubscriptionShipment $profileSubsShipment)
    {
        // User Data is not allowed to be changed from the checkout
        $prepSql  = "UPDATE `{$this->_tableName}` "
                  . 'SET `item_id` = ?, '
                  .     '`status` = ?, '
                  .     '`schedule_index` = ?, '
                  .     '`item_sequence_index` = ?, '
                  .     '`type_shirt_size_id` = ?, '
                  .     '`carrier_id` = ?, '
                  .     '`carrier_service_id` = ?, '
                  .     '`carrier_rate` = ?, '
                  .     '`label_url` = ?, '
                  .     '`tracking_no` = ?, '
                  .     '`v_ship_id` = ?, '
                  .     '`v_ship_tracker_id` = ?, '
                  .     '`shipped_at` = ?, '
                  .     '`delivered_at` = ?, '
                  .     '`label_purchase_at` = ?, '
                  .     '`street_1` = ?, '
                  .     '`street_2` = ?, '
                  .     '`city` = ?, '
                  .     '`state` = ?, '
                  .     '`zip` = ?, '
                  .     '`country` = ?, '
                  .     '`shipping_zone_id` = ?, '
                  .     '`shipping_zone_usps` = ?, '
                  .     '`label_lease` = ? '
                  . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);
        
        $bindings = [
            $profileSubsShipment->getItemId(),
            $profileSubsShipment->getStatus(),
            $profileSubsShipment->getScheduleIndex(),
            $profileSubsShipment->getItemSequenceIndex(),
            $profileSubsShipment->getShirtSize(),
            $profileSubsShipment->getCarrierId(),
            $profileSubsShipment->getCarrierServiceId(),
            $profileSubsShipment->getCarrierRate(),
            $profileSubsShipment->getLabelUrl(),
            $profileSubsShipment->getTrackingNo(),
            $profileSubsShipment->getVendorShipId(),
            $profileSubsShipment->getVendorShipTrackerId(),
            \Pley\Util\Time\DateTime::date($profileSubsShipment->getShippedAt()),
            \Pley\Util\Time\DateTime::date($profileSubsShipment->getDeliveredAt()),
            \Pley\Util\Time\DateTime::date($profileSubsShipment->getLabelPurchaseAt()),
            $profileSubsShipment->getStreet1(),
            $profileSubsShipment->getStreet2(),
            $profileSubsShipment->getCity(),
            $profileSubsShipment->getState(),
            $profileSubsShipment->getZip(),
            $profileSubsShipment->getCountry(),
            $profileSubsShipment->getShippingZoneId(),
            $profileSubsShipment->getUspsShippingZoneId(),
            $profileSubsShipment->getLabelLease(),
            
            // WHERE bindings
            $profileSubsShipment->getId(),
        ];
        
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }
}
