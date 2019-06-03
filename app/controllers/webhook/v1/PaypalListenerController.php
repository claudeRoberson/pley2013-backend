<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace webhook\v1;

use Pley\Paypal\WebhookEventManager;


/**
 * The <kbd>PaypalListenerController</kbd> Listener to PayPal related events.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class PaypalListenerController extends \BaseController
{

    protected $_paypalEventManager;


    public function __construct(
        WebhookEventManager $stripeEventManager
    )
    {
        $this->_paypalEventManager = $stripeEventManager;

        // Adding a separate log so we can keep cleaner and more dedicated track of webhook calls
        // to this controller
        \LogHelper::popAllHandlers();
        \Log::useDailyFiles(storage_path() . '/logs/webhook-PayPalListener.log');
        \LogHelper::ignoreHandlersEmptyContextAndExtra();
    }

    public function handle()
    {
        \RequestHelper::checkPostRequest();
        $event = $this->_paypalEventManager->initWebhookEvent(\Request::header(), \Input::getContent());
        switch ($event->getEventType()) {
            case (\Pley\Enum\Paypal\WebhookEventTypeEnum::TYPE_PAYMENT_SUCCEEDED):
                $this->_paypalEventManager->handleSuccessfulPayment($event);
                break;
            case (\Pley\Enum\Paypal\WebhookEventTypeEnum::TYPE_PAYMENT_FAILED):
                $this->_paypalEventManager->handleFailedPayment($event);
                break;
            case (\Pley\Enum\Paypal\WebhookEventTypeEnum::TYPE_SUBSCRIPTION_CANCELLED):
                $this->_paypalEventManager->handleSubscriptionCancel($event);
                break;
        }
        return \Response::json(['success' => true, 'message'=>'Event handled successfully.']);
    }
}

