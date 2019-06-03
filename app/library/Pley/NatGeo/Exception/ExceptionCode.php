<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\NatGeo\Exception;

/**
 * The <kbd>ExceptionCode</kbd> class provides a central place to identify Errores by a given number.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.NatGeo.Exception
 * @subpackage NatGeo
 * @subpackage Exception
 */
class ExceptionCode
{
    // Using a number grouping structutre, first 3 digits indicate a group, and last 3 digits the
    // specific error within the group
    
    const INTERNAL_SERVER_ERROR = 500;
    
    // Generic group errors group 600xxx
    const INVALID_REQUEST       = 600001;
    const COMMAND_NOT_SUPPORTED = 600002;

    // Errors related to User operations
    const USER_EXISTS       = 700001;
    const USER_DOESNT_EXIST = 700002;
    const USER_ID_REQUIRED  = 700003;
    
    // Errors related to Mission operations
    const MISSION_INVALID_ID = 800001;

}
