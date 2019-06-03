<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Referral;

/**
 * The <kbd>Program</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Referral
 * @Meta\Table(name="referral_program")
 */

use Pley\DataMap\Entity;
use Pley\DataMap\Entity\Timestampable;
use Pley\DataMap\Annotations\Meta;

class Program extends Entity
{
    use Timestampable;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="name")
     */
    protected $_name;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="active")
     */
    protected $_active;
    /**
     * @var float
     * @Meta\Property(fillable=true, column="reward_credit")
     */
    protected $_rewardCredit;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="acquisition_coupon_id")
     */
    protected $_acquisitionCouponId;

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
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param string $name
     * @return Program
     */
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getActive()
    {
        return (bool)$this->_active;
    }
    /**
     * @return int
     */
    public function isActive()
    {
        return $this->getActive();
    }

    /**
     * @param int $active
     * @return Program
     */
    public function setIsActive($active)
    {
        $this->_active = $active;
        return $this;
    }

    /**
     * @return float
     */
    public function getRewardCredit()
    {
        return $this->_rewardCredit;
    }

    /**
     * @param float $rewardCredit
     * @return Program
     */
    public function setRewardCredit($rewardCredit)
    {
        $this->_rewardCredit = $rewardCredit;
        return $this;
    }

    /**
     * @return string
     */
    public function getAcquisitionCouponId()
    {
        return $this->_acquisitionCouponId;
    }

    /**
     * @param string $acquisitionCouponId
     * @return Program
     */
    public function setAcquisitionCouponId($acquisitionCouponId)
    {
        $this->_acquisitionCouponId = $acquisitionCouponId;
        return $this;
    }

}

