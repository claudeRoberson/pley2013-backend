<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Dao\Profile;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Traits\CacheDaoTrait;
use \Pley\Entity\Profile\ProfileSubscriptionPlan;

/** ♰
 * The <kbd>ProfileSubscriptionPlanDao</kbd> class provides implementation to interact with the 
 * Profile Subscription Plan table in the DB and Cache.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.Profile
 * @subpackage Dao
 */
class ProfileSubscriptionPlanDao extends AbstractDbDao implements DbDaoInterface, CacheDaoInterface
{
    use CacheDaoTrait; // Provides implementation for CacheDaoInterface

    /** @var string */
    protected $_tableName = 'profile_subscription_plan';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'user_id', 'user_profile_id', 'profile_subscription_id', 'payment_plan_id',
            'status', 'is_auto_renew', 'v_payment_system_id', 'v_payment_plan_id', 'v_payment_subscription_id',
            'auto_renew_stop_at', 'cancel_at', 'cancel_source', 'cancel_op_user_id', 'created_at', 
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /** ♰
     * @param int $id
     * @return \Pley\Entity\Profile\ProfileSubscriptionPlan
     */
    public function find($id)
    {   
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . "WHERE `id` = ?";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$id];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $entity = $this->_toEntity($dbRecord);
        
        return $entity;
    }
    
    /**
     * @param int $profileSubscriptionId
     * @return \Pley\Entity\Profile\ProfileSubscriptionPlan
     */
    public function findLastByProfileSubscription($profileSubscriptionId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `profile_subscription_id` = ? '
                  . 'ORDER BY `id` DESC LIMIT 1';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$profileSubscriptionId];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        
        $entity = $this->_toEntity($dbRecord);
        
        return $entity;
    }

    /**
     * @param int $userId
     * @param int $vPaymentSystemId
     * @return \Pley\Entity\Profile\ProfileSubscriptionPlan
     */
    public function findLastByUserAndVPaymentSystem($userId, $vPaymentSystemId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `user_id` = ? AND `v_payment_system_id` = ? AND `status` = ? '
            . 'ORDER BY `id` DESC LIMIT 1';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$userId, $vPaymentSystemId, \Pley\Enum\SubscriptionStatusEnum::ACTIVE];

        $pstmt->execute($bindings);

        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        $entity = $this->_toEntity($dbRecord);

        return $entity;
    }

    /**
     * Get a <kbd>ProfileSubscriptionPlan</kbd> by a vendor subscription ID
     * @param string $vendorSubscriptionId
     * @return \Pley\Entity\Profile\ProfileSubscriptionPlan
     */
    public function findByVendorSubscriptionId($vendorSubscriptionId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `v_payment_subscription_id` = ? '
            . 'ORDER BY `id` DESC LIMIT 1';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$vendorSubscriptionId];

        $pstmt->execute($bindings);

        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        $entity = $this->_toEntity($dbRecord);

        return $entity;
    }
    
    /**
     * @param int $profileSubscriptionId
     * @return \Pley\Entity\Profile\ProfileSubscriptionPlan[]
     */
    public function findByProfileSubscription($profileSubscriptionId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `profile_subscription_id` = ? '
                  . 'ORDER BY `id` DESC';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$profileSubscriptionId];

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
     * @param int $statusId
     * @return \Pley\Entity\Profile\ProfileSubscriptionPlan[]
     */
    public function findByStatus($statusId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `status` = ? '
            . 'ORDER BY `id` DESC';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$statusId];

        $pstmt->execute($bindings);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $pstmt->rowCount();
        $pstmt->closeCursor();

        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }

        return $resultSet;
    }
    
    public function save(ProfileSubscriptionPlan $profileSubscriptionPlan)
    {
        // Depending on the Entity ID value, either update or insert.
        if (empty($profileSubscriptionPlan->getId())) {
            $this->_insert($profileSubscriptionPlan);
        } else {
            $this->_update($profileSubscriptionPlan);
        }
    }
   
    private function _insert(ProfileSubscriptionPlan $profileSubscriptionPlan)
    {
        $prepSql  = "INSERT INTO `{$this->_tableName}` ("
                  .     '`user_id`, '
                  .     '`user_profile_id`, '
                  .     '`profile_subscription_id`, '
                  .     '`payment_plan_id`, '
                  .     '`status`, '
                  .     '`is_auto_renew`, '
                  .     '`created_at` '
                  . ') VALUES (?, ?, ?, ?, ?, ?, NOW())';
        $pstmt    = $this->_prepare($prepSql);
        
        // converting the Object to a JSON String
        $bindings = [
            $profileSubscriptionPlan->getUserId(),
            $profileSubscriptionPlan->getProfileId(),
            $profileSubscriptionPlan->getProfileSubscriptionId(),
            $profileSubscriptionPlan->getPaymentPlanId(),
            $profileSubscriptionPlan->getStatus(),
            $profileSubscriptionPlan->isAutoRenew() ? 1 : 0,
        ];
        
        $pstmt->execute($bindings);
        
        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $profileSubscriptionPlan->setId($id);
    }
    
    private function _update(ProfileSubscriptionPlan $profileSubscriptionPlan)
    {
        $prepSql  = "UPDATE `{$this->_tableName}` SET "
                  .     '`v_payment_system_id` = ?, '
                  .     '`v_payment_plan_id` = ?, '
                  .     '`v_payment_subscription_id` = ?, '
                  .     '`status` = ?, '
                  .     '`is_auto_renew` = ?, '
                  .     '`auto_renew_stop_at` = ?, '
                  .     '`cancel_at` = ?, '
                  .     '`cancel_source` = ?, '
                  .     '`cancel_op_user_id` = ? '
                  . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);
        
        // converting the Object to a JSON String
        $bindings = [
            $profileSubscriptionPlan->getVPaymentSystemId(),
            $profileSubscriptionPlan->getVPaymentPlanId(),
            $profileSubscriptionPlan->getVPaymentSubscriptionId(),
            $profileSubscriptionPlan->getStatus(),
            $profileSubscriptionPlan->isAutoRenew()? 1 : 0,
            \Pley\Util\DateTime::date($profileSubscriptionPlan->getAutoRenewStopAt()),
            \Pley\Util\DateTime::date($profileSubscriptionPlan->getCancelAt()),
            $profileSubscriptionPlan->getCancelSource(),
            $profileSubscriptionPlan->getCancelOperationsUserId(),
            
            $profileSubscriptionPlan->getId(),
        ];
        
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }
    
    /** ♰
     * @param array $dbRecord
     * @return \Pley\Entity\Profile\ProfileSubscriptionPlan
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new ProfileSubscriptionPlan(
            $dbRecord['id'],
            $dbRecord['user_id'],
            $dbRecord['user_profile_id'],
            $dbRecord['profile_subscription_id'],
            $dbRecord['payment_plan_id'],
            $dbRecord['status'],
            $dbRecord['is_auto_renew'] == 1,
            $dbRecord['v_payment_system_id'],
            $dbRecord['v_payment_plan_id'],
            $dbRecord['v_payment_subscription_id'],
            \Pley\Util\Time\DateTime::strToTime($dbRecord['auto_renew_stop_at']),
            \Pley\Util\Time\DateTime::strToTime($dbRecord['cancel_at']),
            $dbRecord['cancel_source'],
            $dbRecord['cancel_op_user_id'],
            \Pley\Util\Time\DateTime::strToTime($dbRecord['created_at'])
        );
    }
}
