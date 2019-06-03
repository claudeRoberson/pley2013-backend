<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Dao\Traits;

use \Pley\Cache\CacheInterface;

/**
 * The <kbd>CacheDaoTrait</kbd> provides the implementation for the <kbd>CacheDaoInterface</kbd> so
 * it is easily provided to any DAO that extends such interface without the need of extending a
 * specific abstract class.
 * <p>This is useful in case the DAO implements interfaces but would otherwise would only be able to
 * extends one abstract class and have to provide implementation for the other interface.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Dao.Traits
 * @subpackage Dao
 */
trait CacheDaoTrait
{
    /**
     * Instance of the Cache Mechanism
     * @var \Pley\Cache\CacheInterface
     */
    protected $_cache;
    
    /**
     * Sets the Cache that will be used as means of interacting with the Cache Mechanism.
     * 
     * @param \Pley\Cache\CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        $cache->setNamespace(get_class($this));
        $this->_cache = $cache;
    }
}
