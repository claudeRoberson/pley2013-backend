<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\NatGeo;

/**
 * The <kbd>Mission</kbd> class represents a NatGeo Mission data.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.NatGeo
 * @subpackage NatGeo
 */
class Mission
{
    /** @var int */
    protected $_id;
    /** @var int */
    protected $_createdAt;
    /** @var int */
    protected $_completedAt;
    
    public function __construct($id, $createdAt)
    {
        $this->_id        = $id;
        $this->_createdAt = $createdAt;
        
        if (!is_int($this->_createdAt)) {
            $this->_createdAt = strtotime($this->_createdAt);
        }
    }

    /** @return int */
    public function getId()
    {
        return $this->_id;
    }

    /** @return int */
    public function getCreatedAt()
    {
        return $this->_createdAt;
    }

    /** @return int */
    public function getCompletedAt()
    {
        return $this->_completedAt;
    }

    /** @param int $completedAt */
    public function setCompletedAt($completedAt)
    {
        $this->_completedAt = $completedAt;
        
        if (!is_int($this->_completedAt)) {
            $this->_completedAt = strtotime($this->_completedAt);
        }
    }
}
