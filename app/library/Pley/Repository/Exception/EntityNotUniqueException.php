<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Repository\Exception;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>EntityNotUniqueException</kbd> is used to define exceptions for Entities that cannot
 * be uniquely identified.
 * <p>Actions like UPDATES will fail if the Entity cannot be uniquely identified.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Repository.Exception
 * @subpackage Exception
 */
class EntityNotUniqueException extends \RuntimeException implements ExceptionInterface
{
    /** @var string */
    protected $_entityName;
    
    public function __construct($entityName, $message = null, \Exception $previous = null)
    {
        $msg = 'Entity [`'. $entityName .'`] could not be uniquely identified.';
        
        // If a message is supplied, append it.
        if (is_string($message)) {
            $msg .= ' ' . $message;
        }
        
        parent::__construct($message, ExceptionCode::REPOSITORY_ENTITY_NOT_UNIQUE, $previous);
    }

    /**
     * Get the affected Eloquent model.
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->_entityName;
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
