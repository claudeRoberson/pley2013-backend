<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Shipping;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;

/**
 * The <kbd>Rate</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Shipping
 * @Meta\Table(name="shipping_zone")
 */
class Zone extends Entity
{
    /**
     * @var int
     * @Meta\Property(fillable=false, column="id")
     */
    protected $_id;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="country")
     */
    protected $_country;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="state")
     */
    protected $_state;
    /**
     * @var string
     * @Meta\Property(fillable=true, column="zip")
     */
    protected $_zip;
    /**
     * @var int
     * @Meta\Property(fillable=true, column="name")
     */
    protected $_name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return Zone
     */
    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->_country;
    }

    /**
     * @param string $country
     * @return Zone
     */
    public function setCountry($country)
    {
        $this->_country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     * @param string $state
     * @return Zone
     */
    public function setState($state)
    {
        $this->_state = $state;
        return $this;
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return $this->_zip;
    }

    /**
     * @param string $zip
     * @return Zone
     */
    public function setZip($zip)
    {
        $this->_zip = $zip;
        return $this;
    }

    /**
     * @return int
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param int $name
     * @return Zone
     */
    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }
}