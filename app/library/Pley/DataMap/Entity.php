<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\DataMap;

use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Contracts\JsonableInterface;
use Pley\DataMap\Entity\FillableInterface;
use Pley\DataMap\Entity\MappableInterface;
use Pley\DataMap\Entity\Jsonable;

/**
 * Abstract MappableEntity class, which implements entity service methods
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
abstract class Entity implements FillableInterface, ArrayableInterface, JsonableInterface, MappableInterface
{
    use Jsonable;

    private static $ANNOTATION_READER = 'annotationReader';
    private static $REF_CLASS         = 'ref_cls';
    private static $REF_PROPERTY_LIST = 'ref_prop_lst';
    private static $FILLABLE_MAP      = 'fillable_map';

    protected static $_mappingCache = [];

    public static function columns()
    {
        $columns = [];

        $refPropertyList = static::_getReflectionPropertyList();
        $reader          = static::_getAnnotationReader();
        foreach ($refPropertyList as $property) {
            /** @var $annotation \Pley\DataMap\Annotations\Meta\Property */
            $annotation = $reader->getPropertyAnnotation($property, \Pley\DataMap\Annotations\Meta\Property::class);
            if ($annotation && $annotation->getColumnName()) {
                $columns[] = $annotation->getColumnName();
            }
        }

        return $columns;
    }

    public static function tableName()
    {
        $refClass = static::_getReflectionClass();
        $reader   = static::_getAnnotationReader();

        /** @var $annotation \Pley\DataMap\Annotations\Meta\Table */
        $annotation = $reader->getClassAnnotation($refClass, \Pley\DataMap\Annotations\Meta\Table::class);
        return $annotation->getTableName();
    }

    public function __construct()
    {
        static::_init();
    }

    /**
     * Hydrates an entity using it's setters or properties from a given data array.
     * @param $data []
     * @return $this
     */
    public function fill($data)
    {
        $fillableMap = $this->fillable();

        $refPropertyList = static::_getReflectionPropertyList();
        foreach ($refPropertyList as $property) {
            $propertyKey  = static::_getPropertyKey($property);

            // If the property is not fillable
            if (!isset($fillableMap[$propertyKey])) {
                continue;
            }
            // If the property is not supplied on the data map, go to next property
            if (!array_key_exists($propertyKey, $data)) {
                continue;
            }

            // Check if the setter method exists, if so, then use it to set the value
            $setterMethod = 'set' . ucfirst($propertyKey);
            if (method_exists($this, $setterMethod)) {
                $this->{$setterMethod}($data[$propertyKey]);
                continue;
            }

            // At this point we know there is no setter, but that the property does exist, so just assign
            $this->{$property->getName()} = $data[$propertyKey];
        }

        return $this;
    }

    public function fillable()
    {
        static::_init();
        return static::$_mappingCache[self::$FILLABLE_MAP];
    }

    public function mapFromRow($rowData)
    {
        if (!$rowData) {
            return null;
        }

        $refPropertyList = static::_getReflectionPropertyList();
        $reader          = static::_getAnnotationReader();
        foreach ($refPropertyList as $property) {
            /* @var $annotation \Pley\DataMap\Annotations\Meta\Property */
            $annotation = $reader->getPropertyAnnotation($property, \Pley\DataMap\Annotations\Meta\Property::class);
            if (!$annotation || !$annotation->getColumnName()) {
                continue;
            }
            if (isset($rowData[$annotation->getColumnName()])) {
                $this->{$property->getName()} = $rowData[$annotation->getColumnName()];
            }
        }
        return $this;
    }

    /**
     * Maps array for db insertion/update from object instance
     * @return []
     */
    public function mapToRow()
    {
        $rowData = [];

        $refPropertyList = static::_getReflectionPropertyList();
        $reader          = static::_getAnnotationReader();
        foreach ($refPropertyList as $property) {
            /* @var $annotation \Pley\DataMap\Annotations\Meta\Property */
            $annotation = $reader->getPropertyAnnotation($property, \Pley\DataMap\Annotations\Meta\Property::class);
            if ($annotation && $annotation->getColumnName()) {
                $rowData[$annotation->getColumnName()] = $this->{$property->getName()};
            }
        }

        return $rowData;
    }

    // ---------------------------------------------------------------------------------------------
    // PROTECTED Functionality ---------------------------------------------------------------------

    /** @return \ReflectionClass */
    protected static function _getReflectionClass()
    {
        static::_init();
        return static::$_mappingCache[self::$REF_CLASS];
    }

    /** @return \ReflectionProperty[] */
    protected static function _getReflectionPropertyList()
    {
        static::_init();
        return static::$_mappingCache[self::$REF_PROPERTY_LIST];
    }

    /** @return \Doctrine\Common\Annotations\Reader */
    protected static function _getAnnotationReader()
    {
        static::_init();
        return static::$_mappingCache[self::$ANNOTATION_READER];
    }

    /**
     * Checks a property that is considered immutable to see if it is Set already.
     * <P>If the property is set, and thus considered immutable, an exception is thrown.</p>
     * @param string $propertyName
     * @throws \Pley\Exception\Entity\ImmutableAttributeException
     */
    protected function _checkImmutableChange($propertyName)
    {
        if (isset($this->{$propertyName})) {
            throw new \Pley\Exception\Entity\ImmutableAttributeException(static::class, $propertyName);
        }
    }

    // ---------------------------------------------------------------------------------------------
    // PRIVATE Functionality -----------------------------------------------------------------------

    /**
     * Method that caches the class reflection objects and the Annotations reader for it.
     * @param \Pley\Config\ConfigInterface $config
     */
    private static function _init()
    {
        // If the entity cache has been intialized, no need to re-initialize it
        if (!empty(static::$_mappingCache)) {
            /*
             * Checking if current cache representation holds correct class data
             * collision can happen, when we are instantiating multiple datamap Entities
             * in a succession.
             * */
            if(static::$_mappingCache[self::$REF_CLASS]->getName() === static::class){
                return;
            }
        }

        // Annotations should already be pre-cached as part of the composer after update scripts, but if not
        // The default System's UMASK tends to be 022, which causes the cache directories to not be 
        // writable by the group and thus was causing problems between executing on Web and on Commands
        $systemDefaultUmask = umask(002);

        // Caching the Reflection representation for this class to avoid multiple instantiation
        $refClass = new \ReflectionClass(static::class);
        static::$_mappingCache[self::$REF_CLASS] = $refClass;

        // Caching now the reflection properties as these are needed for the annotation reader
        $refPropertyList = $refClass->getProperties();
        static::$_mappingCache[self::$REF_PROPERTY_LIST] = $refPropertyList;

        // Now caching the annotataion reader
        // We are initializing it with FileCache to improve performance
        $config           = \Pley\Config\ConfigFactory::getConfig();
        $annotationReader = \Pley\DataMap\Annotations\ReaderFactory::getCachedForClass($refClass, $config);
        static::$_mappingCache[self::$ANNOTATION_READER] = $annotationReader;

        // Now that the reader has been initialized, let's just pre-cache the annotations for each property
        // And initialize the `fillable` map
        $fillable = [];
        foreach ($refPropertyList as $property) {
            $annotation = $annotationReader->getPropertyAnnotation($property, \Pley\DataMap\Annotations\Meta\Property::class);
            if ($annotation && $annotation->isFillable()) {
                $fillable[$property->getName()] = $property->getName();
            }
        }
        static::$_mappingCache[self::$FILLABLE_MAP] = $fillable;

        // Reverting to the System's default UMASK
        umask($systemDefaultUmask);
    }

    /**
     * Returns the string to be used as key for a supplied property.
     * @param \ReflectionProperty $property
     * @return String
     */
    private static function _getPropertyKey(\ReflectionProperty $property)
    {
        $propName = $property->getName();

        // If it is a protected/private property, and it start with the leading underscore, remove
        // it for purposes of the Key name
        if (!$property->isPublic() && substr($propName, 0, 1) == '_') {
            $propName = substr($propName, 1);
        }

        return $propName;
    }

}