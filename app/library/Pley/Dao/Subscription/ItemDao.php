<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Dao\Subscription;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Traits\CacheDaoTrait;
use \Pley\Entity\Subscription\Item;

/**
 * The <kbd>ItemDao</kbd> class provides implementation to interact with the SubscriptionItem
 * table in the DB and Cache.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.Subscription
 * @subpackage Dao
 */
class ItemDao extends AbstractDbDao implements DbDaoInterface, DaoInterface, CacheDaoInterface
{
    use CacheDaoTrait; // Provides implementation for CacheDaoInterface

    /** @var string */
    protected $_tableName = 'item';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'name', 'description', 'length_cm', 'width_cm', 'height_cm', 'weight_gr'
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /**
     * Return the <kbd>Item</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\Subscription\Item
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
     * Return the <kbd>Item</kbd> collection for the supplied subscriptionId
     * or null if not found.
     * @param int $subscriptionId
     * @return \Pley\Entity\Subscription\Item[]
     */
    public function findBySubscriptionId($subscriptionId)
    {
        $prepSql  = "SELECT `{$this->_tableName}`.* FROM `{$this->_tableName}` LEFT JOIN 
        `subscription_item` ON `{$this->_tableName}`.`id` = `subscription_item`.`item_id`"
            . " WHERE `subscription_item`.`subscription_id` = ?";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$subscriptionId];

        $pstmt->execute($bindings);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        foreach($resultSet as $idx => $dbRecord) {
            $resultSet[$idx] = $this->_toEntity($dbRecord);
        }
        return $resultSet;
    }

    /**
     * Map an associative array DB record into a <kbd>Item</kbd> Entity.
     * 
     * @param array $dbRecord
     * @return \Pley\Entity\Subscription\Item
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new Item(
            $dbRecord['id'], 
            $dbRecord['name'], 
            $dbRecord['description'],
            $dbRecord['length_cm'],
            $dbRecord['width_cm'],
            $dbRecord['height_cm'],
            $dbRecord['weight_gr']
        );
    }
}
