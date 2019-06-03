<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\User;

use Pley\DataMap\Dao;
use Pley\DataMap\Repository;
use Pley\Entity\User\UserNote;

/**
 * Repository class for NPS user related CRUD operations
 *
 * @author Sebastian Maldonado (seba@pley.com)
 * @version 1.0
 */
class UserNoteRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(UserNote::class);
    }

    /**
     * Find invite by Id
     *
     * @param int $id
     * @return \Pley\Entity\User\UserNote
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Get invites by a given user Id
     *
     * @param int $userId
     * @return \Pley\Entity\User\UserNote[]
     */
    public function findByUserId($userId)
    {
        return $this->_dao->where('user_id = ?', [$userId]);
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\User\UserNote[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>UserNote</kbd> Entity.
     *
     * @param \Pley\Entity\User\UserNote $userNote
     * @return \Pley\Entity\User\UserNote
     */
    public function save(\Pley\Entity\User\UserNote $userNote)
    {
        return $this->_dao->save($userNote);
    }

    /**
     * Removes a note with the supplied id
     *
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        $note = $this->find($id);
        if (empty($note)) {
            return false;
        } else {
            $this->_dao->remove($note);
            return true;
        }

    }

}