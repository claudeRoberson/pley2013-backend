<?php // Pley (c) 2014, All Rights Reserved
namespace Pley\Exception;

/**
 * The <kbd>Code</kbd> class declares error numbers that can map to specific exceptions and thus
 * help out figure out what went wrong when running on Production environments where we don't want
 * to supply information that in the wrong hands could harm our system.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Exception
 */
abstract class ExceptionCode
{
    // The 100XXX range is for codes related to Generic Data exceptions
    const AUTH_INVALID_CREDENTIALS = 100001;
    const AUTH_NOT_AUTHENTICATED   = 100002;

    // The 101XXX range is for ENTITY related exceptions
    const ENTITY_EXISTS     = 101001;
    const ENTITY_NOT_FOUND  = 101002;
    const ENTITY_NOT_UNIQUE = 101003;

    // The 102XXX range is for DAO related exceptions
    const DAO_UNSUPPORTED_METHOD  = 102001;
    const DAO_UPDATE_NOT_ALLOWED  = 102002;

    // The 500XXX range is for codes related to Repository or Storage exceptions
    const REPOSITORY_ENTITY_NOT_FOUND  = 500001;
    const REPOSITORY_EXISTING_ENTITY   = 500002;
    const REPOSITORY_ENTITY_NOT_UNIQUE = 500003;

    // The 125XXX range is for Mail related exceptions (relationship to SMTP Port default = 25)
    const MAIL_RESEND_LIMIT     = 125001;
    const MAIL_UNKNOWN_TEMPLATE = 125002;

    // The 199XXX range is for User related exceptions
    const USER_ADDRESS_EXISTS          = 199001;
    const USER_ADDRESS_DELETE          = 199002;
    const USER_PROFILE_EXISTS          = 199003;
    const USER_PROFILE_DELETE          = 199004;
    const USER_ADDRESS_NON_DELETABLE   = 199006;
    const USER_PROFILE_NON_DELETABLE   = 199007;

    //const USER_PAYMENT_METHOD_EXISTS = 199005; Not used in favor of reusing `PAYMENT_METHOD_EXISTS`
    const USER_PAYMENT_METHOD_DELETE   = 199006;
    const USER_PASSWORD_TOKEN_REDEEMED = 199007;
    const USER_EXISTING                = 199008;

    // The 200XXX range is for Registration related exceptions
    const REGISTRATION_EXISTING_USER         = 200001;
    const REGISTRATION_EXISTING_SUBSCRIPTION = 200004;

    // The 201XXX range is for Profile Subscription related exceptions
    const PROFILE_SUBSCRIPTION_NON_CANCELABLE             = 201001;
    const PROFILE_SUBSCRIPTION_INVALID_REACTIVATION_STATE = 201002;
    
    // The 202XXX range is for Payment System related exceptions
    const PAYMENT_MISMATCHING_SYSTEM    = 202001;
    const PAYMENT_METHOD_EXISTS         = 202002;
    const PAYMENT_METHOD_DOES_NOT_EXIST = 202003;
    const PAYMENT_METHOD_EXPIRED        = 202004;
    const PAYMENT_METHOD_INVALID_INPUT  = 202005;
    const PAYMENT_METHOD_DECLINED       = 202006;
    const PAYMENT_METHOD_BAD_ZIP        = 202007;
    const PAYMENT_METHOD_PROCESSING     = 202999;

    // The 201XXX range is for Gift related exceptions
    const GIFT_REDEEMED                 = 201001;

    // The 204XXX range is for Coupon related exceptions
    const COUPON_NOT_FOUND                      = 204001;
    const COUPON_EXPIRED                        = 204002;
    const COUPON_MAX_USAGES_EXCEEDED            = 204003;
    const COUPON_MAX_USAGES_PER_USER_EXCEEDED   = 204004;
    const COUPON_SUBSCRIPTION_MISMATCH          = 204005;
    const COUPON_DISABLED                       = 204006;
    const COUPON_TYPE_INVALID                   = 204007;
    const COUPON_MIN_BOXES_NOT_REACHED          = 204008;
    const COUPON_DISCOUNT_INVALID               = 204009;
    
    // The 212XXX range is for Waitlist related exceptions
    const WAITLIST_NO_AVAILABLE_INVENTORY       = 212001;
    const WAITLIST_NO_CURRENT_BOX_SET           = 212002;

    // The 400XXX range is for codes related to Validations on Requests
    const REQUEST_INVALID_PARAMETER             = 400001;
    const REQUEST_INVALID_FORMAT                = 400002;

    // ---------------------------------------------------------------------------------------------
    // The 600XXX range is for codes related to logic specific exceptions that do not belong to 
    // big specifc group, so we'll start having 601XXX or 602XXX, etc

    // * Using 601XXX for shipping related errors
    const SHIPPING_LABEL_SERVICE_UNAVAILABLE                = 601004;
    const SHIPPING_LABEL_PURCHASE                           = 601007;

    const STRIPE_WEBHOOK_SUBSCRIPTION_NOT_FOUND             = 601008;

    const SHIPPING_ZONE_NOT_FOUND                           = 601009;
}
