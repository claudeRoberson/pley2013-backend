<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Dao\Profile;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Traits\CacheDaoTrait;
use \Pley\Entity\Profile\ProfileSubscriptionTransaction;
use Pley\Enum\TransactionEnum;

/** ♰
 * The <kbd>ProfileSubscriptionTransactionDao</kbd> class provides implementation to interact with the 
 * Profile Subscription Transaction table in the DB and Cache.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.Profile
 * @subpackage Dao♰
 */
class ProfileSubscriptionTransactionDao extends AbstractDbDao implements DbDaoInterface, CacheDaoInterface
{
    use CacheDaoTrait; // Provides implementation for CacheDaoInterface
    
    /** @var string */
    protected $_tableName = 'profile_subscription_transaction';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'user_id', 'user_profile_id', 'profile_subscription_id', 'profile_subscription_plan_id',
            'user_payment_method_id', 'type_transaction_id', 'amount', 'v_payment_system_id', 
            'v_payment_method_id', 'v_payment_transaction_id', 'transaction_at', 'transaction_op_user_id',
            'base_amount', 'discount_amount', 'discount_type', 'discount_source_id', 
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /** ♰
     * Return the <kbd>ProfileSubscriptionTransaction</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\Profile\ProfileSubscriptionTransaction
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
     * Return a list of <kbd>ProfileSubscriptionTransaction</kbd> entity for the supplied profile subscription
     * @param int $profileSubscriptionId
     * @return \Pley\Entity\Profile\ProfileSubscriptionTransaction[]
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
     * Return the last <kbd>ProfileSubscriptionTransaction</kbd> for a CHARGE on the supplied profile subscription.
     * @param int $profileSubscriptionId
     * @return \Pley\Entity\Profile\ProfileSubscriptionTransaction
     */
    public function findByLastCharge($profileSubscriptionId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `profile_subscription_id` = ? '
                  .   'AND `type_transaction_id` = ? '
                  . 'ORDER BY `id` DESC LIMIT 1';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$profileSubscriptionId, \Pley\Enum\TransactionEnum::CHARGE];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        
        $entity = $this->_toEntity($dbRecord);
        return $entity;
    }

    /**
     * Return <kbd>ProfileSubscriptionTransaction</kbd> by a Stripe charge ID
     * @param string $chargeId
     * @param int $type
     * @return \Pley\Entity\Profile\ProfileSubscriptionTransaction
     */
    public function findByChargeId($chargeId, $type = \Pley\Enum\TransactionEnum::CHARGE)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `v_payment_transaction_id` = ? '
            .   'AND `type_transaction_id` = ? '
            . 'ORDER BY `id` DESC LIMIT 1';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$chargeId, $type];

        $pstmt->execute($bindings);

        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        $entity = $this->_toEntity($dbRecord);
        return $entity;
    }

    /**
     * Return a list of <kbd>ProfileSubscriptionTransaction</kbd> entity for the supplied payment method id and type
     * @param int $paymentMethodId
     * @param int $type
     * @return \Pley\Entity\Profile\ProfileSubscriptionTransaction[]
     */
    public function findByPaymentMethodId($paymentMethodId, $type = TransactionEnum::CHARGE)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `user_payment_method_id` = ? AND `type_transaction_id` = ? '
            . 'ORDER BY `id` DESC';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$paymentMethodId, $type];

        $pstmt->execute($bindings);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $pstmt->rowCount();
        $pstmt->closeCursor();

        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }

        return $resultSet;
    }
    
    public function save(ProfileSubscriptionTransaction $profileSubscriptionTransaction)
    {
        // Depending on the Entity ID value, either update or insert.
        if (empty($profileSubscriptionTransaction->getId())) {
            $this->_insert($profileSubscriptionTransaction);
        } else {
            $this->_update($profileSubscriptionTransaction);
        }
    }

    private function _insert(ProfileSubscriptionTransaction $profileSubscriptionTransaction)
    {
        $prepSql  = "INSERT INTO `{$this->_tableName}` ("
                  .     '`user_id`, '
                  .     '`user_profile_id`, '
                  .     '`profile_subscription_id`, '
                  .     '`profile_subscription_plan_id`, '
                  .     '`user_payment_method_id`, '
                  .     '`type_transaction_id`, '
                  .     '`amount`, '
                  .     '`v_payment_system_id`, '
                  .     '`v_payment_method_id`, '
                  .     '`v_payment_transaction_id`, '
                  .     '`transaction_at`, '
                  .     '`base_amount`, '
                  .     '`discount_amount`, '
                  .     '`discount_type`, '
                  .     '`discount_source_id`, '
            . '`created_at` '
                  . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $pstmt    = $this->_prepare($prepSql);
        
        // converting the Object to a JSON String
        $bindings = [
            $profileSubscriptionTransaction->getUserId(),
            $profileSubscriptionTransaction->getProfileId(),
            $profileSubscriptionTransaction->getProfileSubscriptionId(),
            $profileSubscriptionTransaction->getProfileSubscriptionPlanId(),
            $profileSubscriptionTransaction->getUserPaymentMethodId(),
            $profileSubscriptionTransaction->getTransactionType(),
            $profileSubscriptionTransaction->getAmount(),
            $profileSubscriptionTransaction->getVPaymentSystemId(),
            $profileSubscriptionTransaction->getVPaymentMethodId(),
            $profileSubscriptionTransaction->getVPaymentTransactionId(),
            \Pley\Util\DateTime::date($profileSubscriptionTransaction->getTransactionAt()),
            $profileSubscriptionTransaction->getBaseAmount(),
            $profileSubscriptionTransaction->getDiscountAmount(),
            $profileSubscriptionTransaction->getDiscountType(),
            $profileSubscriptionTransaction->getDiscountSourceId()
        ];
        
        $pstmt->execute($bindings);
        
        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $profileSubscriptionTransaction->setId($id);
    }

    private function _update(ProfileSubscriptionTransaction $profileSubscriptionTransaction){
        // User Data is not allowed to be changed from the checkout
        $prepSql  = "UPDATE `{$this->_tableName}` "
            . 'SET `v_payment_transaction_id` = ? '
            . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);

        $bindings = [
            $profileSubscriptionTransaction->getVPaymentTransactionId(),
            // WHERE bindings
            $profileSubscriptionTransaction->getId(),
        ];

        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }
    
    /**
     * Map an associative array DB record into a <kbd>ProfileSubscriptionTransaction</kbd> Entity.
     * 
     * @param array $dbRecord
     * @return \Pley\Entity\Profile\ProfileSubscriptionTransaction
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new ProfileSubscriptionTransaction(
            $dbRecord['id'],
            $dbRecord['user_id'],
            $dbRecord['user_profile_id'],
            $dbRecord['profile_subscription_id'],
            $dbRecord['profile_subscription_plan_id'],
            $dbRecord['user_payment_method_id'],
            $dbRecord['type_transaction_id'],
            $dbRecord['amount'],
            $dbRecord['v_payment_system_id'],
            $dbRecord['v_payment_method_id'],
            $dbRecord['v_payment_transaction_id'],
            \Pley\Util\Time\DateTime::strToTime($dbRecord['transaction_at']),
            $dbRecord['transaction_op_user_id'],
            $dbRecord['base_amount'],
            $dbRecord['discount_amount'],
            $dbRecord['discount_type'],
            $dbRecord['discount_source_id']
        );
    }
}
