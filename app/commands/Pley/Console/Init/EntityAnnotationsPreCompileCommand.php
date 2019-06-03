<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console\Init;

use \Illuminate\Console\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * The <kbd>EntityAnnotationsPreCompileCommand</kbd> class allows us to warm up annotations cache
 * during deployment phase.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Console.Init
 * @subpackage Console
 */
class EntityAnnotationsPreCompileCommand extends Command
{
    /**
     * The console command name.
     * @var string
     */
    protected $name = 'pleyInit:EntityAnnotationPrecompile';
    
    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Helper command to precompile our Entity Annotations';
    
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\DataMap\Annotations\ReaderFactory */
    protected $_annotationReaderFactory;
    
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        
        $this->_config                  = \App::make('\Pley\Config\ConfigInterface');
        $this->_annotationReaderFactory = \App::make('\Pley\DataMap\Annotations\ReaderFactory');
    }
    
    public function fire()
    {
        $this->line('Pre-caching Entity Annotations');
        
        $entityDirPath = app_path() . '/library/Pley/Entity';
        
        /* @var $laravelFS \Illuminate\Filesystem\Filesystem */
        $laravelFS = $this->laravel['files'];
        
        $fileList = $laravelFS->allFiles($entityDirPath);
        
        /* @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($fileList as $file) {
            // Exclude anything that is not a file (i.e. directory file pointers)
            if (!$file->isFile()) {
                continue;
            }
            
            $className = $this->_getClassFromFile($file->getRealPath());
            
            // Ignore if the contents of the file do not represent a class.
            if ($className === false) {
                continue;
            }
            
            $refClass = new \ReflectionClass($className);
            
            // Creating an instance without using the constructor in case the object we are trying 
            // to instantiate is not an entity but some other class, and thus avoid trying to call
            // an empty constructor where there is none.
            $instance = $refClass->newInstanceWithoutConstructor();
            
            // Now check if the class instance is of an Entity type, if not, ignore it
            if (!$instance instanceof \Pley\DataMap\Entity) {
                continue;
            }
            
            $this->_cacheAnnotations($refClass);
        }
    }
    
    /**
     * Helper method to retrieve the fully qualified name of an instantiable class if the provided
     * file path contains one.
     * @param string $pathToFile
     * @return boolean|string The fully qualified class name if the file contains one, <kbd>False</kbd> otherwise.
     */
    private function _getClassFromFile($pathToFile)
    {
        $namespaceRegex     = '/namespace[ ]+([\w\d_\\\\]+);/';
        $namespaceMatchList = [];
        $namespaceIdx       = 1;

        $classNameRegex     = '/(abstract +)?class[ ]+([\w\d_]+)[ ]*(extends|implements|{)?/';
        $classNameMatchList = [];
        $classAbstractIdx   = 1;
        $classNameIdx       = 2;

        $contents = file_get_contents($pathToFile);
        
        $isNamespace = preg_match($namespaceRegex, $contents, $namespaceMatchList) == 1;
        $isClassName = preg_match($classNameRegex, $contents, $classNameMatchList) == 1;
        
        // If a class name was not found, this file does not represent a Class file, or the class
        // is an abstract Class and thus cannot be instantiated
        if (!$isClassName || !empty($classNameMatchList[$classAbstractIdx])) {
            return false;
        }
        
        $namespace = $isNamespace? $namespaceMatchList[$namespaceIdx] : '';
        $className = $classNameMatchList[$classNameIdx];
        
        $qualifiedName = ltrim($namespace, '\\') . '\\' . $className;
        return $qualifiedName;
    }
    
    /**
     * Method that does the pre-caching into the storage for the supplied class
     * @param \ReflectionClass $refClass
     */
    private function _cacheAnnotations(\ReflectionClass $refClass)
    {
        // The default System's UMASK tends to be 022, which causes the cache directories to not be 
        // writable by the group and thus was causing problems between executing on Web and on Commands
        $systemDefaultUmask = umask(002); 
        
        $annotationReader = \Pley\DataMap\Annotations\ReaderFactory::getCachedForClass($refClass, $this->_config);
        
        $classAnnotation = $annotationReader->getClassAnnotation(
            $refClass, \Pley\DataMap\Annotations\Meta\Table::class
        );
        
        // If the class does not have a Table annotation, it is not annotated and thus cannot cache it
        if (!isset($classAnnotation)) {
            return;
        }
        
        // Now that we know it has the table annotation, that annotation has been cached, so now
        // lets proceed to cache the properties
        $refPropertyList = $refClass->getProperties();
        foreach ($refPropertyList as $property) {
            $annotationReader->getPropertyAnnotation(
                $property, \Pley\DataMap\Annotations\Meta\Property::class
            );
        }
        
        // Reverting to the System's default UMASK
        umask($systemDefaultUmask);
    }
}
