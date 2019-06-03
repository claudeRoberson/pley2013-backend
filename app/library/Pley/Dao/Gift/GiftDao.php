<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Dao\Gift;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Traits\CacheDaoTrait;

/** ♰
 * The <kbd>GiftDao</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class GiftDao extends AbstractDbDao implements DbDaoInterface, CacheDaoInterface
{
    use CacheDaoTrait; // Provides implementation for CacheDaoInterface

    /** @var string */
    protected $_tableName = 'gift';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'token', 'is_redeemed', 'subscription_id', 'gift_price_id',
            'v_payment_system_id', 'v_payment_transaction_id',
            'from_first_name', 'from_last_name', 'from_email',
            'to_first_name', 'to_last_name', 'to_email',
            'message', 'is_email_sent', 'notify_date', 
            'redeemed_at', 'redeem_user_id', 'created_at',
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /**
     * Return the <kbd>Gift</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\Gift\Gift
     */
    public function find($id)
    {
        // Reading from cache first
        if ($this->_cache->has($id)) {
            return $this->_cache->get($id);
        }
        
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . "WHERE `id` = ?";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$id];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $entity = $this->_toEntity($dbRecord);
        
        // Now that we have retrieved the Entity, let's add it to the cache
        $this->_cache->set($id, $entity);
        
        return $entity;
    }
    
    /**
     * Return the <kbd>Gift</kbd> entity for the supplied token or null if not found.
     * @param int $token
     * @return \Pley\Entity\Gift\Gift
     */
    public function findByToken($token)
    {
        // Reading from cache first
        if ($this->_cache->has($token)) {
            return $this->_cache->get($token);
        }
        
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . "WHERE `token` = ?";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$token];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $entity = $this->_toEntity($dbRecord);
        
        // Now that we have retrieved the Entity, let's add it to the cache
        $this->_cache->set($token, $entity);
        
        return $entity;
    }
    
    /**
     * Return a list of Gift IDs that match the supplied input.
     * @return int[]
     */
    public function csSearchId($input)
    {
        if (strlen($input) < 3) {
            return [];
        }
        
        $prepSql = "SELECT `id` FROM `{$this->_tableName}` "
                 . 'WHERE `token` LIKE CONCAT("%", ?, "%") '
                 .    'OR `from_first_name` LIKE CONCAT("%", ?, "%") '
                 .    'OR `from_last_name` LIKE CONCAT("%", ?, "%") '
                 .    'OR `from_email` LIKE CONCAT("%", ?, "%") '
                 .    'OR `to_first_name` LIKE CONCAT("%", ?, "%") '
                 .    'OR `to_last_name` LIKE CONCAT("%", ?, "%") '
                 .    'OR `to_email` LIKE CONCAT("%", ?, "%") '
                 .    'OR `message` LIKE CONCAT("%", ?, "%") ';
        $pstmt    = $this->_prepare($prepSql);
        $pstmt->execute(array_fill(0, 8, $input));
        
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $resultSet[$i]['id'];
        }
        
        return $resultSet;
    }
    
    public function save(\Pley\Entity\Gift\Gift $gift)
    {
        // Depending on the Entity ID value, either update or insert.
        if (empty($gift->getId())) {
            $this->_insert($gift);
        } else {
            $this->_update($gift);
        }
    }   
    
    /**
     * Map an associative array DB record into a <kbd>Gift</kbd> Entity.
     * 
     * @param array $dbRecord
     * @return \Pley\Entity\Gift\Gift
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new \Pley\Entity\Gift\Gift(
            $dbRecord['id'],
            $dbRecord['token'],
            $dbRecord['is_redeemed'],
            $dbRecord['subscription_id'],
            $dbRecord['gift_price_id'],
            $dbRecord['v_payment_system_id'],
            $dbRecord['v_payment_transaction_id'],
            $dbRecord['from_first_name'],
            $dbRecord['from_last_name'],
            $dbRecord['from_email'],
            $dbRecord['to_first_name'],
            $dbRecord['to_last_name'],
            $dbRecord['to_email'],
            $dbRecord['message'],
            $dbRecord['is_email_sent'] == 1,
            $dbRecord['notify_date'],
            \Pley\Util\DateTime::strToTime($dbRecord['redeemed_at']),
            $dbRecord['redeem_user_id'],
            \Pley\Util\DateTime::strToTime($dbRecord['created_at'])
        );
    }
    
    private function _insert(\Pley\Entity\Gift\Gift $gift)
    {
        $prepSql  = "INSERT INTO `{$this->_tableName}` ("
                  .     '`token`, '
                  .     '`is_redeemed`, '
                  .     '`subscription_id`, '
                  .     '`gift_price_id`, '
                  .     '`from_first_name`, '
                  .     '`from_last_name`, '
                  .     '`from_email`, '
                  .     '`to_first_name`, '
                  .     '`to_last_name`, '
                  .     '`to_email`, '
                  .     '`message`, '
                  .     '`is_email_sent`, '
                  .     '`notify_date`, '
                  .     '`created_at` '
                  . ') VALUES (?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW())';
        $pstmt    = $this->_prepare($prepSql);
        
        // converting the Object to a JSON String
        $bindings = [
            $gift->getToken(),
            $gift->getSubscriptionId(),
            $gift->getGiftPriceId(),
            $gift->getFromFirstName(),
            $gift->getFromLastName(),
            $gift->getFromEmail(),
            $gift->getToFirstName(),
            $gift->getToLastName(),
            $gift->getToEmail(),
            $gift->getMessage(),
            \Pley\Util\DateTime::date($gift->getNotifyDate(), 'Y-m-d')
        ];
        
        $pstmt->execute($bindings);
        
        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $gift->setId($id);
    }
    
    private function _update(\Pley\Entity\Gift\Gift $gift)
    {
        $prepSql  = "UPDATE `{$this->_tableName}` "
                  . 'SET `is_redeemed` = ?, '
                  .     '`redeemed_at` = ?, '
                  .     '`redeem_user_id` = ?, '
                  .     '`v_payment_system_id` = ?, '
                  .     '`v_payment_transaction_id` = ?, '
                  .     '`is_email_sent` = ? '
                  . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);
        
        $bindings = [
            $gift->isRedeemed()? 1 : 0,
            \Pley\Util\Time\DateTime::date($gift->getRedeemedAt()),
            $gift->getRedeemUserId(),
            $gift->getVPaymentSystemId(),
            $gift->getVPaymentTransactionId(),
            $gift->isEmailSent()? 1 : 0,
            
            // WHERE bindings
            $gift->getId(),
        ];
        
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }
}
