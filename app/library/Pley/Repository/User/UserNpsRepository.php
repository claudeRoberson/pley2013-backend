<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\User;

use Pley\DataMap\Dao;
use Pley\DataMap\Repository;
use Pley\Entity\User\UserNps;

/**
 * Repository class for NPS user related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class UserNpsRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(UserNps::class);
    }

    /**
     * Find invite by Id
     *
     * @param int $id
     * @return \Pley\Entity\User\UserNps
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Get invites by a given user Id
     *
     * @param int $userId
     * @return \Pley\Entity\User\UserNps[]
     */
    public function findByUserId($userId)
    {
        return $this->_dao->where('user_id = ?', [$userId]);
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\User\UserNps[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>UserNps</kbd> Entity.
     *
     * @param \Pley\Entity\User\UserNps $userNps
     * @return \Pley\Entity\User\UserNps
     */
    public function save(\Pley\Entity\User\UserNps $userNps)
    {
        return $this->_dao->save($userNps);
    }
}