<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\NatGeo\Exception;

/**
 * The <kbd>InternalServerException</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.NatGeo.Exception
 * @subpackage NatGeo
 * @subpackage Exception
 */
class InternalServerException extends AbstractBaseException
{
    /** {@inheritDoc} */
    protected function _getMessageString()
    {
        return 'Internal API Server Error.';
    }
    
    /** {@inheritDoc} */
    protected function _getExceptionCode()
    {
        return ExceptionCode::INTERNAL_SERVER_ERROR;
    }
}
