<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Dao\User;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Traits\CacheDaoTrait;

/**
 * The <kbd>UserAddressDao</kbd> class provides implementation to interact with the User table in the DB and Cache.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.User
 * @subpackage Dao
 */
class UserAddressDao extends AbstractDbDao implements DbDaoInterface, CacheDaoInterface
{
    use CacheDaoTrait; // Provides implementation for CacheDaoInterface
    
    /** @var string */
    protected $_tableName = 'user_address';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    /**
     * Internal class to help with handling multiple cache keys (see bottom of this file)
     * @var _UserAddressDao_CacheKey
     */
    private $_cacheKey;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'user_id', 'street_1', 'street_2', 'phone', 'city', 'state', 'zip', 'country', 'shipping_zone_id', 'shipping_zone_usps', 'valid',
            'created_at', 'updated_at'
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
        
        // Create internal class to help with handling multiple cache keys
        $this->_cacheKey = new _UserAddressDao_CacheKey();
    }
    
    /**
     * Return the <kbd>UserAddress</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\User\UserAddress|null
     */
    public function find($id)
    {
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
        $userAddrEntity = $this->_toEntity($dbRecord);
        
        // Now that we have retrieved the Entity, let's add it to the cache
        $this->_cache->set($id, $userAddrEntity);
        
        return $userAddrEntity;
    }
    
    /**
     * Return a list of <kbd>UserAddress</kbd> entity objects for the supplied user.
     * @param int $userId
     * @return \Pley\Entity\User\UserAddress[]|null
     */
    public function findByUser($userId)
    {
        // Reading from cache first
        $cacheKey = $this->_cacheKey->getByUser($userId);
        if ($this->_cache->has($cacheKey)) {
            return $this->_cache->get($cacheKey);
        }
        
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . "WHERE `user_id` = ?";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$userId];
        
        $pstmt->execute($bindings);
        
        $resultSet   = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $resultCount = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        // Doing in place replacement to save some memory as the original records are not needed
        for ($i = 0; $i < $resultCount; $i++) {
            $dbRecord = $resultSet[$i];
            
            $resultSet[$i] = $this->_toEntity($dbRecord);
        }
        
        // Now that we have retrieved the Entity, let's add it to the cache
        $this->_cache->set($cacheKey, $resultSet);
        
        return $resultSet;
    }
    
    /**
     * Takes a <kbd>UserAddress</kbd> entity object and saves it into the Storage.
     * <p>Saving could imply adding or updating based on the entity supplied; if the entity has a
     * set ID, it will produce an Update, otherwise it will produce an Insert and the entity will
     * be updated with the newly generated id.</p>
     * <p>The method also does an entity type check to do some error validation before run time</p>
     * 
     * @param \Pley\Entity\User\UserAddress $userAddress The Entity object to save
     */
    public function save(\Pley\Entity\User\UserAddress $userAddress)
    {
        // Depending on the Entity ID value, either update or insert.
        if (empty($userAddress->getId())) {
            $this->_insert($userAddress);
        } else {
            $this->_update($userAddress);
        }
        
        $this->_cacheKey->clear($this->_cache, $userAddress);
    }
    
    /**
     * Removew the supplied user address from the storage.
     * @param \Pley\Entity\User\UserAddress $userAddress
     */
    public function delete(\Pley\Entity\User\UserAddress $userAddress)
    {
        $prepSql = "DELETE FROM `{$this->_tableName}` WHERE `id` = ?";
        $pstmt   = $this->_prepare($prepSql);
        
        $pstmt->execute([$userAddress->getId()]);
        $pstmt->closeCursor();
        
        $this->_cacheKey->clear($this->_cache, $userAddress);
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
        
        return new \Pley\Entity\User\UserAddress(
            $dbRecord['id'],
            $dbRecord['user_id'],
            $dbRecord['street_1'],
            $dbRecord['street_2'],
            $dbRecord['phone'],
            $dbRecord['city'],
            $dbRecord['state'],
            $dbRecord['country'],
            $dbRecord['zip'],
            $dbRecord['shipping_zone_id'],
            $dbRecord['shipping_zone_usps'],
            $dbRecord['valid'],
            \Pley\Util\DateTime::strToTime($dbRecord['created_at']),
            \Pley\Util\DateTime::strToTime($dbRecord['updated_at'])
        );
    }
    
    /**
     * Helper method to perform <kbd>UserAddress</kbd> inserts.
     * @param \Pley\Entity\User\UserAddress $userAddress
     */
    private function _insert(\Pley\Entity\User\UserAddress $userAddress)
    {
        $prepSql  = "INSERT INTO `{$this->_tableName}` ("
                  .     '`user_id`, '
                  .     '`street_1`, '
                  .     '`street_2`, '
                  .     '`phone`, '
                  .     '`city`, '
                  .     '`state`, '
                  .     '`zip`, '
                  .     '`country`, '
                  .     '`shipping_zone_id`, '
                  .     '`shipping_zone_usps`, '
                  .     '`valid`, '
                  .     '`created_at` '
                  . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $pstmt    = $this->_prepare($prepSql);
        
        // converting the Object to a JSON String
        $bindings = [
            $userAddress->getUserId(),
            $userAddress->getStreet1(),
            $userAddress->getStreet2(),
            $userAddress->getPhone(),
            $userAddress->getCity(),
            $userAddress->getState(),
            $userAddress->getZipCode(),
            $userAddress->getCountry(),
            $userAddress->getShippingZoneId(),
            $userAddress->getUspsShippingZoneId(),
            $userAddress->getIsValid()
        ];
        
        $pstmt->execute($bindings);
        
        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $userAddress->setId($id);
    }
    
    /**
     * Helper method to perform <kbd>UserAddress</kbd> updates
     * @param \Pley\Entity\User\UserAddress $userAddress
     */
    private function _update(\Pley\Entity\User\UserAddress $userAddress)
    {
        $prepSql  = "UPDATE `{$this->_tableName}` "
                  . 'SET `street_1` = ?, '
                  .     '`street_2` = ?, '
                  .     '`phone` = ?, '
                  .     '`city` = ?, '
                  .     '`state` = ?, '
                  .     '`zip` = ?, '
                  .     '`country` = ?, '
                  .     '`shipping_zone_id` = ?, '
                  .     '`shipping_zone_usps` = ?, '
                  .     '`valid` = ? '
                  . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);
        
        $bindings = [
            $userAddress->getStreet1(),
            $userAddress->getStreet2(),
            $userAddress->getPhone(),
            $userAddress->getCity(),
            $userAddress->getState(),
            $userAddress->getZipCode(),
            $userAddress->getCountry(),
            $userAddress->getShippingZoneId(),
            $userAddress->getUspsShippingZoneId(),
            $userAddress->getIsValid(),
            // WHERE bindings
            $userAddress->getId(),
        ];
        
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
        
        $userAddress->setUpdatedAt(time());
    }
}





/***************************************************************************************************
 * Class provides internal support to handle cache keys.
 * @author Alejandro Salazar (alejandros@pley.com)
 **************************************************************************************************/
class _UserAddressDao_CacheKey
{
    public function getByUser($userId)
    {
        return 'uid:' . $userId;
    }
    
    public function clear(\Pley\Cache\CacheInterface $cache, \Pley\Entity\User\UserAddress $userAddr)
    {
        $cache->delete($userAddr->getId());
        $cache->delete($this->getByUser($userAddr->getUserId()));
    }
}

