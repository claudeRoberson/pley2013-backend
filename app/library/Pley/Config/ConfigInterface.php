<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Config;

/**
 * The <kbd>ConfigInterface</kbd> defines methods to access data from a configuration file.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Config
 * @subpackage Config
 */
interface ConfigInterface extends \ArrayAccess
{
    /**
     * Determine if the given configuration value exists.
     *
     * @param string $key
     * @return boolean
     */
    public function has($key);
    
    /**
     * Determine if a configuration group exists.
     *
     * @param string $key
     * @return boolean
     */
    public function hasGroup($key);
    
    /**
     * Get the specified configuration value.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get($key, $default = null);
    
    /**
     * Set a given configuration value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value);
}
