<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\User;

use Pley\DataMap\Dao;
use Pley\DataMap\Repository;
use Pley\Entity\User\Invite;

/**
 * Repository class for invites related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class InviteRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(Invite::class);
    }

    /**
     * Find invite by Id
     *
     * @param int $id
     * @return \Pley\Entity\User\Invite
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Get invites by a given user Id
     *
     * @param int $userId
     * @return \Pley\Entity\User\Invite[]
     */
    public function findByUserId($userId)
    {
        return $this->_dao->where('user_id = ?', [$userId]);
    }

    /**
     * Get invites by a given status Id
     *
     * @param int $statusId
     * @return \Pley\Entity\User\Invite[]
     */
    public function findByStatus($statusId)
    {
        return $this->_dao->where('status = ?', [$statusId]);
    }

    /**
     * Get invites by a given user Id
     *
     * @param int $referralEmail
     * @return \Pley\Entity\User\Invite[]
     */
    public function findByReferralEmail($referralEmail)
    {
        return $this->_dao->where('referral_user_email = ?', [$referralEmail]);
    }

    /**
     * Update invites with user id based on referral email
     *
     * @param \Pley\Entity\User\User $user
     * @return void
     */
    public function updateEntriesWithUser(\Pley\Entity\User\User $user)
    {
        /**
         * @var $invites Invite[]
         */
        $invites = $this->_dao->where('referral_user_email = ?', [$user->getEmail()]);
        foreach ($invites as $invite){
            $invite->setUserId($user->getId());
            $this->save($invite);
        }
    }

    /**
     * Get invite by a given token Id
     *
     * @param int $tokenId
     * @return \Pley\Entity\User\Invite
     */
    public function findByTokenId($tokenId)
    {
        $result = $this->_dao->where('referral_token_id = ?', [$tokenId]);
        return count($result) ? $result[0] : null;
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\User\Invite[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>Invite</kbd> Entity.
     *
     * @param \Pley\Entity\User\Invite $invite
     * @return \Pley\Entity\User\Invite
     */
    public function save(\Pley\Entity\User\Invite $invite)
    {
        return $this->_dao->save($invite);
    }
}