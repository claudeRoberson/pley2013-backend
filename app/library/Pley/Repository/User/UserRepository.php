<?php 
namespace Pley\Repository\User;

use \Pley\Exception\Entity\EntityExistsException;
use \Pley\Exception\Entity\EntityNotFoundException;

/**
 * The <kbd>UserRepository</kbd> handles operations related to the User objects
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Repository.User
 * @subpackage Repository
 */
class UserRepository
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\User\UserDao */
    protected $_userDao;
    
    public function __construct(
            \Pley\Db\AbstractDatabaseManager $dbManager,
            \Pley\Dao\User\UserDao $userDao)
    {
        $this->_dbManager = $dbManager;
        $this->_userDao   = $userDao;
    }
    
    /**
     * Return a specific <kbd>User</kbd> Entity
     * @param int $userId
     * @return \Pley\Entity\User\User
     */
    public function find($userId)
    {
        return $this->_userDao->find($userId);
    }
    
    /**
     * Return a specific <kbd>User</kbd> Entity by the user email
     * @param string $email
     * @return \Pley\Entity\User\User
     */
    public function findByEmail($email)
    {
        return $this->_userDao->findByEmail($email);
    }
    
    /**
     * Return the <kbd>User</kbd> entity for the supplied Facebook Token or null if not found.
     * @param string $fbToken
     * @return \Pley\Entity\User\User|null
     */
    public function  findByFBToken($fbToken)
    {
        return $this->_userDao->findByFbToken($fbToken);
    }
    
    /**
     * Creates a new user and returns an new Entity that contains the newly generated id of it.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\User\User
     * @throws \Pley\Exception\Entity\EntityExistsException If supplied entity has an id which
     *      would lead to an update instead of the desired addition
     */
    public function add(User $user)
    {
        // If the id is available, this will produce an update instead of an insert
        if (!empty($user->getId())) {
            throw new EntityExistsException(\Pley\Entity\User\User::class, $user->getId());
        }
        
        $this->_userDao->save($user);
    }    
    
    /**
     * Updates an existing user.
     * @param \Pley\Entity\User\User $user
     * @throws \Pley\Exception\Entity\EntityNotFoundException If supplied entity lacks an id
     *      which would lead to an addition instead of the desired update
     */
    public function update(User $user)
    {
        // If the id is not available, throw exception because otherwise it would cause an insert
        if (empty($user->getId())) {
            throw new EntityNotFoundException(\Pley\Entity\User\User::class);
        }
        
        $this->_userDao->save($user);
    }
    
    /**
     * Perform a search of users given a string input.
     * @param string $input
     * @return \Pley\Entity\User\User[]
     */
    public function customerServiceSearch($input)
    {
        // Replacing any 2 or more spaces in the input string for a single space so that we can
        // split all words correctly for the search
        $sanitizedInput = preg_replace('/ {2,}/', ' ', $input);
        $inputWordList  = explode(' ', $sanitizedInput);
        
        $userIdList = [];
        
        foreach ($inputWordList as $inputWord) {
            if (is_numeric($inputWord)) {
                $userIdList[] = (int)$inputWord;
                
            } else {
                $userIdList = array_merge($userIdList, $this->_csSearchUserInfo($inputWord));
            }
        }
        
        $uniqueUserIdList = array_unique($userIdList);
        
        $userList = [];
        foreach ($uniqueUserIdList as $userId) {
            $user = $this->_userDao->find($userId);
            
            if (!is_null($user)) {
                $userList[] = $user;
            }
        }
        
        return $userList;
    }
    
    /**
     * Perform a search for a single user given a string input directly on the Database.
     * @param string $input
     * @return int[] The List of User IDs that matched the search query.
     */
    private function _csSearchUserInfo($input)
    {
        if (strlen($input) < 3) {
            return [];
        }
        
        $userSql   = 'SELECT `id` FROM `user` '
                   . 'WHERE `first_name` LIKE CONCAT("%", ?, "%") '
                   . ' OR `last_name` LIKE CONCAT("%", ?, "%") '
                   . ' OR `email` LIKE CONCAT("%", ?, "%")';
        $userPstmt = $this->_dbManager->prepare($userSql);
        $userPstmt->execute([$input, $input, $input]);
        
        $resultSet = $userPstmt->fetchAll(\PDO::FETCH_ASSOC);
        $userPstmt->closeCursor();
        
        $userIdList = [];
        foreach ($resultSet as $dbRecord) {
            $userIdList[] = $dbRecord['id'];
        }
        
        return $userIdList;
    }
}
