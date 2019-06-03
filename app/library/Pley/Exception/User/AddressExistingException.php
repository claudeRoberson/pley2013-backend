<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Exception\User;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>AddressExistingException</kbd> is thrown when trying to add a new address with values
 * identical to another address the user already has, or is trying to update an existing address but
 * the new values also match the values of another address the user already has.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.User
 * @subpackage Exception
 */
class AddressExistingException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
            \Pley\Entity\User\User $user, \Pley\Entity\User\UserAddress $userAddress, \Exception $previous = null)
    {
        $message = json_encode(['userId' => $user->getId(), 'userAddressId' => $userAddress->getId()]);
        parent::__construct($message, ExceptionCode::USER_ADDRESS_EXISTS, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_CONFLICT;
    }
}
