<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Exception\User;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>RegistrationExistingSubscriptionException</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class RegistrationExistingSubscriptionException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(\Pley\Entity\User\User $user, \Exception $previous = null)
    {
        $message = json_encode(['userId' => $user->getId()]);
        parent::__construct($message, ExceptionCode::REGISTRATION_EXISTING_SUBSCRIPTION, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_CONFLICT;
    }
}
