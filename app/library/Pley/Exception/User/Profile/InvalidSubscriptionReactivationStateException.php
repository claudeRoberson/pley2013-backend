<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Exception\User\Profile;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\ResponseCode;

/**
 * The <kbd>InvalidSubscriptionReactivationStateException</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class InvalidSubscriptionReactivationStateException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(
            \Pley\Entity\User\User $user,
            \Pley\Entity\Profile\ProfileSubscription $profileSubscription,
            \Exception $previous = null)
    {
        $message = json_encode([
            'userId'                => $user->getId(),
            'profileSubscriptionId' => $profileSubscription->getId(),
            'profileSubscription'   => [
                'status'      => $profileSubscription->getStatus(),
                'isAutoRenew' => $profileSubscription->isAutoRenew(),
            ],
        ]);
        parent::__construct($message, ExceptionCode::PROFILE_SUBSCRIPTION_INVALID_REACTIVATION_STATE, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}
