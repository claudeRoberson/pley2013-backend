<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Entity;

use Illuminate\Support\Contracts\ArrayableInterface;

/**
 * Class description goes here
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
            if (substr($method, 0, 3) == 'get') {
                $key = lcfirst(substr($method, 3));
                $value = $this->{$method}();
                if (is_object($value) && $value instanceof ArrayableInterface) {
                    $array[$key] = $value->toArray();
                    continue;
                } elseif (is_array($value)) {
                    foreach ($value as $arrayValKey => $valueItem) {
                        if (is_object($valueItem) && $valueItem instanceof ArrayableInterface) {
                            $array[$key][$arrayValKey] = $valueItem->toArray();
                            continue;
                        }
                        $array[$key][$arrayValKey] = $valueItem;
                        continue;
                    }
                } else {
                    $array[$key] = $value;
                }
            }
        }
        return $array;
    }

    public function toJson($options = 0)
    {
        return $this->toArray();
    }
}