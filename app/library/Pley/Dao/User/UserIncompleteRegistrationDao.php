<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Dao\User;

use Pley\Db\AbstractDatabaseManager as DatabaseManager;

/** ♰
 * The <kbd>UserIncompleteRegistrationDao</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class UserIncompleteRegistrationDao extends \Pley\DataMap\Dao
{
    public function __construct(DatabaseManager $databaseManager)
    {
        parent::__construct($databaseManager);
        
        $this->setEntityClass(\Pley\Entity\User\UserIncompleteRegistration::class);
    }
    
    /**
     * Return the <kbd>UserIncompleteRegistration</kbd> entity for the supplied id or null if not found.
     * @param int $userId
     * @return \Pley\Entity\User\UserIncompleteRegistration
     */
    public function findByUser($userId)
    {
        $resultSet = $this->where('`user_id` = ?', [$userId]);
        
        return empty($resultSet)? null : $resultSet[0];
    }
    
    public function delete(\Pley\Entity\User\UserIncompleteRegistration $userIncompleteReg)
    {
        $prepSql  = "DELETE FROM `{$this->_tableName}` "
                  . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$userIncompleteReg->getId()];
        
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }
}
