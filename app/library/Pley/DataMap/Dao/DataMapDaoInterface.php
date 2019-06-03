<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\DataMap\Dao;

use Pley\DataMap\Entity;
use \Pley\Db\DatabaseManagerInterface;

/**
 * Class description goes here
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
interface DataMapDaoInterface
{
    /**
     * Sets the DatabaseManager that will be used as means of interacting with the database.
     *
     * @param string $entityClassName
     */
    public function setEntityClass($entityClassName);

    /**
     * Return the <kbd>Coupon</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\DataMap\Entity
     */

    public function find($id);

    /**
     * Insert or update an <kbd>Entity</kbd> to DB.
     *
     * @param \Pley\DataMap\Entity $entity
     * @return \Pley\DataMap\Entity
     */
    public function save(Entity $entity);

    /**
     * Returns a list of all <kbd>Entity</kbd> records from DB.
     * @return \Pley\DataMap\Entity[]
     */
    public function all();

    /**
     * @param \Pley\DataMap\Entity $entity
     * @return bool
     */
    public function remove(Entity $entity);

}