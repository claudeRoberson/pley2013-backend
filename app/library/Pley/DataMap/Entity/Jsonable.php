<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\DataMap\Entity;

use Illuminate\Support\Contracts\ArrayableInterface;

/**
 * Trait, which implements toArray method for casting object to JSON
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
trait Jsonable
{
    /**
     * Defines a behavior of casting an object to array
     * @return array
     */
    public function toArray()
    {
        $array = [];
        foreach (get_class_methods($this) as $method) {
            if (substr($method, 0, 3) != 'get') {
                continue;
            }
            
            $key   = lcfirst(substr($method, 3));
            $value = $this->{$method}();

            $array[$key] = $this->_getJsonableDeepParse($value);
        }
        return $array;
    }

    public function toJson($options = 0)
    {
        return $this->toArray();
    }
    
    // Though some IDEs may show this method as unused, DO NOT DELETE, it is used by the `toArray()` method
    /**
     * Parses a the supplied value given it's type.
     * @param mixed $value
     * @return mixed
     */
    private function _getJsonableDeepParse($value)
    {
        if (is_object($value) && $value instanceof ArrayableInterface) {
            return $value->toArray();
        }
        
        if (is_array($value)) {
            $parsedArray = [];
            foreach ($value as $key => $subValue) {
                $parsedArray[$key] = $this->_getJsonableDeepParse($subValue);
            }
            
            return $parsedArray;
        }
        
        return $value;
    }
}