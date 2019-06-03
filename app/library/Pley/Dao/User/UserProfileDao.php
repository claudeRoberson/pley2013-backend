<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Dao\User;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;


class UserProfileDao extends AbstractDbDao implements DbDaoInterface, DaoInterface

{    
    /** @var string */
    protected $_tableName = 'user_profile';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
        
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'user_id', 'gender', 'first_name', 'last_name', 'birth_date', 'picture', 
            'type_shirt_size_id', 'created_at', 'updated_at'
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /**
     * Return the <kbd>UserProfile</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\User\UserProfile|null
     */
    public function find($id)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `id` = ? ';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$id];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        
        $entity = $this->_toEntity($dbRecord);
        
        return $entity;
    }

    /**
     * Return a list of <kbd>UserProfile</kbd> entities for the supplied user id.
     * @param int $userId
     * @return \Pley\Entity\User\UserProfile[]
     */
    public function findByUser($userId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `user_id` = ? ';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$userId];

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
     * Takes a <kbd>UserProfile</kbd> entity object and saves it into the Storage.
     * <p>Saving could imply adding or updating based on the entity supplied; if the entity has a
     * set ID, it will produce an Update, otherwise it will produce an Insert and the entity will
     * be updated with the newly generated id.</p>
     * <p>The method also does an entity type check to do some error validation before run time</p>
     * 
     * @param \Pley\Entity\User\UserProfile $userProfile The Entity object to save
     */
    public function save(\Pley\Entity\User\UserProfile $userProfile)
    {
        // Depending on the Entity ID value, either update or insert.
        if (empty($userProfile->getId())) {
            $this->_insert($userProfile);
        } else {
            $this->_update($userProfile);
        }
    }
    
    /**
     * Removew the supplied user profile from the storage.
     * @param \Pley\Entity\User\UserProfile $userProfile
     */
    public function delete(\Pley\Entity\User\UserProfile $userProfile)
    {
        $prepSql = "DELETE FROM `{$this->_tableName}` WHERE `id` = ?";
        $pstmt   = $this->_prepare($prepSql);
        
        $pstmt->execute([$userProfile->getId()]);
        $pstmt->closeCursor();
    }
   
    /**
     * Map an associative array DB record into a <kbd>UserProfile</kbd> Entity.
     * 
     * @param array $dbRecord
     * @return \Pley\Entity\User\UserProfile
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new \Pley\Entity\User\UserProfile(
            $dbRecord['id'],
            $dbRecord['user_id'],
            $dbRecord['gender'],
            $dbRecord['first_name'],
            $dbRecord['last_name'],
            $dbRecord['birth_date'],
            $dbRecord['picture'],
            $dbRecord['type_shirt_size_id'],
            \Pley\Util\DateTime::strToTime($dbRecord['created_at']),
            \Pley\Util\DateTime::strToTime($dbRecord['updated_at'])
        );
    }
    
    private function _insert(\Pley\Entity\User\UserProfile $userProfile)
    {
        $prepSql  = "INSERT INTO `{$this->_tableName}` ("
                  .     '`user_id`, '
                  .     '`gender`, '
                  .     '`first_name`, '
                  .     '`last_name`, '
                  .     '`birth_date`, '
                  .     '`picture`, '
                  .     '`type_shirt_size_id`, '
                  .     '`created_at`'
                  . ') VALUES (?, ?, ?, ?, ?, ?, ?, NOW())';
        $pstmt    = $this->_prepare($prepSql);
        
        // converting the Object to a JSON String
        $bindings = [
            $userProfile->getUserId(),
            $userProfile->getGender(),
            $userProfile->getFirstName(),
            $userProfile->getLastName(),
            $userProfile->getBirthDate(),
            $userProfile->getPicture(),
            $userProfile->getTypeShirtSizeId()
        ];
        
        $pstmt->execute($bindings);
        
        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $userProfile->setId($id);
    }
    
    private function _update(\Pley\Entity\User\UserProfile $userProfile)
    {
        $prepSql  = "UPDATE `{$this->_tableName}` "
                  . 'SET `user_id` = ?, '
                  .     '`gender` = ?, '
                  .     '`first_name` = ?, '
                  .     '`last_name` = ?, '
                  .     '`birth_date` = ?, '
                  .     '`picture` = ?, '
                  .     '`type_shirt_size_id` = ? '
                  . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);
        
        $bindings = [
            $userProfile->getUserId(),
            $userProfile->getGender(),
            $userProfile->getFirstName(),
            $userProfile->getLastName(),
            $userProfile->getBirthDate(),
            $userProfile->getPicture(),
            $userProfile->getTypeShirtSizeId(),
            
            // WHERE bindings
            $userProfile->getId(),
        ];
        
        $pstmt->execute($bindings);
        $pstmt->closeCursor();
        
        $userProfile->setUpdatedAt(time());
    }

}



