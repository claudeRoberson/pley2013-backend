<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\DataMap\Annotations\Meta;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class, which implements an annotation for storing class property meta information
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * 
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Property extends Annotation
{
    /** @var boolean */
    public $fillable = true;
    /** @var string */
    public $column;

    /** @return boolean */
    public function isFillable()
    {
        return ((bool)$this->fillable) ? true : false;
    }

    /** @return string */
    public function getColumnName()
    {
        return $this->column;
    }
}