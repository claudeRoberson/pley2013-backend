<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Config;

/**
 * The <kbd>ConfigFactory</kbd> class is a support class to be able to retrieve the Configuration
 * manager object without having to require it as a Dependency for the IoC to inject.
 * <p>This is needed to still be able to use the Dependency Injection mechanics outside the
 * constructor but abstract most dependency of the underlying framework.</p>
 * <p><i>This was mainly developed to be able to use Annotations on our Entities while allowing us
 * to configure the Environment through configuration, and not having to supply the configuration
 * object to every single entity or DAO that would create entities.</i></p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Config
 * @subpackage Config
 */
final class ConfigFactory
{
    /** @var \Pley\Config\ConfigInterface */
    private static $_config;
    
    /**
     * Get the Config manager object.
     * @return \Pley\Config\ConfigInterface
     */
    public static function getConfig()
    {
        if (!isset(self::$_config)) {
            self::$_config = \Pley\Laravel\Providers\PleyConfigServiceProvider::getConfig();
        }
        
        return self::$_config;
    }
}
