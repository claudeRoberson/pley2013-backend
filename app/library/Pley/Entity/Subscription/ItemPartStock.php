<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Entity\Subscription;

use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Pley\Entity\Jsonable;
use Pley\Enum\ItemPartEnum;
use Pley\Enum\ShirtSizeEnum;

/**
 * The <kbd>ItemPartStock</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Subscription
 * @subpackage Entity
 */
class ItemPartStock implements ArrayableInterface, JsonableInterface
{
    use Jsonable;

    /** @var int */
    protected $_id;
    /** @var int */
    protected $_itemId;
    /** @var int */
    protected $_itemPartId;
    /** @var int */
    protected $_type;
    /** @var int */
    protected $_typeSourceId;
    /** @var int */
    protected $_inductedStock;
    /** @var int */
    protected $_stock;

    public function __construct($id, $itemId, $itemPartId, $type, $typeSourceId, $inductedStock, $stock)
    {
        $this->_id            = $id;
        $this->_itemId        = $itemId;
        $this->_itemPartId    = $itemPartId;
        $this->_type          = $type;
        $this->_typeSourceId  = $typeSourceId;
        $this->_inductedStock = $inductedStock;
        $this->_stock         = $stock;
    }

    /** @return int */
    public function getId()
    {
        return $this->_id;
    }

    /** @return int */
    public function getItemId()
    {
        return $this->_itemId;
    }

    /** @return int */
    public function getItemPartId()
    {
        return $this->_itemPartId;
    }

    /** @return int */
    public function getType()
    {
        return $this->_type;
    }

    /** @return int */
    public function getTypeSourceId()
    {
        return $this->_typeSourceId;
    }

    /** @return int */
    public function getInductedStock()
    {
        return $this->_inductedStock;
    }

    /** @return int */
    public function getStock()
    {
        return $this->_stock;
    }

    /** @return int */
    public function getSizeName()
    {
        if ($this->_typeSourceId) {
            return ShirtSizeEnum::asString($this->_typeSourceId);
        }
        return null;
    }

    /** @return int */
    public function getTypeName()
    {
        if ($this->_type) {
            return ItemPartEnum::asString($this->_type);
        }
        return null;
    }
}
