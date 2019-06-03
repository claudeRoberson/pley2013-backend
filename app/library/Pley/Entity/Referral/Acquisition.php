<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Referral;

/**
 * The <kbd>Acquisition</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Referral
 * @Meta\Table(name="referral_acquisition")
 */

use Pley\DataMap\Entity;
use Pley\DataMap\Entity\Timestampable;
use Pley\DataMap\Annotations\Meta;

class Acquisition extends Entity
{
    use Timestampable;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="source_user_id")
     */
    protected $_sourceUserId;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="referral_user_email")
     */
    protected $_referralUserEmail;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="acquired_user_id")
     */
    protected $_acquiredUserId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="referral_token_id")
     */
    protected $_referralTokenId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="reward_amount")
     */
    protected $_rewardAmount;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return $this
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
    public function getSourceUserId()
    {
        return $this->_sourceUserId;
    }

    /**
     * @param int $sourceUserId
     * @return Acquisition
     */
    public function setSourceUserId($sourceUserId)
    {
        $this->_sourceUserId = $sourceUserId;
        return $this;
    }

    /**
     * @return string
     */
    public function getReferralUserEmail()
    {
        return $this->_referralUserEmail;
    }

    /**
     * @param string $referralUserEmail
     * @return Acquisition
     */
    public function setReferralUserEmail($referralUserEmail)
    {
        $this->_referralUserEmail = $referralUserEmail;
        return $this;
    }

    /**
     * @return int
     */
    public function getAcquiredUserId()
    {
        return $this->_acquiredUserId;
    }

    /**
     * @param int $acquiredUserId
     * @return Acquisition
     */
    public function setAcquiredUserId($acquiredUserId)
    {
        $this->_acquiredUserId = $acquiredUserId;
        return $this;
    }

    /**
     * @return int
     */
    public function getReferralTokenId()
    {
        return $this->_referralTokenId;
    }

    /**
     * @param int $referralTokenId
     * @return Acquisition
     */
    public function setReferralTokenId($referralTokenId)
    {
        $this->_referralTokenId = $referralTokenId;
        return $this;
    }

    /**
     * Returns a potential value associated to the reward option
     * @return null|int
     */
    public function getRewardAmount()
    {
        return $this->_rewardAmount;
    }

    /**
     * Sets the potential value associated to a reward option
     * @param int $rewardAmount
     * @return Acquisition
     */
    public function setRewardAmount($rewardAmount)
    {
        $this->_rewardAmount = $rewardAmount;
        return $this;
    }
}

