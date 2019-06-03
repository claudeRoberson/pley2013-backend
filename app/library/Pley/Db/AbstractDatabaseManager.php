<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Db;

/**
 * The <kbd>AbstractDatabaseManager</kbd> class defines the methods to work with a given database
 * and provides some base implementation.
 * <p>The <kbd>$_pdo</kbd> protected variable must be set by the concrete class.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Db
 * @subpackage Db
 */
abstract class AbstractDatabaseManager implements DatabaseManagerInterface
{   
    /** @var \PDO */
    protected $_pdo;
    /** @var boolean*/
    protected $_isTransactionActive = false;
    
    /**
     * Returns the connection between PHP and a database server.
     * <p>Connection allows to create prepared statements.
     * @return \PDO
     */
    public function getPDO()
    {
        return $this->_pdo;
    }
    
    /**
     * Creates a new prepared <kbd>\PDOStatement</kbd> object with the supplied sql query.
     * @param string $preparedStatementSql
     * @return \PDOStatement
     */
    public function prepare($preparedStatementSql)
    {
        $prepStmt = $this->_pdo->prepare($preparedStatementSql);
        return $prepStmt;
    }
    
    /**
     * Returns the last inserted id performed over the PDO.
     * @return int
     */
    public function lastInsertedId()
    {
        $lastInsertedId = $this->_pdo->lastInsertId();
        
        return $lastInsertedId;
    }
    
    /**
     * Execute a Closure within a transaction and return the value returned by the closure if any.
     * <p>This is helpfull for wholesome operations that don't require multiple exit points performing
     * conditional `commits`, and helps with code readability to remove the transaciton calls and
     * the try/catch operations.</p>
     *
     * @param \Closure $callback
     * @return mixed
     * @throws \Exception
     */
    public function transaction(\Closure $callback)
    {
        // If this transaction request happens to be called within another existing transaction in 
        // the same connection represented by this manager, then just execute the call back as we 
        // don't want to commit on this inner transaction request as the commit the operation will
        // flag the transaction as over and it is incorrect as the outer connection has not yet
        // been finished.
        /*
         * @TODO: Since we are passing a `$this` reference to the call back, figure out if we can
         *        make the Active State separate of the Manager level, in other words, be scoped to
         *        this method call.
         */
        if ($this->_isTransactionActive) {
            return $callback($this);
        }
        
        $result = null;
        
        $this->beginTransaction();
        
        try {
            $result = $callback($this);

            $this->commit();
            
        } catch (\Exception $ex) {
            $this->rollBack();

            throw $ex;
        }

        return $result;
    }
    
    /** {@inheritdoc } */
    public function beginTransaction()
    {
        $this->_isTransactionActive = true;
    }

    /** {@inheritdoc } */
    public function commit()
    {
        $this->_isTransactionActive = false;
    }

    /** {@inheritdoc } */
    public function rollBack()
    {
        $this->_isTransactionActive = false;
    }

    /**
     * Indicates whether a transaction was initiated on this connection and is still active, meaning
     * no calls to <kbd>commit()</kbd> or <kbd>rollBack</kbd> have been made.
     * <p>This method is usefull if we need to perform a transaction that spans different DAOs and
     * the DAO method needs to ensure that its operation is performed within a transaction.</p>
     * @return boolean
     */
    public function isTransactionActive()
    {
        return $this->_isTransactionActive;
    }
    
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
    public function checkActiveTransaction($methodName)
    {
        if (!$this->isTransactionActive()) {
            throw new \Pley\Exception\Db\TransactionRequiredException($methodName);
        }
    }

}
