<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Exception\Cache;

use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>CacheNamespaceException</kbd> is raised when trying to use cache but the namespace
 * has not been set by the DAO.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Cache.Exception
 * @subpackage Exception
 */
class CacheNamespaceException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(\Exception $previous = null)
    {
        $message = 'Cache namespace is not set. Remember to call `setNamespace()`';
        parent::__construct($message, 0, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
