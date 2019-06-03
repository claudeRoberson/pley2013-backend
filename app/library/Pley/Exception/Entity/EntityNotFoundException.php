<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Exception\Entity;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>EntityNotFoundException</kbd> is used to define exceptions for searching entities which
 * do not exist or cannot be found for the given parameters.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Entity
 * @subpackage Exception
 */
class EntityNotFoundException extends \RuntimeException implements ExceptionInterface
{
    public function __construct($entityName, \Exception $previous = null)
    {
        $message = json_encode(['entity' => $entityName]);
        parent::__construct($message, ExceptionCode::ENTITY_NOT_FOUND, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_NOT_FOUND;
    }
}
