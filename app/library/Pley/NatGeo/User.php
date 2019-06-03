<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\NatGeo;

/**
 * The <kbd>User</kbd> class represents a NatGeo User data.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.NatGeo
 * @subpackage NatGeo
 */
class User
{
    /** @var int */
    protected $_id;
    /** @var int */
    protected $_createdAt;
    /** @var \Pley\NatGeo\Mission[] */
    protected $_missionsMap = [];
    
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

    /** @return \Pley\NatGeo\Mission[] */
    public function getMissionsMap()
    {
        return $this->_missionsMap;
    }

    /**
     * Add a new mission to this object reference map.
     * @param \Pley\NatGeo\Mission $mission
     */
    public function addMission(Mission $mission)
    {
        $this->_missionsMap[$mission->getId()] = $mission;
    }
}
