<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Entity\Subscription;

use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Pley\Entity\Jsonable;

/**
 * The <kbd>Item</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Subscription
 * @subpackage Entity
 */
class Item implements ArrayableInterface, JsonableInterface
{
    use Jsonable;

    /** @var int */
    protected $_id;
    /** @var string */
    protected $_name;
    /** @var string */
    protected $_description;
    /** @var int */
    protected $_lengthCm;
    /** @var int */
    protected $_widthCm;
    /** @var int */
    protected $_heightCm;
    /** @var int */
    protected $_weightGr;

    /** @var \Pley\Entity\Subscription\ItemPart[] */
    private $_partList;

    public function __construct($id, $name, $description, $lengthCm, $widthCm, $heightCm, $weightGr)
    {
        $this->_id          = $id;
        $this->_name        = $name;
        $this->_description = $description;
        $this->_lengthCm    = $lengthCm;
        $this->_widthCm     = $widthCm;
        $this->_heightCm    = $heightCm;
        $this->_weightGr    = $weightGr;
    }

    /** @return int */
    public function getId()
    {
        return $this->_id;
    }

    /** @return string */
    public function getName()
    {
        return $this->_name;
    }

    /** @return string */
    public function getDescription()
    {
        return $this->_description;
    }

    /** @return int */
    public function getLengthCm()
    {
        return $this->_lengthCm;
    }

    /** @return int */
    public function getWidthCm()
    {
        return $this->_widthCm;
    }

    /** @return int */
    public function getHeightCm()
    {
        return $this->_heightCm;
    }

    /** @return int */
    public function getWeightGr()
    {
        return $this->_weightGr;
    }

    // ---------------------------------------------------------------------------------------------

    /** ♰
     * @return \Pley\Entity\Subscription\ItemPart[]
     */
    public function getPartList()
    {
        return $this->_partList;
    }

    /** ♰
     * @param $partList \Pley\Entity\Subscription\ItemPart[]
     */
    public function setPartList($partList)
    {
        $this->_partList = $partList;
    }
}
