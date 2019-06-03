<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Db;

/**
 * The <kbd>DatabaseManagerInterface</kbd> class defines the methods to work with a given database.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Db
 * @subpackage Db
 */
interface DatabaseManagerInterface
{
    /**
     * Returns the connection between PHP and a database server.
     * <p>Connection allows to create prepared statements.
     * @return \PDO
     */
    public function getPDO();
    
    /**
     * Creates a new prepared <kbd>\PDOStatement</kbd> object with the supplied sql query.
     * @param string $preparedStatementSql
     * @return \PDOStatement
     */
    public function prepare($preparedStatementSql);
    
    /**
     * Returns the last inserted id performed over the PDO.
     * @return int
     */
    public function lastInsertedId();
    
    /**
     * Start a new database transaction.
     */
    public function beginTransaction();
    
    /**
     * Commit the active database transaction.
     */
    public function commit();
    
    /**
     * Rollback the active database transaction.
     */
    public function rollBack();
    
    /**
     * Execute a Closure within a transaction and return the value returned by the closure if any.
     *
     * @param \Closure $callback
     * @return mixed
     * @throws \Exception
     */
    public function transaction(\Closure $callback);
    
    /**
     * Indicates whether a transaction was initiated on this connection and is still active, meaning
     * no calls to <kbd>commit()</kbd> or <kbd>rollBack</kbd> have been made.
     * <p>This method is usefull if we need to perform a transaction that spans different DAOs and
     * the DAO method needs to ensure that its operation is performed within a transaction.</p>
     * @return boolean
     */
    public function isTransactionActive();
    
    /**
     * Checks if there is an Active Transaction on this connection and if not, an exeception is thrown.
     * <p>This method reduces the repetitive code checks that throw exception and leaves 
     * <kbd>::isTransactionActive()</kbd> for more decision like creating a new transaction if one
     * is not already active, or just don't commit if a transaction is currently active as part
     * of a wrapper function call, etc.
     * </p>
     * @param string $methodName Usually <kbd>__METHOD__</kbd>
     * @throws \Pley\Exception\Db\TransactionRequiredException
     */
    public function checkActiveTransaction($methodName);
    
    /** Resets the internal DB Connection with a references to a new Connection */
    public function resetConnection();
}
