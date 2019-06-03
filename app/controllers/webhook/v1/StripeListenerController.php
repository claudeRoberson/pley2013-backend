<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace webhook\v1;

use Pley\Stripe\Event as StripeEvent;
use Pley\Stripe\WebhookEventManager;

/**
 * The <kbd>StripeListenerController</kbd> Listener to Stripe related events.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class StripeListenerController extends \BaseController
{

    protected $_stripeEventManager;

    protected $_stripeEvent;

    public function __construct(
        WebhookEventManager $stripeEventManager,
        StripeEvent $stripeEvent
    )
    {
        $this->_stripeEventManager = $stripeEventManager;
        $this->_stripeEvent = $stripeEvent;
        
        // Adding a separate log so we can keep cleaner and more dedicated track of webhook calls
        // to this controller
        \LogHelper::popAllHandlers();
        \Log::useDailyFiles(storage_path(). '/logs/webhook-StripeListener.log');
        \LogHelper::ignoreHandlersEmptyContextAndExtra();
    }

    public function handle()
    {
        \RequestHelper::checkPostRequest();
        $event = $this->_stripeEvent->hydrate(\Input::getContent());
        switch ($event->getType()) {
            case (StripeEvent::TYPE_INVOICE_PAYMENT_SUCCEEDED):
                $this->_stripeEventManager->handleRecurringTransaction($event);
                break;
            case (StripeEvent::TYPE_INVOICE_PAYMENT_FAILED):
                $this->_stripeEventManager->handleFailedPayment($event);
                break;
            case (StripeEvent::TYPE_SUBSCRIPTION_CANCEL):
                $this->_stripeEventManager->handleSubscriptionCancel($event);
                break;
        }
        return \Response::json(['success' => true]);
    }
}

