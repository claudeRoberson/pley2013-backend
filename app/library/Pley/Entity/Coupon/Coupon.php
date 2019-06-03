<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Coupon;

use Pley\DataMap\Entity;
use Pley\DataMap\Entity\Timestampable;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>Coupon</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileen1gine.com)
 * @version 1.0
 * @package Pley.Entity.Coupon
 * @subpackage Entity
 * @Meta\Table(name="coupon")
 */
class Coupon extends Entity
{
    use Timestampable;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $id;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="code")
     */
    protected $code;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="type")
     */
    protected $type;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="enabled")
     */
    protected $enabled;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="special")
     */
    protected $special;
    /**
     * @var float
     * @Meta\Property(fillable=true, column="discount_amount")
     */
    protected $discountAmount;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="subscription_id")
     */
    protected $subscriptionId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="max_usages")
     */
    protected $maxUsages;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="usages_per_user")
     */
    protected $usagesPerUser;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="min_boxes")
     *
     */
    protected $minBoxes;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="title")
     *
     */
    protected $title;

    /**
     * @var string
     * @Meta\Property(fillable=true, column="subtitle")
     *
     */
    protected $subtitle;

    /**
     * @var string
     * @Meta\Property(fillable=true, column="label_url")
     *
     */
    protected $labelUrl;

    /**
     * @var string
     * @Meta\Property(fillable=true, column="description")
     *
     */
    protected $description;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="expires_at")
     *
     */
    protected $expiresAt;

    public function getId()
    {
        return $this->id;
    }

    /** @param int $id */
    public function setId($id)
    {
        if (isset($this->id)) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, '_id');
        }
        $this->id = $id;
    }

    /** @return string */
    public function getCode()
    {
        return $this->code;
    }

    /** @param string $code */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return int
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    /**
     * @param int $subscriptionId
     * @return Coupon
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountAmount()
    {
        return (float)$this->discountAmount;
    }

    /**
     * @param float $discountAmount
     * @return Coupon
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->discountAmount = (float)$discountAmount;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Coupon
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param int $enabled
     * @return Coupon
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return int
     */
    public function getSpecial()
    {
        return $this->special;
    }

    /**
     * @param int $special
     * @return Coupon
     */
    public function setSpecial($special)
    {
        $this->special = $special;
        return $this;
    }

    /**
     * @see Coupon::getEnabled()
     * @return bool
     */
    public function isEnabled()
    {
        return $this->getEnabled();
    }

    /**
     * @see Coupon::setEnabled()
     * @param bool $enabled
     * @return Coupon
     */
    public function setIsEnabled($enabled)
    {
        return $this->setEnabled($enabled);
    }

    /**
     * @return int
     */
    public function getMaxUsages()
    {
        return $this->maxUsages;
    }

    /**
     * @param int $maxUsages
     * @return Coupon
     */
    public function setMaxUsages($maxUsages)
    {
        $this->maxUsages = $maxUsages;
        return $this;
    }

    /**
     * @return int
     */
    public function getUsagesPerUser()
    {
        return ($this->usagesPerUser) ? $this->usagesPerUser : 1;
    }

    /**
     * @param int $usagesPerUser
     * @return Coupon
     */
    public function setUsagesPerUser($usagesPerUser)
    {
        $this->usagesPerUser = $usagesPerUser;
        return $this;
    }

    /**
     * @return int
     */
    public function getMinBoxes()
    {
        return $this->minBoxes;
    }

    /**
     * @param int $minBoxes
     * @return Coupon
     */
    public function setMinBoxes($minBoxes)
    {
        $this->minBoxes = $minBoxes;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Coupon
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }

    /**
     * @param string $subtitle
     * @return Coupon
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabelUrl()
    {
        return $this->labelUrl;
    }

    /**
     * @param string $labelUrl
     * @return Coupon
     */
    public function setLabelUrl($labelUrl)
    {
        $this->labelUrl = $labelUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Coupon
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpiresAt()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->expiresAt);
    }

    /**
     * @param int $expiresAt
     * @return Coupon
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        if ($this->getExpiresAt() !== null && $this->getExpiresAt() < time()) {
            return true;
        }
        return false;
    }
}
