<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\NatGeo\Exception;

/**
 * The <kbd>UserExistsException</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.NatGeo.Exception
 * @subpackage NatGeo
 * @subpackage Exception
 */
class UserExistsException extends AbstractBaseException
{
    /** {@inheritDoc} */
    protected function _getMessageString()
    {
        return 'User exists already.';
    }
    
    /** {@inheritDoc} */
    protected function _getExceptionCode()
    {
        return ExceptionCode::USER_EXISTS;
    }
}
