<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\NatGeo\Exception;

/**
 * The <kbd>ImmutableAttributeException</kbd> class represents the exception thrown when trying to
 * modify an attribute that cannot be updated once set (like an ID).
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.NatGeo.Exception
 * @subpackage NatGeo
 * @subpackage Exception
 */
class InvalidRequestException extends AbstractBaseException
{
    /** {@inheritDoc} */
    protected function _getMessageString()
    {
        return 'Invalid Request.';
    }
    
    /** {@inheritDoc} */
    protected function _getExceptionCode()
    {
        return ExceptionCode::INVALID_REQUEST;
    }
}
