<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\DataMap;

use Pley\DataMap\Repository\DataMapRepositoryInterface;

/**
 * Class description goes here
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
abstract class Repository implements DataMapRepositoryInterface
{
    /** @var \Pley\DataMap\Dao */
    protected $_dao;

    public function __construct(Dao $dao)
    {
        $this->_dao = $dao;
    }
}

