<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Referral;

/**
 * The <kbd>Reward</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Referral
 * @Meta\Table(name="referral_reward")
 */

use Pley\DataMap\Entity;
use Pley\DataMap\Entity\Timestampable;
use Pley\DataMap\Annotations\Meta;
use Pley\Enum\Referral\RewardEnum;

class Reward extends Entity
{
    use Timestampable;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="user_id")
     */
    protected $_userId;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="referral_user_email")
     */
    protected $_referralUserEmail;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="status_id")
     */
    protected $_statusId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="acquired_users_num")
     */
    protected $_acquiredUsersNum;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="rewarded_option_id")
     */
    protected $_rewardedOptionId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="rewarded_comment")
     */
    protected $_rewardedComment;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="rewarded_at")
     */
    protected $_rewardedAt;
    /**
     * @var Token[]
     */
    protected $_tokens = [];
    /**
     * @var RewardOption
     */
    protected $_rewardedOption;

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
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
     * @param int $userId
     * @return Reward
     */
    public function setUserId($userId)
    {
        $this->_userId = $userId;
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
     * @return Reward
     */
    public function setReferralUserEmail($referralUserEmail)
    {
        $this->_referralUserEmail = $referralUserEmail;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatusId()
    {
        return $this->_statusId;
    }

    /**
     * @param int $statusId
     * @return Reward
     */
    public function setStatusId($statusId)
    {
        $this->_statusId = $statusId;
        return $this;
    }

    /**
     * @return int
     */
    public function getAcquiredUsersNum()
    {
        return $this->_acquiredUsersNum;
    }

    /**
     * @param int $acquiredUsersNum
     * @return Reward
     */
    public function setAcquiredUsersNum($acquiredUsersNum)
    {
        $this->_acquiredUsersNum = $acquiredUsersNum;
        return $this;
    }

    /**
     * @return string
     */
    public function getRewardedOptionId()
    {
        return $this->_rewardedOptionId;
    }

    /**
     * @param string $rewardedOptionId
     * @return Reward
     */
    public function setRewardedOptionId($rewardedOptionId)
    {
        $this->_rewardedOptionId = $rewardedOptionId;
        return $this;
    }

    /**
     * @return int
     */
    public function getRewardedComment()
    {
        return $this->_rewardedComment;
    }

    /**
     * @param int $rewardedComment
     * @return Reward
     */
    public function setRewardedComment($rewardedComment)
    {
        $this->_rewardedComment = $rewardedComment;
        return $this;
    }

    /**
     * @return int
     */
    public function getRewardedAt()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->_rewardedAt);
    }

    /**
     * @param int $rewardedAt
     * @return Reward
     */
    public function setRewardedAt($rewardedAt)
    {
        $this->_rewardedAt = $rewardedAt;
        return $this;
    }

    /**
     * @return Token[]
     */
    public function getTokens()
    {
        return $this->_tokens;
    }

    /**
     * @param Token[] $tokens
     * @return Reward
     */
    public function setTokens($tokens)
    {
        $this->_tokens = $tokens;
        return $this;
    }

    /**
     * @return RewardOption
     */
    public function getRewardedOption()
    {
        return $this->_rewardedOption;
    }

    /**
     * @param RewardOption $rewardedOption
     * @return Reward
     */
    public function setRewardedOption($rewardedOption)
    {
        $this->_rewardedOption = $rewardedOption;
        return $this;
    }

}

