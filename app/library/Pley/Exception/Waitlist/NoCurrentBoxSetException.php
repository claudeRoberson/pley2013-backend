<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Exception\Waitlist;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>NoAvailableInventoryException</kbd> is triggered when a release attempt is made but
 * there was no available inventory for any of the subscriptions selected by a user in the waitlist.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.Waitlist
 * @subpackage Exception
 */
class NoCurrentBoxSetException extends \RuntimeException implements ExceptionInterface
{
    public function __construct($subscriptionId, \Exception $previous = null)
    {
        $message = json_encode(['subscriptionId' => $subscriptionId, 'message'=>'No active box for a given subscription']);
        parent::__construct($message, ExceptionCode::WAITLIST_NO_CURRENT_BOX_SET, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_PRECONDITION_FAILED;
    }
}
