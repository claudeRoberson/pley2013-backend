<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Laravel\Providers;

use Doctrine\Common\Annotations\AnnotationRegistry;

/**
 * Annotations service provider and class loader
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley\Laravel
 * @subpackage ServiceProvider
 */
class PleyAnnotationsServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $libraryPath = base_path() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'library';
        
        // Registering with and without the leading backslash, so to avoid problems where in some classes
        // we have
        // use Pley\Entity\....
        // use \Pley\Entity\....
        AnnotationRegistry::registerAutoloadNamespace("Pley\\DataMap\\Annotations\\Meta", $libraryPath);
        AnnotationRegistry::registerAutoloadNamespace("\\Pley\\DataMap\\Annotations\\Meta", $libraryPath);
    }
}
