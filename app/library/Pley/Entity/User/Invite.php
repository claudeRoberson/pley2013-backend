<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\User;

/**
 * The <kbd>Invite</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity.User
 * @subpackage Invite
 * @Meta\Table(name="user_invite")
 */

use Pley\DataMap\Entity;
use Pley\DataMap\Entity\Timestampable;
use Pley\DataMap\Annotations\Meta;

class Invite extends Entity
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
     * @Meta\Property(fillable=true, column="referral_token_id")
     */
    protected $_referralTokenId;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="invite_name")
     */
    protected $_inviteName;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="invite_email")
     */
    protected $_inviteEmail;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="status")
     */
    protected $_status;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="reminder_count")
     */
    protected $_reminderCount;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="reminder_last_date")
     */
    protected $_reminderLastDate;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return Invite
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
     * @return Invite
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
     * @return Invite
     */
    public function setReferralUserEmail($referralUserEmail)
    {
        $this->_referralUserEmail = $referralUserEmail;
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
     * @return Invite
     */
    public function setReferralTokenId($referralTokenId)
    {
        $this->_referralTokenId = $referralTokenId;
        return $this;
    }

    /**
     * @return string
     */
    public function getInviteName()
    {
        return $this->_inviteName;
    }

    /**
     * @param string $inviteName
     * @return Invite
     */
    public function setInviteName($inviteName)
    {
        $this->_inviteName = $inviteName;
        return $this;
    }

    /**
     * @return string
     */
    public function getInviteEmail()
    {
        return $this->_inviteEmail;
    }

    /**
     * @param string $inviteEmail
     * @return Invite
     */
    public function setInviteEmail($inviteEmail)
    {
        $this->_inviteEmail = $inviteEmail;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @param int $status
     * @return Invite
     */
    public function setStatus($status)
    {
        $this->_status = $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getReminderCount()
    {
        return $this->_reminderCount;
    }

    /**
     * @param int $reminderCount
     * @return Invite
     */
    public function setReminderCount($reminderCount)
    {
        $this->_reminderCount = $reminderCount;
        return $this;
    }

    /**
     * @return int
     */
    public function getReminderLastDate()
    {
        return $this->_reminderLastDate;
    }

    /**
     * @param int $reminderLastDate
     * @return Invite
     */
    public function setReminderLastDate($reminderLastDate)
    {
        $this->_reminderLastDate = $reminderLastDate;
        return $this;
    }
}

