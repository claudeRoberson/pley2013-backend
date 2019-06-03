<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Dao\Operations;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Entity\Operations\OperationsUser;

/** ♰
 * The <kbd>BackendUserDao</kbd> class provides implementation to interact with the Backend User
 * table in the DB.
 * <p>Since this DAO is designed for Warehouse use, no need to support cache</p>
 *
 * @author Igor Shvartsev (igor.shvartsev@gmail.com)
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Dao.Backend
 * @subpackage Dao
 */
class OperationsUserDao extends AbstractDbDao implements DaoInterface, DbDaoInterface
{
    /** @var string */
    protected $_tableName = 'op_user';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'username', 'first_name', 'last_name', 'email' , 'password', 'role', 'warehouse_id', 
            'activated', 'banned', 'created_at'
        ]);
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /**
     * Return the <kbd>BackendUser</kbd> entity for the supplied id or null if not found.
     * 
     * @param int $id
     * @return \Pley\Entity\Backend\BackendUser|null
     */
    public function find($id)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$id];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $backendUserEntity = $this->_toEntity($dbRecord);
        
        return  $backendUserEntity;
    }
    
    /**
     * Return all <kbd>BackendUser<kbd> entity
     * @return \Pley\Entity\Backend\BackendUser[]
     */
    public function all()
    {
        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}`";
        $pstmt   = $this->_prepare($prepSql);

        $pstmt->execute();

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        
        $backendUserList = [];
        foreach ($resultSet as $dbRecord) {
            $backendUserEntity = $this->_toEntity($dbRecord);
            $backendUserList[] = $backendUserEntity;
        }
        return $backendUserList;
    }
    
    /**
     * Return the <kbd>BackendUser</kbd> entity list for the supplied userId .
     * 
     * @param int $email
     * @return \Pley\Entity\Backend\BackendUser
     */
    public function findByEmail($email)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `email` = ?';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$email];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $backendUserEntity = $this->_toEntity($dbRecord);
          
        return  $backendUserEntity;
    }

    /**
     * Return the <kbd>BackendUser</kbd> entity for the supplied username.
     *
     * @param string $username
     * @return \Pley\Entity\Backend\BackendUser
     */
    public function findByUsername($username)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `username` = ?';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$username];

        $pstmt->execute($bindings);

        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $backendUserEntity = $this->_toEntity($dbRecord);

        return  $backendUserEntity;
    }

    /**
    * Return the <kbd>BackendUser</kbd> entity list
    * 
    * @param int $pageId
    * @param int $perPage
    * @return \Pley\Entity\Backend\BackendUser[] 
    */
    public function getList($pageId, $perPage = 40)
    {
        $startFrom = $perPage * $pageId;

        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` ";
        $prepSql .= " ORDER BY `id` DESC LIMIT {$startFrom}, {$perPage}";

        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute();

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        $backendUserList = [];
        foreach ($resultSet as $dbRecord) {
            $backendUserEntity = $this->_toEntity($dbRecord);
            $backendUserList[] = $backendUserEntity;
        }

        return $backendUserList; 
    }
    
    /**
    * Search entries by query
    * 
    * @param string/numeric $query
    * @param int $limit
    * @return \Pley\Entity\Backend\BackendUser[]
    */
    public function search($query, $limit = 40)
    {
        $limit = (int)$limit;
        $queryLike = "%{$query}%";
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . "WHERE `id` = ?"
                  . " OR `username`  LIKE ?"
                  . " OR `first_name` LIKE ?"
                  . " OR `last_name`  LIKE ?"
                  . " OR `email`  LIKE ?"
                  . " ORDER BY `id` DESC LIMIT {$limit}";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$query, $queryLike, $queryLike, $queryLike, $queryLike];
        $pstmt->execute($bindings);
        
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        
        $backendUserList = [];
        foreach ($resultSet as $dbRecord) {
            $backendUserEntity = $this->_toEntity($dbRecord);
            $backendUserList[]  = $backendUserEntity;
        }
        
        return $backendUserList;  
        
    }
    
    /**
     * Takes an <kbd>BackendUser</kbd> entity object and saves it into the Storage.
     * <p>Saving could imply adding or updating based on the entity supplied; if the entity has a
     * set ID, it will produce an Update, otherwise it will produce an Insert and the entity will
     * be updated with the newly generated id.</p>
     * <p>The method also does an entity type check to do some error validation before run time 
     * 
     * @param \Pley\Entity\Backend\BackendUser $backendUser The Entity object to save
     */
    public function save(BackendUser $backendUser)
    {   
        // because we use original ID from Lego we are just checking part availability in DB 
        // to be inserted or updated
        if (!$backendUser->getId()) {
            $this->_insert($backendUser);
        } else {
            $this->_update($backendUser);
        }
    }
    
    /**
     * Delete Entry
     * 
     * @param int $id
     */
    public function delete($id)
    {
        $prepSql  = "DELETE FROM `{$this->_tableName}` "
                  . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$id];
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }
    
    /**
     * Map an associative array DB record into an Entity.
     * 
     * @param array $dbRecord
     * @return \Pley\Entity\\Backend\BackendUser
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new OperationsUser(
             $dbRecord['id'],
             $dbRecord['username'], 
             $dbRecord['first_name'], 
             $dbRecord['last_name'], 
             $dbRecord['email'], 
             $dbRecord['password'], 
             $dbRecord['role'],
             $dbRecord['warehouse_id'],
             $dbRecord['activated'],
             $dbRecord['banned'],
             $dbRecord['created_at']
        );
    }

    /**
     * Helper method to perform BackendUser inserts
     * @param \Pley\Entity\Backend\BackendUser $backendUser
     */
    private function _insert(BackendUser $backendUser)
    {
        $prepSql  = "INSERT INTO `{$this->_tableName}` ("
                  .    '`username`, '
                  .    '`first_name`, '
                  .    '`last_name`, '
                  .    '`email`, '
                  .    '`password`, '
                  .    '`role`, '
                  .    '`activated`, '
                  .    '`banned`, '
                  .    '`warehouse_id`, '
                  .    '`created_at`'
                  . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [
            $backendUser->getUsername(),
            $backendUser->getFirstName(),
            $backendUser->getLastName(),
            $backendUser->getEmail(),
            $backendUser->getPassword(),
            $backendUser->getRole(),
            (int)$backendUser->isActivated(),
            (int)$backendUser->isBanned(),
            $backendUser->getWarehouseId(),
        ];

        $pstmt->execute($bindings);
        
        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $backendUser->setId($id);
    }
    
    /**
     * Helper method to perform BackendUser updates
     * @param \Pley\Entity\Backend\BackendUser $backendUser
     */
    private function _update(BackendUser $backendUser)
    {
        $prepSql  = "UPDATE `{$this->_tableName}` "
                  . 'SET `username` = ?, '
                  .     '`first_name` = ?, '
                  .     '`last_name` = ?, '
                  .     '`email` = ?, '
                  .     '`password` = ?, '
                  .     '`role` = ?, '
                  .     '`activated` = ?, '
                  .     '`banned` = ?, '
                  .     '`warehouse_id` = ? '
                  . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [
            $backendUser->getUsername(),
            $backendUser->getFirstName(),
            $backendUser->getLastName(),
            $backendUser->getEmail(),
            $backendUser->getPassword(),
            $backendUser->getRole(),
            (int) $backendUser->isActivated(),
            (int) $backendUser->isBanned(),
            $backendUser->getWarehouseId(),
            $backendUser->getId(),
        ];

        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }
}
