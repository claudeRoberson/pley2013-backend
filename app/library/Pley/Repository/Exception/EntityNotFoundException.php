<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Repository\Exception;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>EntityNotFoundException</kbd> is used to define exceptions for searching entities which
 * do not exist or cannot be found for the given parameters.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Repository.Exception
 * @subpackage Exception
 */
class EntityNotFoundException extends \RuntimeException implements ExceptionInterface
{
    /** @var string */
    protected $_entityName;
    
    public function __construct($entityName, \Exception $previous = null)
    {
        $message = 'No query results for entity [`'. $entityName .'`]';
        parent::__construct($message, ExceptionCode::REPOSITORY_ENTITY_NOT_FOUND, $previous);
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
        return ResponseCode::HTTP_NOT_FOUND;
    }
}
