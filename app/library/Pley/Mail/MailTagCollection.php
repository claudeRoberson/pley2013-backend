<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Mail;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Exception\Mail\UnknownMailTagException;

/**
 * The <kbd>MailTagCollection</kbd> is a conteiner class to hold all the Entities required to parse
 * an email template.
 * <p>It provides functionality to dynamically add entity objects and based on configuration create
 * an internal map that will be used by the <kbd>Mail</kbd> class (this saves us from having to
 * create many factory methods for each of the entities have and will support).
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Mail
 * @subpackage Mail
 */
class MailTagCollection
{
    protected static $_entityPrefix = 'Pley\Entity';
    protected static $_daoPrefix    = 'Pley\Dao';
    
    /**
     * Holds a Map between a class name and the key it relates to from the configuration file.
     * <p>i.e.</p>
     * <code>array(<br/>
     * &nbsp;   '\Pley\Entity\User\User' => [<br/>
     * &nbsp;       'id'      => ...,<br/>
     * &nbsp;       'entity'  => ...,<br/>
     * &nbsp;       'dao'     => ...,<br/>
     * &nbsp;       'tagName' => ...,<br/>
     * &nbsp;   ],<br/>
     * &nbsp;   ...<br/>
     * )</code>
     * @var array
     */
    protected static $_mapByClass;
    
    /**
     * Holds a Map of the Tag ids that got added to this collection.
     * <p>This list will be used by the <kbd>Mail</kbd> class to validate the tag replacers.</p>
     * <p>i.e.</p>
     * <pre>array(
     * &nbsp;   tagId => true,
     * &nbsp;   ...,
     * )</pre>
     * @var array
     */
    protected $_tagNameMap = [];
    /**
     * Holds the Map that will be used by the <kbd>Mail</kbd> class to pass parameters to the email
     * template view for parsing.
     * <p>i.e.</p>
     * <pre>array(
     * &nbsp;   tagVarName => object, // of type \Pley\Entity\User\User,
     * &nbsp;   ...,
     * )</pre>
     * @var array
     */
    protected $_tagDataMap = [];
    /**
     * Holds the Map that will be used by the <kbd>Mail</kbd> class to log the reference data of
     * the entities and ids related to them for the specific email.
     * <p>i.e.</p>
     * <pre>array(
     * &nbsp;   array(
     * &nbsp;       "tag" => tagID,
     * &nbsp;       "id"  => entityID,
     * &nbsp;   ),
     * &nbsp;   ...,
     * )</pre>
     * @var array
     */
    protected $_refDataMap = [];
    
    public function __construct(Config $config)
    {
        // Create the map by class map if it has not been yet created.
        if (empty(static::$_mapByClass)) {
            $tagReplacerMap = $config->get('mailTemplate.tagReplacerMap');

            // Creating now the reverse map. By class name to tag data map
            static::$_mapByClass = [];
            foreach ($tagReplacerMap as $tagName => $detailMap) {
                // Adding the tagName to the detail map before appending to the mapByClass static variable
                $detailMap['tagName'] = $tagName;
                
                // Creating the full entity namespace.
                $entityNamespace = $this->_getNamespace(static::$_entityPrefix, $detailMap['entity']);
                $daoNamespace    = $this->_getNamespace(static::$_daoPrefix, $detailMap['dao']);

                // Updating detail map with the fully qualified namespaces
                $detailMap['entity'] = $entityNamespace;
                $detailMap['dao']    = $daoNamespace;
                
                static::$_mapByClass[$entityNamespace] = $detailMap;
            }
        }
    }
    
    /**
     * Get the Map with all the tag ids that had been added to this collection.
     * <p>The map has the following structure:</p>
     * <pre>array(
     * &nbsp;   intName => true, // Key is the tag name.
     * &nbsp;   ...,
     * )</pre>
     * @return array
     */
    public function getTagNameSet()
    {
        return $this->_tagNameMap;
    }
    
    /**
     * Get the Map with all the entites that had been added to this collection.
     * <p>The map has the following structure:</p>
     * <pre>array(
     * &nbsp;   array(
     * &nbsp;       "tag" => tagID,
     * &nbsp;       "id"  => entityID,
     * &nbsp;   ),
     * &nbsp;   ...,
     * )</pre>
     * @return array
     */
    public function getTagDataMap()
    {
        return $this->_tagDataMap;
    }
    
    /**
     * Get the Map of the reference data used for Logging the email on the Storage.
     * <p>The map has the following structure:</p>
     * <pre>array(
     * &nbsp;   'user' => object, // of type \Pley\Entity\User\User
     * &nbsp;   ...,
     * )</pre>
     * @return array
     */
    public function getRefDataMap()
    {
        return array_values($this->_refDataMap);
    }
    
    /**
     * Adds an entity object to this collection and creates the internal map based on the objects
     * class namespace.
     * <p>If the <kbd>$entityObject</kbd> value supplied is <kbd>null</kbd>, the second parameter has
     * to be supplied so that the <kbd>null</kbd> value can be correctly associated to the tagName
     * to be used within the email template.
     * </p>
     * @param mixed  $entityObject           An instance of an entity needed for email template parsing
     * @param string $nullObjEntityNamespace (Optional)<br/>The name of the Entity Namespace that
     *      should be used in case the <kbd>$entityObject</kbd> is <kbd>null</kbd>.
     */
    public function addEntity($entityObject, $nullObjEntityNamespace = null)
    {
        $entityNamespace = isset($entityObject)? get_class($entityObject) : $nullObjEntityNamespace;
        
        // This checks assume that the configuration file does not contain the trailing slash for
        // class namespaces, so that we can make use of the `get_class()` method above without
        // having to iterate through all the entries.
        if (!isset(static::$_mapByClass[$entityNamespace])) {
            throw new UnknownMailTagException($entityNamespace);
        }
        
        // Creating the Tag Map for check of required tags
        $tagName = static::$_mapByClass[$entityNamespace]['tagName'];
        $this->_tagNameMap[$tagName] = true;
        
        // Creating the Tag Data Map for use in template replacement
        $this->_tagDataMap[$tagName] = $entityObject;
        
        // Creating the Ref Data Map used for logging on Storage.
        $this->_refDataMap[$tagName] = [
            'tag' => static::$_mapByClass[$entityNamespace]['tagId'],
            'id'  => isset($entityObject)? $entityObject->getId() : null,
        ];
    }
    
    /**
     * Adds an object to the Tag Map so that it can be used within the email template.
     * <p>This call is different from <kbd>addEntity</kbd> in which the supplied object does not
     * map to any Entity in the Storage, and as such it is not trackable.</p>
     * 
     * @param string $tagName
     * @param mixed  $object
     */
    public function setCustom($tagName, $object)
    {
        $this->_tagDataMap[$tagName] = $object;
    }
    
    /**
     * Helper function to create the full class namespaced with the supplied prefix and the given
     * by convention path of the class.
     * @param string $prefix
     * @param string $path
     * @return string
     */
    protected function _getNamespace($prefix, $path)
    {
        $concatenation = $prefix . '\\' . $path;
        
        // Making sure there are no double inverted slashes on the namespace
        $namespace = str_replace('\\\\', '\\', $concatenation);
        
        return $namespace;
    }
}
