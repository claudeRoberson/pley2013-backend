<?php /** @copyright Pley (c) 2015, All Rights Reserved */

namespace Pley\Enum;

/**
 * The <kbd>EventEnum</kbd> contains constants related to the known events we trigger
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
final class EventEnum
{
    const SUBSCRIPTION_CREATE = 'profile.subscription.create';

    const SUBSCRIPTION_REACTIVATE = 'profile.subscription.reactivate';

    const REFERRAL_ACQUISITION_CREATE = 'referral.acquisition.create';

    const SHIPMENT_PROGRESS = 'shipment.progress';

    const WAITLIST_CREATE = 'waitlist.create';

    const WAITLIST_PAYMENT_FAILED = 'waitlist.payment.failed';

    const USER_ACCOUNT_CREATE = 'user.account.create';

    const PROFILE_SUBSCRIPTION_UPDATED = 'profile.subscription.updated';

    const RECURRING_TRANSACTION_CREATED = 'recurring.transaction.created';
}
