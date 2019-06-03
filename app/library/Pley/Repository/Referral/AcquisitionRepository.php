<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Referral;

use Pley\DataMap\Repository;
use Pley\DataMap\Dao;
use Pley\Entity\Referral\Acquisition;

/**
 * Repository class for <kbd>Acquisition</kbd> entries related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class AcquisitionRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(Acquisition::class);
    }

    /**
     * Find <kbd>Acquisition</kbd> entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Referral\Acquisition
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Find <kbd>Acquisition</kbd> entry by tokenId
     *
     * @param int $tokenId
     * @return \Pley\Entity\Referral\Acquisition[]
     */
    public function findByTokenId($tokenId)
    {
        return $this->_dao->where('referral_token_id = ?', [$tokenId]);
    }

    /**
     * Find <kbd>Acquisition</kbd> entry by userId
     *
     * @param int $userId
     * @return \Pley\Entity\Referral\Acquisition[]
     */
    public function findBySourceUserId($userId)
    {
        return $this->_dao->where('source_user_id = ?', [$userId]);
    }

    /**
     * Find <kbd>Acquisition</kbd> entry by referral email
     *
     * @param int $referralEmail
     * @return \Pley\Entity\Referral\Acquisition[]
     */
    public function findByReferralUserEmail($referralEmail)
    {
        return $this->_dao->where('referral_user_email = ?', [$referralEmail]);
    }

    /**
     * Update acquisitions with user id based on referral email
     *
     * @param \Pley\Entity\User\User $user
     * @return void
     */
    public function updateEntriesWithUser(\Pley\Entity\User\User $user)
    {
        /**
         * @var $acquisitions Acquisition[]
         */
        $acquisitions = $this->_dao->where('referral_user_email = ?', [$user->getEmail()]);
        foreach ($acquisitions as $acquisition){
            $acquisition->setSourceUserId($user->getId());
            $this->save($acquisition);
        }
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\Referral\Acquisition[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>Acquisition</kbd> Entity.
     *
     * @param \Pley\Entity\Referral\Acquisition $acquisition
     * @return \Pley\Entity\Referral\Acquisition
     */
    public function save(Acquisition $acquisition)
    {
        return $this->_dao->save($acquisition);
    }
}