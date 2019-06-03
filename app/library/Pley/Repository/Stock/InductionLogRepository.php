<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Stock;

use Pley\DataMap\Dao;
use Pley\DataMap\Repository;
use Pley\Entity\Stock\InductionLog;

/**
 * Repository class for induction log entries related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class InductionLogRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(InductionLog::class);
    }

    /**
     * Find induction log entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Stock\InductionLog
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }
    /**
     * Find induction log entries by item
     *
     * @param int $itemId
     * @return \Pley\Entity\Stock\InductionLog[]
     */
    public function findByItem($itemId)
    {
        $inductionsTableName = InductionLog::tableName();
        $sql = "SELECT `{$inductionsTableName}`.* FROM `{$inductionsTableName}` 
                      WHERE `{$inductionsTableName}`.`item_id` = ? 
                      ORDER BY `{$inductionsTableName}`.`created_at` DESC";
        return $this->_dao->query($sql, [$itemId]);
    }


    /**
     * Get all entries
     *
     * @return \Pley\Entity\Stock\InductionLog[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>InductionLog</kbd> Entity.
     *
     * @param \Pley\Entity\Stock\InductionLog $inductionLog
     * @return \Pley\Entity\Stock\InductionLog
     */
    public function save(InductionLog $inductionLog)
    {
        return $this->_dao->save($inductionLog);
    }
}