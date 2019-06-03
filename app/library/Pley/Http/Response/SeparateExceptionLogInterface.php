<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Http\Response;

/**
 * The <kbd>SeparateExceptionLogInterface</kbd> allows Exception classes to define a specifc log file
 * where the output should be written to.
 * <p>This allows to unclog the main error log file from common exceptions, like failed attempts to
 * log in, or to isolate more specific errors into a target log.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Http.Response
 * @subpackage Exception
 */
interface SeparateExceptionLogInterface
{
    /**
     * Returns the name of the Log where this exception should be written to.
     * @return string
     */
    public function getLogName();
}
