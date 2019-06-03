<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Referral;

/**
 * The <kbd>RewardOption</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Referral
 * @Meta\Table(name="referral_reward_option")
 */

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

class RewardOption extends Entity
{
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="reward_name")
     */
    protected $_rewardName;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="min_acquisitions_threshold")
     */
    protected $_minAcquisitionsThreshold;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="active")
     */
    protected $_active;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return RewardOption
     */
    public function setId($id)
    {
        if (isset($this->_id)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_id');
        }
        $this->_id = $id;
    }

    /**
     * @return int
     */
    public function getRewardName()
    {
        return $this->_rewardName;
    }

    /**
     * @param int $rewardName
     * @return RewardOption
     */
    public function setRewardName($rewardName)
    {
        $this->_rewardName = $rewardName;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinAcquisitionsThreshold()
    {
        return $this->_minAcquisitionsThreshold;
    }

    /**
     * @param int $minAcquisitionsThreshold
     * @return RewardOption
     */
    public function setMinAcquisitionsThreshold($minAcquisitionsThreshold)
    {
        $this->_minAcquisitionsThreshold = $minAcquisitionsThreshold;
        return $this;
    }

    /**
     * @return int
     */
    public function getActive()
    {
        return $this->_active;
    }

    /**
     * @param int $active
     * @return RewardOption
     */
    public function setActive($active)
    {
        $this->_active = $active;
        return $this;
    }

    /**
     * @return int
     */
    public function isActive()
    {
        return (bool)$this->getActive();
    }

}

