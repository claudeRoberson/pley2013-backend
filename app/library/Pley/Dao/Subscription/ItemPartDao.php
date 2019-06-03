<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Dao\Subscription;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Traits\CacheDaoTrait;
use \Pley\Entity\Subscription\ItemPart;

/** ♰
 * The <kbd>ItemPartDao</kbd> class provides implementation to interact with the SubscriptionItemPart
 * table in the DB and Cache.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.Subscription
 * @subpackage Dao
 */
class ItemPartDao extends AbstractDbDao implements DbDaoInterface, CacheDaoInterface
{
    use CacheDaoTrait; // Provides implementation for CacheDaoInterface

    /** @var string */
    protected $_tableName = 'item_part';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'item_id', 'name', 'type_item_part_id', 'is_need_mod', 'image',  
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /** ♰
     * @param \Pley\Entity\Subscription\Item $item
     * @return \Pley\Entity\Subscription\ItemPart[]
     */
    public function all(\Pley\Entity\Subscription\Item $item)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . "WHERE `item_id` = ?";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$item->getId()];

        $pstmt->execute($bindings);
        
        $resultSet    = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $resultLength = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        for ($i = 0; $i < $resultLength; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }
        
        return $resultSet;
    }
    
    /**
     * Return the <kbd>ItemPart</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\Subscription\ItemPart
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
     * Map an associative array DB record into a <kbd>ItemPart</kbd> Entity.
     * 
     * @param array $dbRecord
     * @return \Pley\Entity\Subscription\ItemPart
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new ItemPart(
            $dbRecord['id'], 
            $dbRecord['item_id'], 
            $dbRecord['name'], 
            $dbRecord['type_item_part_id'],
            $dbRecord['is_need_mod'] == 1,
            $dbRecord['image']
        );
    }
}
