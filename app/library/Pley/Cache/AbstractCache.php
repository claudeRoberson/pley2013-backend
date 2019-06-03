<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Cache;

/**
 * The <kbd>AbstractCache</kbd> defines the common methods shared across different Cache Mechanisms
 * to allow for easy Dependency Injection and consistency of method calls across Cached Decorators.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Cache
 * @subpackage Cache
 */
abstract class AbstractCache implements CacheInterface
{
    /**
     * String used to prefix all keys used by this Cache to avoid any collision of keys.
     * @var string
     */
    protected $_classNamespace;
    
    /**
     * Setter receives the name of the DAO class that will use this cache to be able to add such
     * string as a prefix to the keys and thus avoid collision of keys in case the a shared caching
     * mechanism, like Memcache or APC.
     * 
     * @param string $classNamespace
     */
    public function setNamespace($classNamespace)
    {
        $this->_classNamespace = $classNamespace;
    }
    
    /**
     * Helper method to prepend the class namespace string to the key.
     * @param string $key
     * @return string
     * @throws \PleyWorld\Cache\Exception\CacheNamespaceException If the `setNamespace()` method 
     *      has not been called before any method that needs to use a namespaced key.
     * @see ::setNamespace
     */
    protected function _getNamespacedKey($key)
    {
        if (empty($this->_classNamespace)) {
            throw new \Pley\Exception\Cache\CacheNamespaceException();
        }
        
        return $this->_classNamespace . '::' . $key;
    }
}
