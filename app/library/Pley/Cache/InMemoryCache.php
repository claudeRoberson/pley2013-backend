<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Cache;

/**
 * The <kbd>InMemoryCache</kbd> is a basic type of cache (will not decorate another cache), it is
 * used to store in the object instance on a specific instance of this class so to prevent going
 * to other storage or cache mechanisms.
 * <p>This class if not supplied alone, can be injected as part of a Cached Decorator DAO.</p>
 * <p>A good pattern example of decoration would be the following:<br/>
 * <code>MemCacheDecorator(InMemoryCache)</code></p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Cache
 * @subpackage Cache
 */
class InMemoryCache extends AbstractCache implements CacheInterface
{
    /**
     * Map used to store searched/added/updated objects in this class memory for this request.
     * </p>This approach allows to improve performance for it prevents from accessing the Storage
     * layer for values we have already retrieved.
     * <p>It is up to the implementation to decide what are the values to use for keys and the value
     * to add to the cache.</p>
     * @param array
     */
    protected $_inMemoryCache = [];
    
    /**
     * Returns whether a given key exists on this In-Memory Cache instance.
     * @param string $key
     * @return boolean <kbd>true</kbd> if key exists
     */
    public function has($key)
    {
        $namespacedKey = $this->_getNamespacedKey($key);
        
        // Though in most cases, it would be more efficient to call `isset()`, such method will
        // return `false` if the value associated to the key is `null`
        // So, we use `array_key_exists()` which slightly slower than `isset()` to make sure if the
        // actual key is set or not.
        return array_key_exists($namespacedKey, $this->_inMemoryCache);
    }
    
    /**
     * Returns the value associated to the supplied key from this In-Memory Cache instance.
     * <p>When a <kbd>null</kbd> value is returned, it could mean that either the key is not set on
     * the In-Memory cache, or that the key associated value is actually <kbd>null</kbd>, to
     * know which one is it, use the `<kbd>_hasCacheKey()</kbd>` method.</p>
     * 
     * @param string $key
     * @return mixed|null
     * @see ::has()
     */
    public function get($key)
    {
        $namespacedKey = $this->_getNamespacedKey($key);
        
        if (isset($this->_inMemoryCache[$namespacedKey])) {
            return $this->_inMemoryCache[$namespacedKey];
        }
        
        return null;
    }
    
    /**
     * Sets a value for a given key on this In-Memory Cache instance.
     * 
     * @param string $key
     * @param mixed  $value
     * @param int    $expiry [Optional]<br/>Default 0 (infinite)
     */
    public function set($key, $value, $expiry = 0)
    {
        $namespacedKey = $this->_getNamespacedKey($key);
        
        $this->_inMemoryCache[$namespacedKey] = $value;
    }
    
    /**
     * Removes the given key from this In-Memory Cache instance.
     * <p><b>Note</b>: This is different from setting the key with a <kbd>null</kbd> value, doing so
     * just mean the key is still set but has <kbd>null</kbd> as the associated value.</p>
     * 
     * @param string $key
     * @return mixed|null
     */
    public function delete($key)
    {
        $namespacedKey = $this->_getNamespacedKey($key);
        
        unset($this->_inMemoryCache[$namespacedKey]);
    }
}
