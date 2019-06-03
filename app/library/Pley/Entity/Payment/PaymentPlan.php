<?php /** @copyright Pley (c) 2014, All Rights Reserved */

namespace Pley\Entity\Payment;

/**
 * The <kbd>PaymentPlan</kbd> entity.
 *
 * @author Anurag Phadke (anuragp@pley.com)
 * @version 1.0
 * @package Pley.Entity.Payment
 * @subpackage Entity
 */
class PaymentPlan
{
    /** @var int */
    protected $_id;
    /** @var int */
    protected $_subscriptionId;
    /** @var string */
    protected $_title;
    /** @var string */
    protected $_description;
    /** @var int */
    protected $_period;
    /** @var int */
    protected $_periodUnit;
    /** @var int */
    protected $_sortOrder;
    /** @var int */
    protected $_isFeatured;

    public function __construct(
        $id,
        $subscriptionId,
        $title,
        $description,
        $period,
        $periodUnit,
        $sortOrder,
        $isFeatured)
    {
        $this->_id = $id;
        $this->_subscriptionId = $subscriptionId;
        $this->_title = $title;
        $this->_description = $description;
        $this->_period = $period;
        $this->_periodUnit = $periodUnit;
        $this->_sortOrder = $sortOrder;
        $this->_isFeatured = $isFeatured;
    }

    /** @return int */
    public function getId()
    {
        return $this->_id;
    }

    /** @return int */
    public function getSubscriptionId()
    {
        return $this->_subscriptionId;
    }

    /** @return string */
    public function getDescription()
    {
        return $this->_description;
    }

    /** @return string */
    public function getTitle()
    {
        return $this->_title;
    }

    /** @return int */
    public function getPeriod()
    {
        return $this->_period;
    }

    /** @return int */
    public function getPeriodUnit()
    {
        return $this->_periodUnit;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /**
     * @return int
     */
    public function getIsFeatured()
    {
        return $this->_isFeatured;
    }

}
