<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Banner;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>RevealBanner</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Banner
 * @Meta\Table(name="reveal_banner")
 */
class RevealBanner extends Entity
{
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $id;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="enabled")
     */
    protected $enabled;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="before_timer_text")
     */
    protected $beforeTimerText;
    /**
     * @var float
     * @Meta\Property(fillable=true, column="before_timer_link")
     */
    protected $beforeTimerLink;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="timer_target")
     */
    protected $timerTarget;
    /**
     * @var float
     * @Meta\Property(fillable=true, column="after_timer_text")
     */
    protected $afterTimerText;
    /**
     * @var float
     * @Meta\Property(fillable=true, column="after_timer_link")
     */
    protected $afterTimerLink;
    /**
     * @var float
     * @Meta\Property(fillable=true, column="date_start_show")
     */
    protected $dateStartShow;
    /**
     * @var float
     * @Meta\Property(fillable=true, column="date_end_show")
     */
    protected $dateEndShow;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RevealBanner
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * @return RevealBanner
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return string
     */
    public function getBeforeTimerText()
    {
        return $this->beforeTimerText;
    }

    /**
     * @param string $beforeTimerText
     * @return RevealBanner
     */
    public function setBeforeTimerText($beforeTimerText)
    {
        $this->beforeTimerText = $beforeTimerText;
        return $this;
    }

    /**
     * @return float
     */
    public function getBeforeTimerLink()
    {
        return $this->beforeTimerLink;
    }

    /**
     * @param float $beforeTimerLink
     * @return RevealBanner
     */
    public function setBeforeTimerLink($beforeTimerLink)
    {
        $this->beforeTimerLink = $beforeTimerLink;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimerTargetDate()
    {
        return $this->timerTarget;
    }

    /**
     * @return int
     */
    public function getTimerTargetTime()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->timerTarget);
    }

    /**
     * @param int $timerTarget
     * @return RevealBanner
     */
    public function setTimerTargetTime($timerTarget)
    {
        return $this->setTimerTarget($timerTarget);
    }

    /**
     * @param int $timerTarget
     * @return RevealBanner
     */
    public function setTimerTarget($timerTarget)
    {
        $this->timerTarget = $timerTarget;
        return $this;
    }

    /**
     * @return float
     */
    public function getAfterTimerText()
    {
        return $this->afterTimerText;
    }

    /**
     * @param float $afterTimerText
     * @return RevealBanner
     */
    public function setAfterTimerText($afterTimerText)
    {
        $this->afterTimerText = $afterTimerText;
        return $this;
    }

    /**
     * @return float
     */
    public function getAfterTimerLink()
    {
        return $this->afterTimerLink;
    }

    /**
     * @param float $afterTimerLink
     * @return RevealBanner
     */
    public function setAfterTimerLink($afterTimerLink)
    {
        $this->afterTimerLink = $afterTimerLink;
        return $this;
    }

    /**
     * @return float
     */
    public function getDateStartShow()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->dateStartShow);
    }

    /**
     * @param float $dateStartShow
     * @return RevealBanner
     */
    public function setDateStartShow($dateStartShow)
    {
        $this->dateStartShow = $dateStartShow;
        return $this;
    }

    /**
     * @return float
     */
    public function getDateEndShow()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->dateEndShow);
    }

    /**
     * @param float $dateEndShow
     * @return RevealBanner
     */
    public function setDateEndShow($dateEndShow)
    {
        $this->dateEndShow = $dateEndShow;
        return $this;
    }
}