<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Dao;

/**
 * The <kbd>DaoInterface</kbd> is only used to define types when injecting objects.
 * <p>The body contains comments for suggested method names, butthey are commented so they are not
 * enforced for paticular cases that may not be compatible (i.e. Compound keys, base find is not
 * done through the id but through different fields, etc)</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao
 * @subpackage Dao
 */
interface DaoInterface
{
    // ---------------------------------------------------------------------------------------------
    //                                  SUGGESTED METHODS
    // ---------------------------------------------------------------------------------------------
    
    /**
     * Return an Entity object for the supplied id or null if not found.
     * 
     * @param int $id
     * @return object|null
     */
    public function find($id);
    
    /**
     * Takes an entity object and saves it into the Storage.
     * <p>Saving could imply adding or updating based on the entity supplied; if the entity has a
     * set ID, it will produce an Update, otherwise it will produce an Insert and the entity will
     * be updated with the newly generated id.</p>
     * <p>The method also does an entity type check to do some error validation before run time.</p>
     * 
     * @param object $entity The Entity object to save
     */
    //public function save(ClassType $entity);
    
    /**
     * Removes an record from the storage for the supplied id.
     * 
     * @param int $id
     * @return object|null
     */
    //public function delete($id);
}
