<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Http\Response;

/**
 * The <kbd>ExceptionInterface</kbd> allows to store the response HTTP code to use for a given
 * exception.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Http.Response
 * @subpackage Exception
 */
interface ExceptionInterface
{
    /**
     * Return the HTTP Statuc Code that represents the current exception.
     * @return int
     * @see \Pley\Exception\ExceptionCode
     */
    public function getHttpCode();
}