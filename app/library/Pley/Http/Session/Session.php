<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Http\Session;

/**
 * The <kbd>Session</kbd> class is mainly designed to have a library independent variable which we
 * can refer to within a session to know if it has been recently created or is an existing one.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Http.Session
 * @subpackage Session
 */
class Session
{
    const IS_FRESH_KEY = 'isFresh';
    const IS_ADMIN_KEY = 'isAdmin';
}
