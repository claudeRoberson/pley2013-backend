<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Dao;

use \Pley\Cache\CacheInterface;
use \Pley\Dao\DaoInterface;

/**
 * The <kbd>CacheDaoInterface</kbd> defines that the concrete class will use a Cache storage and
 * thus, it needs to be supplied of a Cache mechanism instance.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Dao
 * @subpackage Dao
 */
interface CacheDaoInterface extends DaoInterface
{
    /**
     * Sets the Cache that will be used as means of interacting with the Cache Mechanism.
     * 
     * @param \Pley\Cache\CacheInterface $cache
     */
    public function setCache(CacheInterface $cache);
}
