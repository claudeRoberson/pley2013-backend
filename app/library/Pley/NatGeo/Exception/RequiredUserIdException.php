<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\NatGeo\Exception;

/**
 * The <kbd>RequiredUserIdException</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.NatGeo.Exception
 * @subpackage NatGeo
 * @subpackage Exception
 */
class RequiredUserIdException extends AbstractBaseException
{
    /** {@inheritDoc} */
    protected function _getMessageString()
    {
        return 'Required user ID.';
    }
    
    /** {@inheritDoc} */
    protected function _getExceptionCode()
    {
        return ExceptionCode::USER_ID_REQUIRED;
    }
}
