<?php /** @copyright Pley (c) 2014, All Rights Reserved */

namespace Pley\Enum\Mail;

/**
 * The <kbd>MailTemplateEnum</kbd> holds constants for known email template ids.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum.Mail
 * @subpackage Enum
 */
abstract class MailTemplateEnum extends \Pley\Enum\AbstractEnum
{
    // Emails triggered by User actions, anything under 100,000 range
    const WELCOME = 1;

    const GIFT_SENDER = 2;
    const GIFT_RECIPIENT = 3;
    const GIFT_REDEEMED = 9;

    const SUBSCRIPTION_CANCEL = 4;

    const SUBSCRIPTION_PAYMENT_ATTEMPT_FAILED = 10;
    const SUBSCRIPTION_PAYMENT_LEFT_UNPAID = 14;

    const SUBSCRIPTION_REACTIVATE = 13;

    const PASSWORD_RESET = 5;
    const PASSWORD_CHANGE = 6;

    const USER_INVITE_REQUEST = 7;

    const SHIPPING_IN_TRANSIT = 8;

    const BOX_SKIPPED = 11;

    const POPUP_SOCIAL_SHARE = 12;

    const WAITLIST_CREATED = 15;
    const WAITLIST_PAYMENT_FAILED = 16;

    const REFERRAL_REWARD_GRANTED = 200;

    const ADDRESS_VALIDATION_FAILURE = 300;
}



