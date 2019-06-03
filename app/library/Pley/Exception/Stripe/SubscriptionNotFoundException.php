<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Exception\Stripe;

use \Pley\Exception\ExceptionCode;
use \Pley\Http\Response\ExceptionInterface;
use \Pley\Http\Response\OneLineExceptionInterface;
use \Pley\Http\Response\ResponseCode;
use \Pley\Http\Response\OneLineExceptionTrait;

/**
 * The <kbd>SubscriptionNotFoundException</kbd> represents the exception which happens
 * when webhook is triggered by event, with subscription ID, which does not belong to this system
 * e.g. ToyLibrary events
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Stripe.Exception
 * @subpackage Exception
 */
class SubscriptionNotFoundException extends \RuntimeException implements ExceptionInterface, OneLineExceptionInterface
{
    use OneLineExceptionTrait;
    
    public function __construct($subscriptionId, \Exception $previous = null)
    {
        $message = sprintf('Subscription ID: [%s] does not exist', $subscriptionId);
        parent::__construct($message, ExceptionCode::STRIPE_WEBHOOK_SUBSCRIPTION_NOT_FOUND, $previous);
    }

    /** {@inheritdoc} */
    public function getHttpCode()
    {
        return ResponseCode::HTTP_BAD_REQUEST;
    }
}
