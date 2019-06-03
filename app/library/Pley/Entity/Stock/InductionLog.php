<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Stock;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>InductionLog</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Stock
 * @Meta\Table(name="stock_induction_log")
 */
class InductionLog extends Entity
{
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="item_id")
     */
    protected $_itemId;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="item_part_id")
     */
    protected $_itemPartId;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="item_part_stock_id")
     */
    protected $_itemPartStockId;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="amount")
     */
    protected $_amount;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="comment")
     */
    protected $_comment;
    /**
     * @var int
     * @Meta\Property(fillable=false, column="created_at")
     */
    protected $_createdAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return InductionLog
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
    public function getItemId()
    {
        return $this->_itemId;
    }

    /**
     * @param int $itemId
     * @return InductionLog
     */
    public function setItemId($itemId)
    {
        $this->_itemId = $itemId;
        return $this;
    }

    /**
     * @return int
     */
    public function getItemPartId()
    {
        return $this->_itemPartId;
    }

    /**
     * @param int $itemPartId
     * @return InductionLog
     */
    public function setItemPartId($itemPartId)
    {
        $this->_itemPartId = $itemPartId;
        return $this;
    }

    /**
     * @return string
     */
    public function getItemPartStockId()
    {
        return $this->_itemPartStockId;
    }

    /**
     * @param string $itemPartStockId
     * @return InductionLog
     */
    public function setItemPartStockId($itemPartStockId)
    {
        $this->_itemPartStockId = $itemPartStockId;
        return $this;
    }

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->_amount;
    }

    /**
     * @param string $amount
     * @return InductionLog
     */
    public function setAmount($amount)
    {
        $this->_amount = $amount;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->_comment;
    }

    /**
     * @param string $comment
     * @return InductionLog
     */
    public function setComment($comment)
    {
        $this->_comment = $comment;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreatedAt()
    {
        return \Pley\Util\Time\DateTime::strToTime($this->_createdAt);
    }

}