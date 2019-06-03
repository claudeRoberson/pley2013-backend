<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Dao\User;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;

/**
 * The <kbd>UserPasswordResetDao</kbd> class provides implementation to interact with the 
 * User Password Reset table in the storage.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.User
 * @subpackage Dao
 */
class UserPasswordResetDao extends AbstractDbDao implements DbDaoInterface, DaoInterface
{
    /** @var string */
    protected $_tableName = 'user_password_reset';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'token', 'is_redeemed', 'user_id', 'request_count',
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /**
     * Return the <kbd>UserPasswordReset</kbd> entity for the supplied token or null if not found.
     * @param string $token
     * @return \Pley\Entity\User\UserPasswordReset|null
     */
    public function find($token)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `token` = ?';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$token];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        
        $entity = $this->_toEntity($dbRecord);
        return $entity;
    }
    
    /**
     * Return the <kbd>UserPasswordReset</kbd> entity for the supplied used id or null if not found.
     * @param int $userId
     * @return \Pley\Entity\User\UserPasswordReset|null
     */
    public function findByUser($userId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `user_id` = ? '
                  . 'AND `is_redeemed` = 0 ';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$userId];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        
        $entity = $this->_toEntity($dbRecord);
        return $entity;
    }
    
    /**
     * Takes a <kbd>UserPasswordReset</kbd> entity object and saves it into the Storage.
     * <p>Saving could imply adding or updating based on the entity supplied; if the entity has a
     * set ID, it will produce an Update, otherwise it will produce an Insert and the entity will
     * be updated with the newly generated id.</p>
     * <p>The method also does an entity type check to do some error validation before run time</p>
     * 
     * @param \Pley\Entity\User\UserPasswordReset $userPassReset The Entity object to save
     */
    public function save(\Pley\Entity\User\UserPasswordReset $userPassReset)
    {
        // Depending on the Entity ID value, either update or insert.
        if (empty($userPassReset->getId())) {
            $this->_insert($userPassReset);
        } else {
            $this->_update($userPassReset);
        }
    }
    
    /**
     * Map an associative array DB record into a <kbd>UserPasswordReset</kbd> Entity.
     * 
     * @param array $dbRecord
     * @return \Pley\Entity\User\UserPasswordReset
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new \Pley\Entity\User\UserPasswordReset(
            $dbRecord['id'],
            $dbRecord['token'],
            $dbRecord['is_redeemed'] == 1,
            $dbRecord['user_id'],
            $dbRecord['request_count']
        );
    }
    
    /**
     * Helper method to perform <kbd>UserPasswordReset</kbd> inserts.
     * @param \Pley\Entity\User\UserPasswordReset $userPassReset
     */
    private function _insert(\Pley\Entity\User\UserPasswordReset $userPassReset)
    {
        $prepSql  = "INSERT INTO `{$this->_tableName}` ("
                  .     '`token`, '
                  .     '`is_redeemed`, '
                  .     '`user_id`, '
                  .     '`request_count`, '
                  .     '`created_at` '
                  . ') VALUES (?, 0, ?, 1, NOW())';
        $pstmt    = $this->_prepare($prepSql);
        
        // converting the Object to a JSON String
        $bindings = [
            $userPassReset->getToken(),
            $userPassReset->getUserId(),
        ];
        
        $pstmt->execute($bindings);
        
        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $userPassReset->setId($id);
    }
    
    /**
     * Helper method to perform <kbd>UserPasswordReset</kbd> updates
     * @param \Pley\Entity\User\UserPasswordReset $userPassReset
     */
    private function _update(\Pley\Entity\User\UserPasswordReset $userPassReset)
    {
        $prepSql  = "UPDATE `{$this->_tableName}` "
                  . 'SET `is_redeemed` = ?, '
                  .     '`request_count` = ? '
                  . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [
            $userPassReset->isRedeemed()? 1 : 0,
            $userPassReset->getRequestCount(),
            
            // WHERE bindings
            $userPassReset->getId(),
        ];
        
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }
}
