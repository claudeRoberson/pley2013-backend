<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\NatGeo\Auth;

/**
 * The <kbd>Auth</kbd> class stores credentials to authenticate against the NatGeo API.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.NatGeo.Auth
 * @subpackage NatGeo
 * @subpackage Auth
 */
class Auth
{
    /** @var string */
    private $_portalId;
    /** @var string */
    private $_password;
    
    public function __construct($protalId, $password)
    {
        $this->_portalId = $protalId;
        $this->_password = $password;
    }

    /**
     * Returns the ID to authenticate against the NatGeo API
     * @return string
     */
    public function getPortalId()
    {
        return $this->_portalId;
    }

    /**
     * Returns the Password to authenticate against the NatGeo API
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

}
