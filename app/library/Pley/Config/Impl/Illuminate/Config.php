<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Config\Impl\Illuminate;

use \Illuminate\Config\Repository as ConfigRepo;

use \Pley\Config\ConfigInterface;

/**
 * The <kbd>Config</kbd> implements the <kbd>ConfigInterface</kbd> to provide a specific
 * implementation of the Configuration reader by using Laravel's Config mechanism.
 * <p>This class will be loaded into the IoC container through the service provider mechanism
 * exposed by <kbd>\Pley\Laravel\PleyConfigServiceProvider</kbd>.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Config.Impl.Illuminate
 * @subpackage Config
 */
class Config implements ConfigInterface
{
    /** @var \Illuminate\Config\Repository */
    protected $_configRepo;
    
    public function __construct(ConfigRepo $configRepo)
    {
        $this->_configRepo = $configRepo;
    }
    
    /**
     * Determine if the given configuration value exists.
     *
     * @param string $key
     * @return boolean
     */
    public function get($key, $default = null)
    {
        return $this->_configRepo->get($key, $default);
    }

    /**
     * Determine if a configuration group exists.
     *
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return $this->_configRepo->has($key);
    }

    /**
     * Get the specified configuration value.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function hasGroup($key)
    {
        return $this->_configRepo->hasGroup($key);
    }

    /**
     * Set a given configuration value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->_configRepo->set($key, $value);
    }

    /**
     * Whether a offset exists
     * @param mixed $offset An offset to check for.
     * @return boolean <kbd>TRUE</kbd> on success or <kbd>FALSE</kbd> on failure.
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     */
    public function offsetExists($offset)
    {
        return $this->_configRepo->offsetExists($offset);
    }

    /**
     * Offset to retrieve
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     */
    public function offsetGet($offset)
    {
        return $this->_configRepo->offsetGet($offset);
    }

    /**
     * Offset to set
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value  The value to set.
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     */
    public function offsetSet($offset, $value)
    {
        $this->_configRepo->offsetSet($offset, $value);
    }

    /**
     * Offset to unset
     * @param mixed $offset The offset to unset.
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset($offset)
    {
        $this->_configRepo->offsetUnset($offset);
    }
}
