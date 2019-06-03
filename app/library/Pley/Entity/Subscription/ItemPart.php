<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Entity\Subscription;

use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Pley\Entity\Jsonable;
/**
 * The <kbd>ItemPart</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Subscription
 * @subpackage Entity
 */
class ItemPart implements ArrayableInterface, JsonableInterface
{
    use Jsonable;

    /** @var int */
    protected $_id;
    /** @var int */
    protected $_itemId;
    /** @var string */
    protected $_name;
    /** @var int */
    protected $_type;
    /** @var boolean */
    protected $_isNeedMod;
    /** @var string */
    protected $_image;
    /** @var []*/
    protected $_stockItems;
    /**
     * Definition of the Stock for this Item Part.
     * <p>A stock can be either a single entry or a map that has multiple entries like shirts (sizes)</p>
     * @var int|array
     */
    private $_stockDef;

    public function __construct($id, $itemId, $name, $type, $isNeedMod, $image)
    {
        $this->_id        = $id;
        $this->_itemId    = $itemId;
        $this->_name      = $name;
        $this->_type      = $type;
        $this->_isNeedMod = $isNeedMod;
        $this->_image     = $image;
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

    /** @return string */
    public function getName()
    {
        return $this->_name;
    }

    /** @return int */
    public function getType()
    {
        return $this->_type;
    }

    /** @return boolean */
    public function isNeedMod()
    {
        return $this->_isNeedMod;
    }

    /** @return string */
    public function getImage()
    {
        return $this->_image;
    }

    // Additional Methods related to stock (to be loaded separately) -------------------------------

    /**
     * Sets the definition of the Stock for this Item Part.
     * <p>A stock can be either a single entry or a map that has multiple entries like shirts (sizes)</p>
     * @param int|array $stockDef
     */
    public function setStockDef($stockDef)
    {
        if (!is_int($stockDef) && !is_array($stockDef)) {
            throw new \InvalidArgumentException('Stock definition can be either Int or Array Map');
        }

        $this->_stockDef = $stockDef;
    }

    /**
     * Gets the definition of the Stock for this Item Part.
     * <p>A stock can be either a single entry or a map that has multiple entries like shirts (sizes)</p>
     * @return int|array
     */
    public function getStockDef()
    {
        return $this->_stockDef;
    }

    /** @return int */
    public function getStock()
    {
        if (!isset($this->_stockDef)) {
            return null;
        }

        // First assume it is a straight INT value.
        $stockAvailable = $this->_stockDef;
        
        // If it happens to be a map, then we just need to add up all the entries
        if (is_array($this->_stockDef)) {
            $stockAvailable = 0;
            foreach ($this->_stockDef as $amount) {
                $stockAvailable += $amount;
            }
        }

        return $stockAvailable;
    }

    /**
     * @return \Pley\Entity\Subscription\ItemPartStock[]
     */
    public function getStockItems()
    {
        return $this->_stockItems;
    }

    /**
     * @param mixed $stockItems
     * @return ItemPart
     */
    public function setStockItems($stockItems)
    {
        $this->_stockItems = $stockItems;
        return $this;
    }

}
