<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

/**
 * Structure to hold information about user's credit within a payment system
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */

namespace Pley\Payment;

use Pley\Entity\Jsonable;

class CreditData
{
    use Jsonable;
    /**
     * @var float
     */
    protected $amount = 0.00;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var int
     */
    protected $createdAt;


    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return CreditData
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     * @return CreditData
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     * @return CreditData
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}