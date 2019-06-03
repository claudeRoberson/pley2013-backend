<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Db\Impl\Illuminate;

use \Pley\Db\AbstractDatabaseManager;
use \Pley\Db\DatabaseManagerInterface;

/**
 * The <kbd>DatabaseManager</kbd> class is the specific implementation of the AbstractDatabaseManager
 * to interact with the DB through the use of the Illiminate <kbd>DatabaseManager</kbd> and
 * expose commonly used methods.
 * <p>The intent of this class is to speed up connectivity with the database since we discovered
 * that the ActiveRecord models add to much overhead, causing in some cases around a 600% more time
 * per request.</p>
 * <p>So, we are going to use the lowest level possible but get access to the dynamic PDO objects
 * handled by the Illuminate implementation.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Db.Impl.Illuminate
 * @subpackage Db
 */
class DatabaseManager extends AbstractDatabaseManager implements DatabaseManagerInterface
{
    /** @var \Illuminate\Database\DatabaseManager */
    protected $_dbManager;
    /** @var \Illuminate\Database\Connection */
    protected $_connection;
    
    public function __construct(\Illuminate\Database\DatabaseManager $dbMgr)
    {
        // By default, Illuminate will Cache in-memory the connection and PDO for this specific
        // PHP request instance.
        // So, we can locally cache a reference to them for easier use within our manager methods.
        $this->_dbManager  = $dbMgr;
        $this->_connection = $this->_dbManager->connection();
        $this->_pdo        = $this->_connection->getPdo();
        
        // By Default, Illuminate parses a query builder string into a class attribute array
        // Unless we are aiming to do some performance checks, there is no need to store extra memory
        // that we are not using and save parsing processes to store such in memory logs.
        $this->_connection->disableQueryLog();
    }
    
    /** {@inheritdoc } */
    public function beginTransaction()
    {
        parent::beginTransaction();
        $this->_connection->beginTransaction();
    }

    /** {@inheritdoc } */
    public function commit()
    {
        $this->_connection->commit();
        parent::commit();
    }

    /** {@inheritdoc } */
    public function rollBack()
    {
        $this->_connection->rollBack();
        parent::rollBack();
    }
    
    /** {@inheritdoc } */
    public function resetConnection()
    {
        $this->_connection->disconnect();
        $this->_connection->reconnect();
        $this->_pdo = $this->_connection->getPdo();
    }
}
