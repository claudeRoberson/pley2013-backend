<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Dao\Gift;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Traits\CacheDaoTrait;

/** ♰
 * The <kbd>GiftUserDao</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class GiftPriceDao extends AbstractDbDao implements DbDaoInterface, DaoInterface, CacheDaoInterface
{
    use CacheDaoTrait; // Provides implementation for CacheDaoInterface

    /** @var string */
    protected $_tableName = 'gift_price';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'title', 'internal_description', 'price_total', 'price_unit', 'equivalent_payment_plan_id'
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /**
     * Return the <kbd>GiftPrice</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\Gift\GiftPrice
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
     * Map an associative array DB record into a <kbd>GiftUser</kbd> Entity.
     * 
     * @param array $dbRecord
     * @return \Pley\Entity\Gift\GiftUser
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new \Pley\Entity\Gift\GiftPrice(
            $dbRecord['id'], 
            $dbRecord['title'], 
            (float)$dbRecord['price_total'],
            (float)$dbRecord['price_unit'],
            $dbRecord['equivalent_payment_plan_id']
        );
    }
}
