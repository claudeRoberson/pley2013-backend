<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Dao\User;

use Pley\Db\AbstractDatabaseManager as DatabaseManager;

/**
 * The <kbd>UserWaitlistSharedDao</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package 
 * @subpackage
 */
class UserWaitlistSharedDao extends \Pley\DataMap\Dao
{
    public function __construct(DatabaseManager $databaseManager)
    {
        parent::__construct($databaseManager);
        
        $this->setEntityClass(\Pley\Entity\User\UserWaitlistShared::class);
    }
    
    /**
     * Saves a <kbd>UserWaitlistShared</kbd> Entity to a database.
     * @param \Pley\Entity\User\UserWaitlistShared $entity
     * @return \Pley\Entity\User\UserWaitlistShared
     */
    public function save(\Pley\DataMap\Entity $entity)
    {
        $waitlistShared = $this->findByUserId($entity->getUserId());
        
        // Updates are not needed and inserts should only happen once
        if (!empty($waitlistShared)) {
            // Only assign ID if not set yet, as it could lead to a Immutable Property exception
            if (empty($entity->getId())) {
                $entity->setId($waitlistShared->getId());
            }
        } else {
            $this->_insert($entity);
        }
        
        return $entity;
        
    }

    /**
     * Get an entry based on the supplied UserID if it exists.
     * @return \Pley\Entity\User\UserWaitlistShared
     */
    public function findByUserId($userId)
    {
        $userWaitlistShared = $this->where('`user_id` = ?', [$userId]);
        
        return empty($userWaitlistShared)? null : $userWaitlistShared[0];
    }
}
