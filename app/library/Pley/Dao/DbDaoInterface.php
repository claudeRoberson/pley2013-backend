<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Dao;

use \Pley\Dao\DaoInterface;
use \Pley\Db\DatabaseManagerInterface;

/**
 * The <kbd>DbDaoInterface</kbd> defines that the concrete class will use a Database storage and
 * thus, it needs to be supplied of a Database Manager instance.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Dao
 * @subpackage Dao
 */
interface DbDaoInterface extends DaoInterface
{
    /**
     * Sets the DatabaseManager that will be used as means of interacting with the database.
     * 
     * @param \Pley\Db\DatabaseManagerInterface $dbManager
     */
    public function setDatabaseManager(DatabaseManagerInterface $dbManager);
}
