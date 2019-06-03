<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Exception\User;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>AddressNonDeletableException</kbd> is triggered when trying to remove the only address user has.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Exception.User
 * @subpackage Exception
 */
class AddressNonDeletableException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
            \Pley\Entity\User\User $user, \Pley\Entity\User\UserAddress $userAddress, \Exception $previous = null)
    {
        $message = json_encode(['userId' => $user->getId(), 'userAddressId' => $userAddress->getId()]);
        parent::__construct($message, ExceptionCode::USER_ADDRESS_NON_DELETABLE, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_CONFLICT;
    }
}
