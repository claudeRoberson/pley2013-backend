<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Exception\Entity;

use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>ImmutableAttributeException</kbd> is used for attempts to modify object attributes which
 * values are immutable once assigned.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Entity
 * @subpackage Exception
 */
class ImmutableAttributeException extends \RuntimeException implements ExceptionInterface
{
    public function __construct($className, $attributeName, \Exception $previous = null)
    {
        $message = 'Cannot modify immutable attribute ' . $className . ':' . $attributeName;
        parent::__construct($message, 0, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
