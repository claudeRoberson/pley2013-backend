<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Repository\Operations;

use \Pley\Dao\Operations\OperationsUserDao;
use \Pley\Entity\Operations\OperationsUser;

/**
 * The <kbd>BackendUserRepository</kbd> 
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Repository.Operations
 * @subpackage Repository
 */
class OperationsUserRepository
{
    /** @var \Pley\Dao\Operations\OperationsUserDao */
    protected $_operationsUserDao;
    
    public function __construct(OperationsUserDao $backendUserDao)
    {
        $this->_operationsUserDao = $backendUserDao;
    }
    
    /**
    * Find entry by Id
    * 
    * @param int $id
    * @return \Pley\Entity\Operations\OperationsUser
    */
    public function find($id)
    {
        return $this->_operationsUserDao->find($id);
    }
    
    /**
    * Get all entries
    * 
    * @return \Pley\Entity\Operations\OperationsUser[]
    */
    public function all()
    {
        return $this->_operationsUserDao->all();
    }
    
    /**
    * Find entry by email
    * 
    * @param string $email
    * @return \Pley\Entity\Operations\OperationsUser
    */
    public function findByEmail($email)
    {
        return $this->_operationsUserDao->findByEmail($email);
    }

    /**
     * @param $username
     * @return \Pley\Entity\Operations\OperationsUser
     */
    public function findByUsername($username)
    {
        return $this->_operationsUserDao->findByUsername($username);
    }
    
    /**
    * Search entry by query
    * 
    * @param string $query
    * @return \Pley\Entity\Operations\OperationsUser[]
    */
    public function search($query)
    {
        return $this->_operationsUserDao->search($query);
    }
    
    /**
     * Saves the supplied <kbd>Operations User</kbd> Entity.
     * 
     * @param \Pley\Entity\Operations\OperationsUser $opUser
     */
    public function save(OperationsUser $opUser)
    {
        $this->_operationsUserDao->save($opUser);
    }
    
}