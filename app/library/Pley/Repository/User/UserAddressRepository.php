<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Repository\User;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Dao\User\UserAddressDao;
use \Pley\Entity\User\UserAddress;
use \Pley\Repository\Exception\ExistingEntityException;
use \Pley\Repository\Exception\EntityNotFoundException;

/**
 * The <kbd>UserAddressRepository</kbd> handles operations related to the User Adresss objects
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Repository.User
 * @subpackage Repository
 */
class UserAddressRepository
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Dao\User\UserAddressDao */
    protected $_userAddressDao;
    
    public function __construct(Config $config, UserAddressDao $userAddresssDao)
    {
        $this->_config         = $config;
        $this->_userAddressDao = $userAddresssDao;
    }
    
    /**
     * Return a <kbd>UserAddress</kbd> Entity for the supplied User.
     * <p>Currently we only support only one address per customer and as such</p>
     * @param int $userId
     * @return \Pley\Entity\User\UserAddress
     */
    public function find($id)
    {
        $addressList = $this->_userAddressDao->find($id);
        
        if (empty($addressList)) {
            return null;
        }
        
        return $addressList;
    }
    
    public function findByUserId($userId)
    {
        $addressList = $this->_userAddressDao->findByUser($userId);
        
        if (empty($addressList)) {
            return null;
        }
        
        return $addressList;
    }
    
    
    public function findFirst($userId)
    {
        $addressList = $this->_userAddressDao->findByUser($userId);
        
        if (empty($addressList)) {
            return null;
        }
        
        return $addressList[0];
    }
    
    
    
    /**
     * Creates a new user address and returns an new Entity that contains the newly generated id of it.
     * @param \Pley\Entity\User\UserAddress $userAddress
     */
    public function add(UserAddress $userAddress)
    {
        // If the id is available, this will produce an update instead of an insert
        if (!empty($userAddress->getId())) {
            throw new ExistingEntityException(UserAddress::class, $userAddress->getId());
        }
        
        $this->_setUspsShippingZoneId($userAddress);
        $this->_userAddressDao->save($userAddress);
    }
    
    /**
     * Updates a user address.
     * @param \Pley\Entity\User\UserAddress $userAddress
     */
    public function update(UserAddress $userAddress)
    {
        // If the id is not available, throw an exception
        if (empty($userAddress->getId())) {
            throw new EntityNotFoundException(UserAddress::class, $userAddress->getId());
        }
        
        $this->_setUspsShippingZoneId($userAddress);
        $this->_userAddressDao->save($userAddress);
    }

    /**
     * Sets the USPS Shipping zone for the User's Address if there is a zone for it.
     * <p>Used mainly to set the zone value when Adding or Updating a user's address.</p>
     * @param \Pley\Entity\User\UserAddress $userAddress
     */
    private function _setUspsShippingZoneId(UserAddress $userAddress)
    {
        $shippingZone = new \Pley\Util\Shipping\UspsShippingZone($this->_config);
        $zipCode = $userAddress->getZipCode();
        $uspsShippingZoneId = $shippingZone->getUspsShippingZoneId($zipCode);
        $userAddress->setUspsShippingZoneId($uspsShippingZoneId);
    }
}
