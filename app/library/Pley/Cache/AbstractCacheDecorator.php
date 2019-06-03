<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Cache;

/**
 * The <kbd>AbstractCacheDecorator</kbd> defines common methods to be used when decorating cache to
 * allow multi-layer caching.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Cache
 * @subpackage Cache
 */
abstract class AbstractCacheDecorator extends AbstractCache
{
    /**
     * The Cache object to be decorated.
     * @var \Pley\Cache\CacheInterface
     */
    protected $_decoratedCache;
    
    public function __construct(CacheInterface $decoratedCache)
    {
        $this->_decoratedCache = $decoratedCache;
    }
    
    /**
     * Setter receives the name of the DAO class that will use this cache to be able to add such
     * string as a prefix to the keys and thus avoid collision of keys in case the a shared caching
     * mechanism, like Memcache or APC.
     * 
     * @param string $classNamespace
     */
    public function setNamespace($classNamespace)
    {
        $this->_decoratedCache->setNamespace($classNamespace);
        $this->_classNamespace = $classNamespace;
    }
    
    // ---------------------------------------------------------------------------------------------
    // Sample implmenentation for concrete decorators ----------------------------------------------

//    public function has($key)
//    {
//        $nsKey = $this->_getNamespacedKey($key);
//        if ($this->_decoratedCache->has($nsKey)) {
//            return true;
//        }
//        
//        // `cacheMechanism` is just a sample variable which represents the internal object of the
//        // concrete class that will handle the respective method
//        return $this->_cacheMechanism->has($nsKey);
//    }
    
//    public function get($key)
//    {
//        $nsKey = $this->_getNamespacedKey($key);
//        if ($this->_decoratedCache->has($nsKey)) {
//            return $this->_decoratedCache->get($nsKey);
//        }
//        
//        // `cacheMechanism` is just a sample variable which represents the internal object of the
//        // concrete class that will handle the respective method
//        $obj = null;
//        if ($this->_cacheMechanism->has($nsKey)) {
//            $obj = $this->_cacheMechanism->get($nsKey);
//            $this->_decoratedCache->set($key, $obj);
//        }
//        
//        return $obj;
//    }
    
//    public function set($key, $value, $expiry = 0)
//    {
//        $this->_decoratedCache->set($key, $value, $expiry);
//        
//        // `cacheMechanism` is just a sample variable which represents the internal object of the
//        // concrete class that will handle the respective method
//        $nsKey = $this->_getNamespacedKey($key);
//        $this->_cacheMechanism->set($nsKey, $value, $expiry);
//    }
    
//    public function delete($key)
//    {
//        $this->_decoratedCache->delete($key);
//        
//        // `cacheMechanism` is just a sample variable which represents the internal object of the
//        // concrete class that will handle the respective method
//        $nsKey = $this->_getNamespacedKey($key);
//        $this->_cacheMechanism->delete($nsKey);
//    }
}
