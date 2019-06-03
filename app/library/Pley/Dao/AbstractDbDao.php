<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Dao;

use \Pley\Dao\Traits\DbDaoTrait;

/**
 * The <kbd>AbstractDbDao</kbd> class provides some base functionality for the <kbd>DbDaoInterface</kbd>.
 * <p>It also provides with some new methods to help guide the developer in elements that make it
 * easier to interact between a Dao and an Entity.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Dao
 * @subpackage Dao
 */
abstract class AbstractDbDao implements DaoInterface, DbDaoInterface
{   
    use DbDaoTrait; // Provides implementation for DbDaoInterface
    
    /**
     * Map to store prepared <kbd>\PDOStatement</kbd> objects for this request.
     * <p>This allows us to reuse a statement that we have created and as such gain performance.</p>
     * @var array
     */
    protected $_prepStmtMap = [];
    
    /**
     * Map an associative array DB record into a Pley Entity.
     * 
     * @param array $dbRecord
     * @return object The Pley Entity
     */
    protected abstract function _toEntity($dbRecord);
    
    /**
     * Returns a prepared <kbd>\PDOStatement</kbd> object for the supplied prepared sql query.
     * <p>If the Prepared Statement for the given query has been created already and cached in-memory,
     * then such instance will be returned, otherwise a new Prepared Statement will be created, cached
     * and returned.</p>
     * 
     * @param string $prepSql
     * @return \PDOStatement
     */
    protected function _prepare($prepSql)
    {
        // create a quite unique key for the supplied query
        $prepSqlKey = md5(get_class($this) . '::' . $prepSql);
        
        // If the prepared statement has not been stored, create it and store it for possible
        // future use within this request.
        if (!isset($this->_prepStmtMap[$prepSqlKey])) {
            $this->_prepStmtMap[$prepSqlKey] = $this->_dbManager->prepare($prepSql);
        }
        
        return $this->_prepStmtMap[$prepSqlKey];
    }
    
    /**
     * Helper function to escape a list of column fields (add the back ticks).
     * @param array  $fieldList
     * @param string $tableName (Optional)<br/>If supplied, all columns will be prefrix with the
     *      table name. (Useful where some joins are needed)
     * @return array
     */
    protected function _escapedFields($fieldList, $tableName = null)
    {
        $noPrefixClosure = function(&$value) {
            $value = "`{$value}`";
        };
        $prefixClosure   = function(&$value) use ($tableName) {
            $value = "`{$tableName}`.`{$value}`";
        };
        
        $closureToUse = isset($tableName)? $prefixClosure : $noPrefixClosure;
        
        // escaping fields
        array_walk($fieldList, $closureToUse);
        
        return $fieldList;
    }
}
