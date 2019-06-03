<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\DataMap\Entity;
/**
 * Class description goes here
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
interface FillableInterface
{
    /**
     * Returns an array of properties, which can be mass assigned
     * @return []
     */
    public function fillable();

    /**
     * Hydrates an entity using it's setters or properties from a given data array.
     * @param $data []
     * @return $this
     */
    public function fill($data);

}