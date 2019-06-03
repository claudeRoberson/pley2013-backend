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
class NoAvailableInventoryException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(\Pley\Entity\User\User $user, $subscriptionIdList, \Exception $previous = null)
    {
        $message = json_encode(['userId' => $user->getId(), 'subscriptionIdList' => array_values($subscriptionIdList)]);
        parent::__construct($message, ExceptionCode::WAITLIST_NO_AVAILABLE_INVENTORY, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_PRECONDITION_FAILED;
    }
}
