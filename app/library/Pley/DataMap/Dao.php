<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\DataMap;

use Pley\DataMap\Dao\DataMapDaoInterface;
use Pley\Db\AbstractDatabaseManager;

/**
 * Class description goes here
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class Dao implements DataMapDaoInterface
{
    /**
     * Instance of the Database Manager
     * @var \Pley\Db\DatabaseManagerInterface
     */
    protected $_dbManager;

    /**
     * Holds Entity class name, which is injected via service provider from a repository
     * @var string
     */
    protected $_entityClass;

    /**
     * Map to store prepared <kbd>\PDOStatement</kbd> objects for this request.
     * <p>This allows us to reuse a statement that we have created and as such gain performance.</p>
     * @var array
     */
    protected $_prepStmtMap = [];

    /** @var string */
    protected $_tableName;
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;

    public function __construct(AbstractDatabaseManager $databaseManager)
    {
        $this->_dbManager = $databaseManager;
    }

    /**
     * Sets the entityClassName that will be used for mapping table/columns against a database.
     *
     * @param string $entityClassName
     */
    public function setEntityClass($entityClassName)
    {
        $this->_entityClass = $entityClassName;
        $this->_tableName = $entityClassName::tableName();
        $this->_columnNames = implode(',', $this->_escapedFields($entityClassName::columns()));
    }

    /**
     * Finds an Entity by a given id
     *
     * @param int $id
     * @return \Pley\DataMap\Entity
     */
    public function find($id)
    {
        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . "WHERE `id` = ?";
        $pstmt = $this->_prepare($prepSql);
        $bindings = [$id];

        $pstmt->execute($bindings);

        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        return $this->_toEntity($dbRecord);
    }

    /**
     * Returns an array of all Entities
     * @return \Pley\DataMap\Entity[]
     */
    public function all()
    {
        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}`";
        $pstmt = $this->_prepare($prepSql);

        $pstmt->execute();

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        $collection = [];
        foreach ($resultSet as $dbRecord) {
            $entity = $this->_toEntity($dbRecord);
            $collection[] = $entity;
        }
        return $collection;
    }

    /**
     * Saves a given Entity to a database
     * @return \Pley\DataMap\Entity
     */
    public function save(\Pley\DataMap\Entity $entity)
    {
        return empty($entity->getId()) ? $this->_insert($entity) : $this->_update($entity);
    }

    /**
     * Removes a given Entity from a database
     * @return void
     */
    public function remove(\Pley\DataMap\Entity $entity)
    {
        $prepSql = "DELETE FROM `{$this->_tableName}` WHERE `id` = ?";
        $pstmt   = $this->_prepare($prepSql);

        $pstmt->execute([$entity->getId()]);
        $pstmt->closeCursor();
    }

    /**
     * Search a database for an Entities matching given condition
     * @param string $condition
     * @param string $bindings
     * @param string|null $limit
     * @return array
     */
    public function where($condition, $bindings, $limit = null)
    {
        $prepSql = "SELECT {$this->_columnNames} 
                      FROM `{$this->_tableName}` 
                      WHERE {$condition}";
        if($limit){
            $prepSql .= " LIMIT {$limit}";
        }
        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute($bindings);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        $collection = [];
        foreach ($resultSet as $dbRecord) {
            $entity = $this->_toEntity($dbRecord);
            $collection[] = $entity;
        }
        return $collection;
    }

    /**
     * Search a database for an Entities matching given SQL query
     * @param string $sql
     * @param string $bindings
     * @param string|null $limit
     * @return array
     */
    public function query($sql, $bindings, $limit = null)
    {
        $pstmt = $this->_prepare($sql);
        $pstmt->execute($bindings);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        $collection = [];
        foreach ($resultSet as $dbRecord) {
            $entity = $this->_toEntity($dbRecord);
            $collection[] = $entity;
        }
        return $collection;
    }

    /**
     * @param \Pley\DataMap\Entity $entity
     * @return \Pley\DataMap\Entity
     */
    protected function _insert(\Pley\DataMap\Entity $entity)
    {
        $rowData = $entity->mapToRow();
        $placeholders = array_fill(0, count($rowData), '?');
        if (array_key_exists('created_at', $rowData)) {
            $rowData['created_at'] = \Pley\Util\DateTime::date(time());
        }
        if (array_key_exists('updated_at', $rowData)) {
            $rowData['updated_at'] = '0000-00-00 00:00:00';
        }
        $keys = $values = array();
        foreach ($rowData as $columnName => $columnValue) {
            $keys[] = $columnName;
            $bindings[] = $columnValue;
        }
        // assuming the PDO instance is $pdo
        $prepSql = 'INSERT INTO `' . $this->_tableName . '` ' .
            '(' . implode(',', $keys) . ') VALUES ' .
            '(' . implode(',', $placeholders) . ')';
        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute($bindings);

        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $entity->setId($id);
        return $entity;
    }

    /**
     * @param \Pley\DataMap\Entity $entity
     * @return \Pley\DataMap\Entity
     */
    protected function _update(\Pley\DataMap\Entity $entity)
    {
        $rowData = $entity->mapToRow();
        $bindings = [];
        if (array_key_exists('updated_at', $rowData)) {
            $rowData['updated_at'] = \Pley\Util\DateTime::date(time());
        }
        $prepSql = 'UPDATE `' . $this->_tableName . '` SET';
        foreach ($rowData as $columnName => $columnValue) {
            $prepSql .= "`{$columnName}` = ?,";
            $bindings[] = $columnValue;
        }
        $prepSql = substr($prepSql, 0, -1);
        $prepSql .= " WHERE `id` = ?";
        $bindings[] = $entity->getId();

        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
        return $entity;
    }

    /**
     * Map an associative array DB record into a <kbd>Entity</kbd> via Entity::mapFromRow()
     * implementation.
     *
     * @param array $dbRecord
     * @return \Pley\DataMap\Entity
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        $entity = new $this->_entityClass();
        return $entity->mapFromRow($dbRecord);
    }

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
     * @param array $fieldList
     * @param string $tableName (Optional)<br/>If supplied, all columns will be prefrix with the
     *      table name. (Useful where some joins are needed)
     * @return array
     */
    protected function _escapedFields($fieldList, $tableName = null)
    {
        $noPrefixClosure = function (&$value) {
            $value = "`{$value}`";
        };
        $prefixClosure = function (&$value) use ($tableName) {
            $value = "`{$tableName}`.`{$value}`";
        };

        $closureToUse = isset($tableName) ? $prefixClosure : $noPrefixClosure;

        // escaping fields
        array_walk($fieldList, $closureToUse);

        return $fieldList;
    }
}