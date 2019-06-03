<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Http\Response;

/**
 * The <kbd>OneLineExceptionInterface</kbd> indicates that this is an exception that is most likely
 * common and we don't need to print multiple lines of stack trace beyond the message.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Http.Response
 * @subpackage Exception
 */
interface OneLineExceptionInterface
{
    /**
     * Return the one line message from the exception including where it happened and the exception
     * associated to it.
     * @return string
     */
    public function getOneLineMessage();
}
