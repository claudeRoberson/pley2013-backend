<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Dao\Payment;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;

/**
 * The <kbd>UserPaymentMethodDao</kbd> class provides implementation to interact with the User table
 * in the DB and Cache.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.User
 * @subpackage Dao
 */
class UserPaymentMethodDao extends AbstractDbDao implements DbDaoInterface
{
    /** @var boolean Constant used to indicate that even hidden  */
    const INCLUDE_HIDDEN = true;
    
    /** @var string */
    protected $_tableName = 'user_payment_method';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;       
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'user_id', 'v_payment_system_id', 'v_payment_method_id', 'is_visible',
            'created_at', 'updated_at', 
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /**
     * Return the <kbd>UserPaymentMethod</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\Payment\UserPaymentMethod|null
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
     * Return all Payment Methods associated to a given user.
     * @param int     $userId
     * @param boolean $includeHidden (Optional)<br/>Default <kbd>FALSE</kbd>.</br> By default it
     *      only returns the visible cards, but if all are needed (either for Customer Service or
     *      to re-enable a hidden one), set the value to <kbd>TRUE</kbd> or <kbd>::INCLUDE_HIDDEN</kbd>.
     * @param $paymentSystemId
     * @return \Pley\Entity\Payment\UserPaymentMethod[]
     */
    public function findByUser($userId, $includeHidden = false, $paymentSystemId = \Pley\Enum\PaymentSystemEnum::STRIPE)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `user_id` = ? AND `v_payment_system_id` = ? ';
        
        if (!$includeHidden) {
            $prepSql .= 'AND `is_visible` = 1 ';
        }
        
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$userId, $paymentSystemId];

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
     * Returns a PaymentMethod for a user given the vendor IDs
     * @param int    $userId
     * @param int    $vPaymentSystemId
     * @param string $vPaymentMethodId
     * @return \Pley\Entity\Payment\UserPaymentMethod
     */
    public function findByVendorId($userId, $vPaymentSystemId, $vPaymentMethodId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `user_id` = ? '
                  .   'AND `v_payment_system_id` = ? '
                  .   'AND `v_payment_method_id` = ? ';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [
            $userId,
            $vPaymentSystemId,
            $vPaymentMethodId,
        ];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $entity   = $this->_toEntity($dbRecord);

        return $entity;
    }
    
    /**
     * Adds or updates the supplied PaymentMethod into the storage.
     * @param \Pley\Entity\Payment\UserPaymentMethod $userPaymentMethod
     */
    public function save(\Pley\Entity\Payment\UserPaymentMethod $userPaymentMethod)
    {
        // Depending on the Entity ID value, either update or insert.
        if (empty($userPaymentMethod->getId())) {
            $this->_insert($userPaymentMethod);
        } else {
            $this->_update($userPaymentMethod);
        }
    }
    
    // ---------------------------------------------------------------------------------------------
    // SPECIAL METHOD TO DELETE A CARD, NOT JUST HIDE IT, IT IS NOT VISIBLE AS IT NOT INTENDED FOR
    // ANY USE OTHER THAN WHEN ADDING THE VERY FIRST CARD FOR THE VERY FIRST PAID SUBSCRIPTION FAILS
    protected function _delete(\Pley\Entity\Payment\UserPaymentMethod $userPaymentMethod)
    {
        $prepSql  = "DELETE FROM `{$this->_tableName}` "
                  . 'WHERE `id` = ? ';
        $pstmt    = $this->_prepare($prepSql);
        $pstmt->execute([$userPaymentMethod->getId()]);
        $pstmt->closeCursor();
    }
    
    /**
     * Map an associative array DB record into a <kbd>UserAddress</kbd> Entity.
     * 
     * @param array $dbRecord
     * @return \Pley\Entity\User\UserAddress
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new \Pley\Entity\Payment\UserPaymentMethod(
            $dbRecord['id'],
            $dbRecord['user_id'],
            $dbRecord['v_payment_system_id'],
            $dbRecord['v_payment_method_id'],
            $dbRecord['is_visible'] == 1,
            \Pley\Util\DateTime::strToTime($dbRecord['created_at']), 
            \Pley\Util\DateTime::strToTime($dbRecord['updated_at'])
        );
    }
    
    private function _insert(\Pley\Entity\Payment\UserPaymentMethod $userPaymentMethod)
    {
        $prepSql  = "INSERT INTO `{$this->_tableName}` ("
                  .     '`user_id`, '
                  .     '`v_payment_system_id`, '
                  .     '`v_payment_method_id`, '
                  .     '`is_visible`, '
                  .     '`created_at` '
                  . ') VALUES (?, ?, ?, 1, NOW())';
        $pstmt    = $this->_prepare($prepSql);
        
        // converting the Object to a JSON String
        $bindings = [
            $userPaymentMethod->getUserId(),
            $userPaymentMethod->getVPaymentSystemId(),
            $userPaymentMethod->getVPaymentMethodId(),
        ];
        
        $pstmt->execute($bindings);
        
        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $userPaymentMethod->setId($id);
    }
    
    private function _update(\Pley\Entity\Payment\UserPaymentMethod $userPaymentMethod)
    {
        $prepSql  = "UPDATE `{$this->_tableName}` "
                  . 'SET `is_visible` = ?, '
                  . '    `updated_at` = CURRENT_TIMESTAMP() '
                  . 'WHERE `id` = ? ';
        $pstmt    = $this->_prepare($prepSql);
        
        // converting the Object to a JSON String
        $bindings = [
            $userPaymentMethod->isVisible()? 1 : 0,
            
            // WHERE bindings
            $userPaymentMethod->getId()
        ];
        
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
        
        $userPaymentMethod->setUpdatedAt(time());
    }

}
