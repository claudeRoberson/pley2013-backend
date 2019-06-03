<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Dao\Subscription;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Traits\CacheDaoTrait;
use \Pley\Entity\Subscription\Subscription;

/**
 * The <kbd>SubscriptionDao</kbd> class provides implementation to interact with the Subscription
 * table in the DB and Cache.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.Subscription
 * @subpackage Dao
 */
class SubscriptionDao extends AbstractDbDao implements DbDaoInterface, CacheDaoInterface
{
    use CacheDaoTrait; // Provides implementation for CacheDaoInterface

    /** @var string */
    protected $_tableName = 'subscription';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'type_brand_id', 'name', 'description', 'type_item_pull_id',
            'period', 'period_unit', 'start_period', 'start_year',
            'charge_day', 'deadline_day', 'delivery_day_start', 'delivery_day_end',
            'deadline_extended_days', 'payment_plan_id_signup_list_json', 'gift_price_id_list_json',
            'welcome_email_header_img',
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /**
     * Return the <kbd>Subscription</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\Subscription\Subscription
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
     * Returns a list of all the subscriptions
     * @return \Pley\Entity\Subscription\Subscription[]
     */
    public function all()
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` ";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [];

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
     * Map an associative array DB record into a <kbd>Subscription</kbd> Entity.
     * 
     * @param array $dbRecord
     * @return \Pley\Entity\Subscription\Subscription
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new Subscription(
            $dbRecord['id'], 
            $dbRecord['type_brand_id'], 
            $dbRecord['name'], 
            $dbRecord['description'], 
            $dbRecord['type_item_pull_id'], 
            $dbRecord['period'], 
            \Pley\Enum\PeriodUnitEnum::fromString($dbRecord['period_unit']),
            $dbRecord['start_period'], 
            $dbRecord['start_year'], 
            $dbRecord['charge_day'], 
            $dbRecord['deadline_day'],
            $dbRecord['delivery_day_start'],
            $dbRecord['delivery_day_end'],
            $dbRecord['deadline_extended_days'],
            json_decode($dbRecord['payment_plan_id_signup_list_json'], true),
            json_decode($dbRecord['gift_price_id_list_json'], true),
            $dbRecord['welcome_email_header_img']
        );
    }
}
