<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Referral;

/**
 * The <kbd>Token</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Referral
 * @Meta\Table(name="referral_token")
 */

use Pley\DataMap\Entity;
use Pley\DataMap\Entity\Timestampable;
use Pley\DataMap\Annotations\Meta;

use Pley\Enum\Referral\TokenEnum;

class Token extends Entity
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
     * @var string
     * @Meta\Property(fillable=true, column="token")
     */
    protected $_token;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="referral_program_id")
     */
    protected $_referralProgramId;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="token_type_id")
     */
    protected $_tokenTypeId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="active")
     */
    protected $_active;
    /**
     * @var Acquisition[]
     */
    protected $_acquisitions = [];

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
     * @return Token
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
     * @return Token
     */
    public function setReferralUserEmail($referralUserEmail)
    {
        $this->_referralUserEmail = $referralUserEmail;
        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * @param string $token
     * @return Token
     */
    public function setToken($token)
    {
        $this->_token = $token;
        return $this;
    }

    /**
     * @return int
     */
    public function getReferralProgramId()
    {
        return $this->_referralProgramId;
    }

    /**
     * @param int $referralProgramId
     * @return Token
     */
    public function setReferralProgramId($referralProgramId)
    {
        $this->_referralProgramId = $referralProgramId;
        return $this;
    }

    /**
     * @return string
     */
    public function getTokenTypeId()
    {
        return $this->_tokenTypeId;
    }

    /**
     * @param string $tokenTypeId
     * @return Token
     */
    public function setTokenTypeId($tokenTypeId)
    {
        $this->_tokenTypeId = $tokenTypeId;
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
     * @return Token
     */
    public function setActive($active)
    {
        $this->_active = $active;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->getActive() == 1;
    }

    /**
     * @param int $active
     * @return Token
     */
    public function setIsActive($active)
    {
        return $this->setIsActive($active);
    }

    /**
     * @return Acquisition[]
     */
    public function getAcquisitions()
    {
        return $this->_acquisitions;
    }

    /**
     * @param Acquisition[] $acquisitions
     * @return Token
     */
    public function setAcquisitions($acquisitions)
    {
        $this->_acquisitions = $acquisitions;
        return $this;
    }

    /**
     * @return string
     */
    public function getTokenTypeLabel()
    {
        return TokenEnum::asString($this->getTokenTypeId());
    }
}

