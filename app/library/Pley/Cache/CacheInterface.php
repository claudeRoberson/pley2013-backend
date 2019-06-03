<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Cache;

/**
 * The <kbd>CacheInterface</kbd> defines the common methods shared across different Cache Mechanisms
 * to allow for easy Dependency Injection and consistency of method calls across Cached Decorators.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Cache
 * @subpackage Cache
 */
interface CacheInterface
{
    /**
     * Setter receives the name of the DAO class that will use this cache to be able to add such
     * string as a prefix to the keys and thus avoid collision of keys in case the a shared caching
     * mechanism, like Memcache or APC.
     * 
     * @param string $classNamespace
     */
    public function setNamespace($classNamespace);
    
    /**
     * Returns whether a given key exists on this Cache instance.
     * @param string $key
     * @return boolean <kbd>true</kbd> if key exists, <kbd>false</kbd> otherwise.
     */
    public function has($key);
    
    /**
     * Returns the value associated to the supplied key from this Cache instance.
     * <p>When a <kbd>null</kbd> value is returned, it could mean that either the key is not set on
     * the In-Memory cache, or that the key associated value is actually <kbd>null</kbd>, to
     * know which one is it, use the `<kbd>_hasCacheKey()</kbd>` method.</p>
     * 
     * @param string $key
     * @return mixed|null
     * @see ::has()
     */
    public function get($key);
    
    /**
     * Sets a value for a given key on this Cache instance.
     * 
     * @param string $key
     * @param mixed  $value
     * @param int    $expiry [Optional]<br/>Default 0 (infinite)
     */
    public function set($key, $value, $expiry = 0);
    
    /**
     * Removes the given key from this Cache instance.
     * <p><b>Note</b>: This is different from setting the key with a <kbd>null</kbd> value, doing so
     * just mean the key is still set but has <kbd>null</kbd> as the associated value.</p>
     * 
     * @param string $key
     */
    public function delete($key);
}
