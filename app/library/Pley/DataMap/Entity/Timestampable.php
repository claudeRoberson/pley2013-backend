<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\DataMap\Entity;

use Pley\DataMap\Annotations\Meta;

/**
 * Trait, which implements createdAt and updatedAt properties
 * and getters.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
trait Timestampable
{
    /**
     * @var int
     * @Meta\Property(fillable=false, column="created_at")
     */
    protected $_createdAt;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="updated_at")
     */
    protected $_updatedAt;

    /**
     * @return int
     */
    public function getCreatedAt()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->_createdAt);
    }

    /**
     * @return int
     */
    public function getUpdatedAt()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->_updatedAt);
    }
}