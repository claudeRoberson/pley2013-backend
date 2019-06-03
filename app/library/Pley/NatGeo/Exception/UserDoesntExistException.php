<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\NatGeo\Exception;

/**
 * The <kbd>UserDoesntExistException</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.NatGeo.Exception
 * @subpackage NatGeo
 * @subpackage Exception
 */
class UserDoesntExistException extends AbstractBaseException
{
    /** {@inheritDoc} */
    protected function _getMessageString()
    {
        return 'User doesn\'t exist.';
    }
    
    /** {@inheritDoc} */
    protected function _getExceptionCode()
    {
        return ExceptionCode::USER_DOESNT_EXIST;
    }
}
