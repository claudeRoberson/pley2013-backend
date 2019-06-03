<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Exception\User;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>ProfileExistingException</kbd> is thrown when trying to add a new profile with values
 * identical to another profile the user already has, or is trying to update an existing profile but
 * the new values also match the values of another profile the user already has.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception.User
 * @subpackage Exception
 */
class ProfileExistingException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
            \Pley\Entity\User\User $user, \Pley\Entity\User\UserProfile $userProfile, \Exception $previous = null)
    {
        $message = json_encode(['userId' => $user->getId(), 'userProfileId' => $userProfile->getId()]);
        parent::__construct($message, ExceptionCode::USER_PROFILE_EXISTS, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_CONFLICT;
    }
}
