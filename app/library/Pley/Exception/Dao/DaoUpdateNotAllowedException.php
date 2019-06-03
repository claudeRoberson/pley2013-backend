<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Exception\Dao;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>DaoUpdateNotAllowedException</kbd> is used when an update operation is not suported
 * by the <kbd>DaoInterface</kbd> specific implementation.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Dao
 * @subpackage Exception
 */
class DaoUpdateNotAllowedException extends \Exception implements ExceptionInterface
{
    /**
     * Creates a new <kbd>DaoUpdateNotAllowedException</kbd> exception with the method that triggered
     * this behavior.
     * @param string     $classMethod Usually the magic variable __METHOD__ is passed as the value.
     * @param \Exception $previous
     */
    public function __construct($classMethod, \Exception $previous = null)
    {
        $message  = "Update operation is not allowed for DAO [`{$classMethod}`]";
        parent::__construct($message, ExceptionCode::DAO_UPDATE_NOT_ALLOWED, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
