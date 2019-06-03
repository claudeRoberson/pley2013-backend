<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Laravel\Providers;

/**
 * The <kbd>PleyConfigServiceProvider</kbd> registers the Pley Config Service to be used within the 
 * Laravel framework.
 * <p>This Service Provider is automatically registered on the <kbd>/app/config/app.php</kbd>
 * configuration file.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @version 1.0
 * @package Pley\Laravel
 * @subpackage ServiceProvider
 */
class PleyConfigServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const CONFIG_PATH     = '\\Pley\\Config\\';
    const IMPL_ILLUMINATE = 'Illuminate';
    
    /**
     * Registers the service provider
     */
    public function register()
    {
        $implToUse = self::IMPL_ILLUMINATE;
        
        $bindPath = self::CONFIG_PATH . 'ConfigInterface';
        $implPath = self::CONFIG_PATH . 'Impl\\'. $implToUse . '\\Config';
        
        $this->app->bind($bindPath, $implPath);
    }
    
    /** @return \Pley\Config\ConfigInterface */
    public static function getConfig()
    {
        $config = \App::make('\Pley\Config\ConfigInterface');
        return $config;
    }
}
