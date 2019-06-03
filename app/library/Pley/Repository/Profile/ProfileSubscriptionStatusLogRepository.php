<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Profile;

use Pley\DataMap\Dao;
use Pley\DataMap\Repository;

use Pley\Entity\Profile\ProfileSubscriptionStatusLog;

/**
 * Repository class for profile subscription log entries related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ProfileSubscriptionStatusLogRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(ProfileSubscriptionStatusLog::class);
    }

    /**
     * Find log entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Profile\ProfileSubscriptionStatusLog
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\Profile\ProfileSubscriptionStatusLog[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>ProfileSubscriptionStatusLog</kbd> Entity.
     *
     * @param \Pley\Entity\Profile\ProfileSubscriptionStatusLog $profileSubscriptionStatusLog
     * @return \Pley\Entity\Profile\ProfileSubscriptionStatusLog
     */
    public function save(ProfileSubscriptionStatusLog $profileSubscriptionStatusLog)
    {
        return $this->_dao->save($profileSubscriptionStatusLog);
    }
}