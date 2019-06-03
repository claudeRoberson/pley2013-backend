<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\User;

/**
 * The <kbd>UserNps</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity.User
 * @subpackage UserNps
 * @Meta\Table(name="user_nps")
 */

use Pley\DataMap\Entity;
use Pley\DataMap\Entity\Timestampable;
use Pley\DataMap\Annotations\Meta;

class UserNps extends Entity
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
     * @var int
     * @Meta\Property(fillable=true, column="survey_scheduled_at")
     */
    protected $_surveyScheduledAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return UserNps
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
     * @return UserNps
     */
    public function setUserId($userId)
    {
        $this->_userId = $userId;
        return $this;
    }

    /**
     * @return int
     */
    public function getSurveyScheduledAt()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->_surveyScheduledAt);
    }

    /**
     * @param int $surveyScheduledAt
     * @return UserNps
     */
    public function setSurveyScheduledAt($surveyScheduledAt)
    {
        $this->_surveyScheduledAt = \Pley\Util\Time\DateTime::date($surveyScheduledAt);
        return $this;
    }
}

