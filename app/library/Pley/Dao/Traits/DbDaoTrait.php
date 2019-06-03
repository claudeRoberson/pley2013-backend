<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Dao\Traits;

use \Pley\Db\DatabaseManagerInterface;

/**
 * The <kbd>DbDaoTrait</kbd> provides the implementation for the <kbd>DbDaoInterface</kbd> so it is
 * easily provided to any DAO that extends such interface without the need of extending a specific
 * abstract class.
 * <p>This is useful in case the DAO implements interfaces but would otherwise would only be able to
 * extends one abstract class and have to provide implementation for the other interface.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Dao.Traits
 * @subpackage Dao
 */
trait DbDaoTrait
{
    /**
     * Instance of the Database Manager
     * @var \Pley\Db\DatabaseManagerInterface
     */
    protected $_dbManager;
    
    /**
     * Sets the DatabaseManager that will be used as means of interacting with the database.
     * 
     * @param \Pley\Db\DatabaseManagerInterface $dbManager
     */
    public function setDatabaseManager(DatabaseManagerInterface $dbManager)
    {
        $this->_dbManager = $dbManager;
    }
}
