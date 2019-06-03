<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Http\Response\Traits;

/**
 * The <kbd>PaymentSeparateExceptionLogTrait</kbd> provides the common name to log exceptions related
 * to the payment system into a separate file.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Http.Response.Traits
 * @subpackage Exception
 */
trait PaymentSeparateExceptionLogTrait
{
    /**
     * Returns the name of the Log where this exception should be written to.
     * @return string
     */
    public function getLogName()
    {
        return 'Payment';
    }
}
