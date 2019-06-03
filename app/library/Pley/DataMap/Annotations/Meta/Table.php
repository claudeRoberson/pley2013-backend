<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\DataMap\Annotations\Meta;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class, which implements an annotation for storing entity class mapped table name
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Table extends Annotation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @return mixed
     */
    public function getTableName()
    {
        return $this->name;
    }
}