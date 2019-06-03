<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Repository\Exception;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>ExistingEntityException</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Repository.Exception
 * @subpackage Exception
 */
class ExistingEntityException extends \RuntimeException implements ExceptionInterface
{
    public function __construct($entityName, $entityId, \Exception $previous = null)
    {
        $message = 'Existing entity [`' .$entityName . '`, `' . $entityId . '`]';
        parent::__construct($message, ExceptionCode::REPOSITORY_EXISTING_ENTITY, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}
