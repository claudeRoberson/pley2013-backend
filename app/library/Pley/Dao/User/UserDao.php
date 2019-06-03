<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Dao\User;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Traits\CacheDaoTrait;

/**
 * The <kbd>UserDao</kbd> class provides implementation to interact with the User table in the DB and Cache.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.User
 * @subpackage Dao
 */
class UserDao extends AbstractDbDao implements DbDaoInterface, CacheDaoInterface
{
    use CacheDaoTrait; // Provides implementation for CacheDaoInterface
    
    private static $FIELD_ID    = 'id';
    private static $FIELD_EMAIL = 'email';
    private static $FIELD_FBTOKEN = 'fb_token';
    
    
    /** @var string */
    protected $_tableName = 'user';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    /**
     * Internal class to help with handling multiple cache keys (see bottom of this file)
     * @var _UserDao_CacheKey
     */
    private $_cacheKey;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'first_name', 'last_name', 'email', 'country',
            'password', 'fb_token',
            'is_verified',
            'v_payment_system_id', 'v_payment_account_id',
            'default_user_payment_method_id',
            'is_receive_newsletter',
            'referrer',
            'created_at',
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
        
        // Create internal class to help with handling multiple cache keys
        $this->_cacheKey = new _UserDao_CacheKey();
    }
    
    /**
     * Return the <kbd>User</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\User\User|null
     */
    public function find($id)
    {
        $cacheKey = $id;
        return $this->_findByField(self::$FIELD_ID, $id, $cacheKey);
    }
    
    /**
     * Return the <kbd>User</kbd> entity for the supplied email or null if not found.
     * @param string $email
     * @return \Pley\Entity\User\User|null
     */
    public function findByEmail($email)
    {
        $cacheKey = $this->_cacheKey->getByEmail($email);
                
        return $this->_findByField(self::$FIELD_EMAIL, $email, $cacheKey);
    }
    
    /**
     * Return the <kbd>User</kbd> entity for the supplied Facebook Token or null if not found.
     * @param string $fbToken
     * @return \Pley\Entity\User\User|null
     */
    public function findByFbToken($fbToken)
    {
        return $this->_findByField(self::$FIELD_FBTOKEN, $fbToken, null);
    }
    
    /**
     * Returns a list of all <kbd>User</kbd> entities.
     * @return \Pley\Entity\User\User[]
     */
    public function all()
    {
        // Reading from cache first
        $cacheKey = $this->_cacheKey->getAll();
        if ($this->_cache->has($cacheKey)) {
            return $this->_cache->get($cacheKey);
        }
        
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` ORDER BY `id` DESC";
        $bindings = [];
        
        $entityList = $this->_retrieveEntityList($prepSql, $bindings);
        
        // Now that we have retrieved the Entity, let's add it to the cache
        $this->_cache->set($cacheKey, $entityList);
        
        return $entityList;
    }
    
    /**
     * Returns a list of all <kbd>User</kbd> entities for the supplied range.
     * @param int $pageStart    Page to retrieve (starting from page 0)
     * @param int $itemsPerPage Number of items to include per page requested.
     * @return \Pley\Entity\User\User[]
     */
    public function range($pageStart, $itemsPerPage)
    {
        $itemStart = $pageStart * $itemsPerPage;
        
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'ORDER BY `id` DESC '
                  . "LIMIT {$itemStart}, {$itemsPerPage} ";
        $bindings = [];
        
        $entityList = $this->_retrieveEntityList($prepSql, $bindings);
        
        return $entityList;
    }
    
    /** ♰
     * @param int $tzMinutesOffset
     * @return \Pley\Entity\User\User
     */
    public function getRecentSignupList($tzMinutesOffset)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . "WHERE TIMESTAMPADD(MINUTE, ?, `created_at`) > DATE(TIMESTAMPADD(MINUTE, ?, NOW()))"
                  . "ORDER BY `id` DESC";
        $pstmt    = $this->_prepare($prepSql);
        $pstmt->execute([$tzMinutesOffset, $tzMinutesOffset]);
        
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $count = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        for ($i = 0; $i< $count; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }
        
        return $resultSet;
    }
    
    /**
     * Takes a <kbd>User</kbd> entity object and saves it into the Storage.
     * <p>Saving could imply adding or updating based on the entity supplied; if the entity has a
     * set ID, it will produce an Update, otherwise it will produce an Insert and the entity will
     * be updated with the newly generated id.</p>
     * <p>The method also does an entity type check to do some error validation before run time</p>
     * 
     * @param \Pley\Entity\User\User $user The Entity object to save
     */
    public function save(\Pley\Entity\User\User $user)
    {
        // Depending on the Entity ID value, either update or insert.
        if (empty($user->getId())) {
            $this->_insert($user);
        } else {
            $this->_update($user);
        }
        
        $this->_cacheKey->clear($this->_cache, $user);
    }
    
    /**
     * Return a list of <kbd>User</kbd> entity objects that contain the supplied value.
     * @param int|string $value
     * @return \Pley\Entity\User\User[]
     */
    public function findLike($value)
    {
        $likeValue = '%' . $value . '%';
        
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `id` = ? '
                  .    'OR `email` LIKE ? '
                  .    'OR `first_name` LIKE ? '
                  .    'OR `last_name` LIKE ? ';
        $bindings = [
            $value,     // For `id`
            $likeValue, // For `email`
            $likeValue, // For `first_name`
            $likeValue  // For `last_name`
        ];
        
        return $this->_retrieveEntityList($prepSql, $bindings);
    }
    
    /**
     * Map an associative array DB record into a <kbd>User</kbd> Entity.
     * 
     * @param array $dbRecord
     * @return \Pley\Entity\User\User
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new \Pley\Entity\User\User(
            $dbRecord['id'],
            $dbRecord['first_name'],
            $dbRecord['last_name'],
            $dbRecord['email'],
            $dbRecord['country'],
            $dbRecord['password'],
            $dbRecord['fb_token'],
            $dbRecord['is_verified'] == 1,
            $dbRecord['v_payment_system_id'],
            $dbRecord['v_payment_account_id'],
            $dbRecord['default_user_payment_method_id'],
            $dbRecord['is_receive_newsletter'] == 1,
            $dbRecord['referrer'],
            \Pley\Util\Time\DateTime::strToTime($dbRecord['created_at'])
        );
    }
    
    /**
     * Helper method to return the <kbd>User</kbd> entity for the supplied value on the given field.
     * @param string     $fieldName
     * @param mixed      $value
     * @param string|int $cacheKey
     * @return \Pley\Entity\User\User|null
     */
    private function _findByField($fieldName, $value, $cacheKey)
    {
        // Reading from cache first
        if ($this->_cache->has($cacheKey)) {
            return $this->_cache->get($cacheKey);
        }
        
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . "WHERE `{$fieldName}` = ?";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$value];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $userEntity = $this->_toEntity($dbRecord);
        
        // Now that we have retrieved the Entity, let's add it to the cache
        $this->_cache->set($cacheKey, $userEntity);
        
        return $userEntity;
    }
    
    /**
     * Helper method to return a list of <kbd>User</kbd> entity objects for the supplied query and
     * related bindings.
     * @param string $querySql
     * @param array  $bindings
     * @return \Pley\Entity\User\User[]
     */
    private function _retrieveEntityList($querySql, $bindings)
    {
        $pstmt = $this->_prepare($querySql);
        $pstmt->execute($bindings);
        
        $resultSet   = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $resultCount = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        // Doing in place replacement to save some memory as the original records are not needed
        for ($i = 0; $i < $resultCount; $i++) {
            $dbRecord = $resultSet[$i];
            
            $resultSet[$i] = $this->_toEntity($dbRecord);
        }
        
        return $resultSet;
    }
    
    /**
     * Helper method to perform <kbd>User</kbd> inserts.
     * @param \Pley\Entity\User\User $user
     */
    private function _insert(\Pley\Entity\User\User $user)
    {
        $prepSql  = "INSERT INTO `{$this->_tableName}` ("
                  .     '`first_name`, '
                  .     '`last_name`, '
                  .     '`email`, '
                  .     '`country`, '
                  .     '`password`, '
                  .     '`fb_token`, '
                  .     '`v_payment_system_id`, '
                  .     '`v_payment_account_id`, '
                  .     '`is_verified`, '
                  .     '`created_at` '
                  . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $pstmt    = $this->_prepare($prepSql);
        
        // converting the Object to a JSON String
        $bindings = [
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getCountry(),
            $user->getPassword(),
            $user->getFbToken(),
            $user->getVPaymentSystemId(),
            $user->getVPaymentAccountId(),
            $user->isVerified() ? 1: 0,
        ];
        
        $pstmt->execute($bindings);
        
        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $user->setId($id);
    }
    
    /**
     * Helper method to perform <kbd>User</kbd> updates
     * @param \Pley\Entity\User\User $user
     */
    private function _update(\Pley\Entity\User\User $user)
    {
        $prepSql  = "UPDATE `{$this->_tableName}` "
                  . 'SET `first_name` = ?, '
                  .     '`last_name` = ?, '
                  .     '`email` = ?, '
                  .     '`country` = ?, '
                  .     '`password` = ?, '
                  .     '`fb_token` = ?, '
                  .     '`is_verified` = ?, '
                  .     '`v_payment_system_id` = ?, '
                  .     '`v_payment_account_id` = ?, '
                  .     '`default_user_payment_method_id` = ?, '
                  .     '`is_receive_newsletter` = ?, '
                  .     '`referrer` = ? '
                  . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getCountry(),
            $user->getPassword(),
            $user->getFbToken(),
            $user->isVerified()? 1:0 ,
            $user->getVPaymentSystemId(),
            $user->getVPaymentAccountId(),
            $user->getDefaultPaymentMethodId(),
            $user->isReceiveNewsletter()? 1 : 0,
            $user->getReferrer(),

            // WHERE bindings
            $user->getId(),
        ];
        
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }
}





/***************************************************************************************************
 * Class provides internal support to handle cache keys.
 * @author Alejandro Salazar (alejandros@pley.com)
 **************************************************************************************************/
class _UserDao_CacheKey
{
    public function getByEmail($email)
    {
        return 'm:' . $email;
    }
    
    public function getAll()
    {
        return 'all';
    }
    
    public function clear(\Pley\Cache\CacheInterface $cache, \Pley\Entity\User\User $user)
    {
        $cache->delete($user->getId());
        $cache->delete($this->getByEmail($user->getEmail()));
        $cache->delete($this->getAll());
    }
}
