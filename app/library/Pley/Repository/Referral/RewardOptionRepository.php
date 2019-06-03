<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Referral;

use Pley\DataMap\Repository;
use Pley\DataMap\Dao;
use Pley\Entity\Referral\RewardOption;

/**
 * Repository class for <kbd>RewardOption</kbd> entries related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class RewardOptionRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(RewardOption::class);
    }

    /**
     * Find <kbd>RewardOption</kbd> entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Referral\RewardOption
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Find <kbd>RewardOption</kbd> entry by userId
     *
     * @return \Pley\Entity\Referral\RewardOption[]
     */
    public function findActive()
    {
        return $this->_dao->where('active = ?', [1]);
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\Referral\RewardOption[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>RewardOption</kbd> Entity.
     *
     * @param \Pley\Entity\Referral\RewardOption $rewardOption
     * @return \Pley\Entity\Referral\RewardOption
     */
    public function save(RewardOption $rewardOption)
    {
        return $this->_dao->save($rewardOption);
    }
}