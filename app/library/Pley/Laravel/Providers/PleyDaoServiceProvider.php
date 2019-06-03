<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Laravel\Providers;

use \Pley\Cache\InMemoryCache;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DbDaoInterface;

/**
 * The <kbd>PleyDaoServiceProvider</kbd> registers the Pley DAOs to be used within the Laravel
 * framework for injection.
 * <p>This Service Provider is automatically registered on the <kbd>/app/config/app.php</kbd>
 * configuration file.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley\Laravel\Providers
 * @subpackage ServiceProvider
 */
class PleyDaoServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const DAO_PATH = '\Pley\Dao';
    
    const CACHE_TYPE_IN_MEMORY = 'inMemory';
    const CACHE_TYPE_DYNAMIC   = 'dynamic';
    const CACHE_TYPE_STATIC    = 'static';
    
    // Variables that map to the configuration file for Cache Drivers
    const CACHE_DRIVER_STATIC  = 'staticDriver';
    const CACHE_DRIVER_DEFAULT = 'driver';

    /** Registers the service provider */
    public function register()
    {   
        $daoConfig = $this->app['config']['dao'];
        
        $this->app->singleton(
            '\Pley\Db\AbstractDatabaseManager', '\Pley\Db\Impl\Illuminate\DatabaseManager'
        );
        
        $dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');
        
        // Static DAOs are for those that handle entities that are NOT affected by user interaction
        // and rarely ever change, thus, usually stored in APC cache
        $this->_processDaosConfig($daoConfig['staticDao'], $dbManager);
        
        // Dynamic DAOs are for those that handle entities that are affected by user interaction,
        // thus, usually stored in MemCache
        $this->_processDaosConfig($daoConfig['dynamicDao'], $dbManager);
    }
    
    protected function _processDaosConfig($daoConfig, $dbManager)
    {
        $daoMap    = $daoConfig['daoMap'];
        $cacheType = $daoConfig['cacheType'];
        
        foreach ($daoMap as $classPath => $daoDefinition) {
            $this->_bind($classPath, $daoDefinition, $dbManager, $cacheType);
        }
    }
    
    protected function _bind($classPath, $daoDefinition, $dbManager, $cacheType)
    {
        $class     = self::DAO_PATH . $classPath;
        $isShared  = $daoDefinition['isShared'];
        
        $this->app->bind(
            $class,
            function() use ($class, $cacheType, $dbManager) {
                $dao = (new \ReflectionClass($class))->newInstance();
                
                if ($dao instanceof DbDaoInterface) {
                    /* @var $dao \Pley\Dao\DbDaoInterface */
                    $dao->setDatabaseManager($dbManager);
                }
                
                if ($dao instanceof CacheDaoInterface) {
                    /* @var $cache \Pley\Cache\CacheInterface */
                    $cache = $this->_getCache($cacheType);
                    
                    /* @var $dao \Pley\Dao\CacheDaoInterface */
                    $dao->setCache($cache);
                }
                
                return $dao;
            },
            $isShared
        );
    }
    
    /**
     * Creates the cache object based on the type.
     * <p>It determines based on type, whether there is any decoration needed (i.e. MemCache will
     * decorate a InMemory).</p>
     * 
     * @param string $cacheType
     * @return \Pley\Cache\CacheInterface
     */
    protected function _getCache($cacheType)
    {
        $cache = null;
        
        switch($cacheType) {
            case self::CACHE_TYPE_IN_MEMORY:
                $cache = new InMemoryCache();
                break;
            case self::CACHE_TYPE_DYNAMIC:
            case self::CACHE_TYPE_STATIC:
                // here we do the decoration
//                $decorated = new InMemoryCache();
//                $cache = new DecoratorCache($driver, $decorated);
                
            default:
                break;
        }
        
        return $cache;
    }
}
