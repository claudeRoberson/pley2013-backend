<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Laravel\Providers;

use \Pley\Storage\Cdn\Impl\Aws\S3CdnStorage;
use \Pley\Storage\Cdn\Impl\Illuminate\LocalCdnStorage;

/**
 * The <kbd>PleyWorldStorageServiceProvider</kbd> provides with the functionality to inject a
 * Storage implementation for DependencyInjection based on configuration files.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package PleyWorld.Laravel.Providers
 * @subpackage ServiceProvider
 */
class PleyServiceStorageProvider extends \Illuminate\Support\ServiceProvider
{
    const STORAGE_TYPE_S3    = 'S3';
    const STORAGE_TYPE_LOCAL = 'local';

    /**
     * Represents the Abstract implementation used for injection
     * @var string
     */
    private static $abstract = '\Pley\Storage\Cdn\CdnStorageInterface';

    public function register()
    {
        $cdnConfig = $this->app['config']['constants']['cdn'];

        $storageType = $cdnConfig['storage']['type'];

        if ($storageType == self::STORAGE_TYPE_S3) {
            // The way the \Aws\Common\Aws is supplied, it requires to use the Laravel \App::make
            // facade, for Class type hinting will not initialize the instance correctly if attempting
            // to inject the object at as a parameter of a method through the use of type hinting.
            $this->app->bind(self::$abstract, function($app) {
                /* @var $aws \Aws\Common\Aws */
                $aws          = $app->make('aws');
                $s3CdnStorage = new S3CdnStorage($aws);

                return $s3CdnStorage;
            });

        } else { // if ($storageType == self::STORAGE_TYPE_LOCAL) {
            $this->app->bind(self::$abstract, LocalCdnStorage::class);
        }
    }
}
