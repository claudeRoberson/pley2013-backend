<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Exception\Entity;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>InvalidParameterException</kbd> is thrown when bad parameters is sent to a method.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Exception
 * @subpackage Exception
 */
class EntityExistsException extends \RuntimeException implements ExceptionInterface
{
    public function __construct($entityName, $entityId, \Exception $previous = null)
    {
        $message = json_encode(['entity' => $entityName, 'id' => $entityId]);
        parent::__construct($message, ExceptionCode::ENTITY_EXISTS, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}