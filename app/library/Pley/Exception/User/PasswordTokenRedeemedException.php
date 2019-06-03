<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Exception\User;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>PasswordTokenRedeemedException</kbd> is triggered when trying to redeem a reset password
 * token that has already been redeemed.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.User
 * @subpackage Exception
 */
class PasswordTokenRedeemedException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
            \Pley\Entity\User\User $user, \Pley\Entity\User\UserPasswordReset $passReset, \Exception $previous = null)
    {
        $message = json_encode(['userId' => $user->getId(), 'userPasswordResetId' => $passReset->getId()]);
        parent::__construct($message, ExceptionCode::USER_PASSWORD_TOKEN_REDEEMED, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_CONFLICT;
    }
}
