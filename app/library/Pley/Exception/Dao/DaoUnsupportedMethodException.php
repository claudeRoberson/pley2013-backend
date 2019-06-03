<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Exception\Dao;

use \Pley\Dao\DaoInterface;
use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>UnsupportedOperationException</kbd> is used when a specific operation from the default
 * ones on the <kbd>DaoInterface</kbd> are not supported for the specific DAO implementation.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Dao
 * @subpackage Exception
 */
class DaoUnsupportedMethodException extends \Exception implements ExceptionInterface
{   
    public function __construct(DaoInterface $dao, $method, \Exception $previous = null)
    {
        $daoClass = get_class($dao);
        $message  = "Unsupported DAO operation  [`{$daoClass}::{$method}`]";
        parent::__construct($message, ExceptionCode::DAO_UNSUPPORTED_METHOD, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
    }
}
