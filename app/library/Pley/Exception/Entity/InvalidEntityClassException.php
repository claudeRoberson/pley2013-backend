<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Exception\Entity;

use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>InvalidEntityClassException</kbd> is used when a specific Entity class is needed but
 * a different one is provided.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Entity
 * @subpackage Exception
 */
class InvalidEntityClassException extends \Exception implements ExceptionInterface
{
    public function __construct($expectedClassName, $receivedClassName, \Exception $previous = null)
    {
        $msg = "Invalid Entity Class [required:{$expectedClassName}, received:{$receivedClassName}]";
        parent::__construct($msg, 0, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}