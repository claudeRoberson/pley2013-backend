<?php // Pley (c) 2014, All Rights Reserved
namespace Pley\Http\Response\Formatter;

/**
 * The <kbd>FormatterInterface</kbd> interface defines the common methods accross Response
 * formatters.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Http.Response.Formatter
 * @subpackage Formatter
 */
interface FormatterInterface
{
    /**
     * Takes an object an returns a formatted array map that can be used for JSON responses.
     * @param mixed $object
     * @return array
     */
    public function format($object);
}
