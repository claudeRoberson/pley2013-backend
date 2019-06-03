<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\DataMap\Annotations;

use \Pley\Config\ConfigInterface as Config;

/**
 * The <kbd>ReaderFactory</kbd> class allows us to standarized the creation or Annotation Reader
 * instances for the supplied classes.
 * <p>This allows us to centralize how the readers are configured and be used across the application
 * without conflicting structures.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.DataMap.Annotations
 * @subpackage Annotations
 */
final class ReaderFactory
{   
    /**
     * Creates an Annotation Reader for the supplied class that caches the annotations for performance.
     * @param string|\ReflectionClass $class Either the qualified string name or a reflection class object
     * @return \Doctrine\Common\Annotations\Reader
     */
    public static function getCachedForClass($class, Config $config)
    {
        $refClass = $class;
        
        if (!$class instanceof \ReflectionClass) {
            $refClass = new \ReflectionClass($class);
        }
        
        $cacheDir    = $config->get('cache.path');
        $isDebugMode = $config->get('cache.annotations.debugMode');

        $classFileName = str_replace('\\', DIRECTORY_SEPARATOR, $refClass->getName());
        
        $cachePath     = $cacheDir . DIRECTORY_SEPARATOR . $classFileName;
        
        $fileCacheReader = new \Doctrine\Common\Annotations\FileCacheReader(
            new \Doctrine\Common\Annotations\AnnotationReader(), $cachePath, $isDebugMode
        );
        return $fileCacheReader;
    }
}
