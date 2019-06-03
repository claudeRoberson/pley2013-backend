<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Enum\Paypal;

use Pley\Enum\AbstractEnum;

/**
 * The <kbd>WebhookEventTypeEnum</kbd> class represents Paypal API request types
 *
 * @author Vsevolod Yatsiuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class WebhookEventTypeEnum extends AbstractEnum
{
    const TYPE_PAYMENT_SUCCEEDED = 'PAYMENT.SALE.COMPLETED';

    const TYPE_PAYMENT_FAILED = 'PAYMENT.SALE.DENIED';

    const TYPE_SUBSCRIPTION_CANCELLED = 'BILLING.SUBSCRIPTION.CANCELLED';

    /*
     * Internal event types, used for error handing, does not exist on PayPal
     * */
    const TYPE_SUBSCRIPTION_NOT_FOUND = 'WEBHOOK.SUBSCRIPTION.NOTFOUND';
}