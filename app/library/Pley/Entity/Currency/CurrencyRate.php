<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity\Currency;

use Pley\DataMap\Entity;
use Pley\DataMap\Annotations\Meta;
use Pley\DataMap\Entity\Timestampable;

/**
 * The <kbd>CurrencyRate</kbd> entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Entity
 * @subpackage Currency
 * @Meta\Table(name="currency_rate")
 */
class CurrencyRate extends Entity
{
    use Timestampable;
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
     * @Meta\Property(fillable=true, column="code")
     */
    protected $_code;
    /**
     * @var float
     * @Meta\Property(fillable=true, column="rate")
     */
    protected $_rate;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     * @return CurrencyRate
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
     * @return CurrencyRate
     */
    public function setCountry($country)
    {
        $this->_country = $country;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * @param string $code
     * @return CurrencyRate
     */
    public function setCode($code)
    {
        $this->_code = $code;
        return $this;
    }

    /**
     * @return float
     */
    public function getRate()
    {
        return $this->_rate;
    }

    /**
     * @param float $rate
     * @return CurrencyRate
     */
    public function setRate($rate)
    {
        $this->_rate = $rate;
        return $this;
    }
}