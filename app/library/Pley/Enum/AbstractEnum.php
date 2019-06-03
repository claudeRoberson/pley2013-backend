<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Enum;

/**
 * The <kbd>AbstractEnum</kbd> replicates the functionality behind SplEnum to provide
 * type hinting of enums.
 * <p>It allows to have stronger checks in code to increse maintainability.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
class AbstractEnum
{
    /**
     * Store existing constants in a static cache per Class.
     * @var array
     */
    protected static $_cache = [];

    private function __construct() {}
    
    /**
     * Returns a Map of the constant values declared on this Enum.
     * @return array Map of constant name to value.
     */
    public static function constantsMap()
    {
        $cacheKey = get_called_class();
        if (empty(static::$_cache[$cacheKey])) {
            static::$_cache[$cacheKey] = (new \ReflectionClass(get_called_class()))->getConstants();
        }
        
        return static::$_cache[$cacheKey];
    }
    
    /**
     * Checks if the supplied value is supported by the constants in this Enum.
     * @param mixed $value
     * @return boolean Returns <kbd>true</kbd> if value is supported, otherwise the exception is thrown.
     * @throws \UnexpectedValueException if the value is not supported by this enum
     */
    public static function validate($value)
    {
        $inConstants = in_array($value, static::constantsMap(), true);
        
        if (!$inConstants) {
            throw new \UnexpectedValueException("Value `{$value}` is not part of the enum " . get_called_class());
        }
        
        return true;
    }
}
