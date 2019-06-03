<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Exception\User;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>AddressDeleteException</kbd> is triggered when trying to remove an address that is associated
 * to an active subscription.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.User
 * @subpackage Exception
 */
class AddressDeleteException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
            \Pley\Entity\User\User $user, \Pley\Entity\User\UserAddress $userAddress, \Exception $previous = null)
    {
        $message = json_encode(['userId' => $user->getId(), 'userAddressId' => $userAddress->getId()]);
        parent::__construct($message, ExceptionCode::USER_ADDRESS_DELETE, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_CONFLICT;
    }
}
