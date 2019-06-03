<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Exception\User;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>ProfileNonDeletableException</kbd> is triggered when trying to remove the only profile user has.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Exception.User
 * @subpackage Exception
 */
class ProfileNonDeletableException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
        \Pley\Entity\User\User $user, \Pley\Entity\User\UserProfile $userProfile, \Exception $previous = null)
    {
        $message = json_encode(['userId' => $user->getId(), 'userProfileId' => $userProfile->getId()]);
        parent::__construct($message, ExceptionCode::USER_PROFILE_NON_DELETABLE, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}
