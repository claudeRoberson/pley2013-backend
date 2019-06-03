<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Referral;

use Pley\DataMap\Repository;
use Pley\DataMap\Dao;
use Pley\Entity\Referral\Reward;

/**
 * Repository class for <kbd>Reward</kbd> entries related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class RewardRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(Reward::class);
    }

    /**
     * Find <kbd>Reward</kbd> entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Referral\Reward
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Find <kbd>Reward</kbd> entry by userId
     *
     * @param int $userId
     * @return \Pley\Entity\Referral\Reward
     */
    public function findByUserId($userId)
    {
        $result = $this->_dao->where('user_id = ?', [$userId]);
        return count($result) ? $result[0] : null;
    }

    /**
     * Find <kbd>Reward</kbd> entry by referral user email
     *
     * @param string $referralEmail
     * @return \Pley\Entity\Referral\Reward
     */
    public function findByReferralEmail($referralEmail)
    {
        $result = $this->_dao->where('referral_user_email = ?', [$referralEmail]);
        return count($result) ? $result[0] : null;
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\Referral\Reward[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Get all entries with condition
     * @param string $condition
     * @param array $bindings
     * @return \Pley\Entity\Referral\Reward[]
     */
    public function where($condition, $bindings)
    {
        return $this->_dao->where($condition, $bindings);
    }

    /**
     * Saves the supplied <kbd>Reward</kbd> Entity.
     *
     * @param \Pley\Entity\Referral\Reward $reward
     * @return \Pley\Entity\Referral\Reward
     */
    public function save(Reward $reward)
    {
        return $this->_dao->save($reward);
    }
}