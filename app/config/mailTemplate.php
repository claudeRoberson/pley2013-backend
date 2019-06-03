<?php

// This configuration is used to define the email templates as well as the entities needed for each
// of them.
return [
    /***********************************************************************************************
     * The domain to use when creating links that direct to our websites.
     **********************************************************************************************/
    'siteUrl' => [
        'pley' => [
            'protocol' => 'http',
            'domain'   => 'fe.localhost.com:8888',
        ],
    ],

    'customerSupportEmail' => 'support@pley.com',

    'tagReplacerMap' => [
        /*******************************************************************************************
         * // "tagName" is the key for the map required tag list, and variable name to use for the
         * // email template
         * 'tagName' => [
         *    // This is the name of the tag that will hold the entity object for the template to use
         *    // when parsing values
         *    'tagId'  => 1,  // relates to \Pley\Enum\Mail\MailTagEnum::USER
         * 
         *    // This is the namespace of the entity to make checks that the required entity object
         *    // was supplied for the parsing.
         *    // We use convention to assume that entities reside under the "\Pley\Entity"
         *    // directory and namespace
         *    'entity'  => 'User\User',
         * 
         *    // This is the subpath to the Entity's managing DAO.
         *    // We use convention to assume that entities reside under the "\Pley\Dao"
         *    // directory and namespace
         *    'dao'  => 'User\UserDao',
         * ]
         ******************************************************************************************/
        'user'          => ['tagId' => 1, 'entity' => 'User\User',                 'dao' => 'User\UserDao'],
        'subscription'  => ['tagId' => 2, 'entity' => 'Subscription\Subscription', 'dao' => 'Subscription\SubscriptionDao'],
        'paymentPlan'   => ['tagId' => 3, 'entity' => 'Payment\PaymentPlan',       'dao' => 'Payment\PaymentPlanDao'],
        'gift'          => ['tagId' => 5, 'entity' => 'Gift\Gift',                 'dao' => 'Gift\GiftDao'],
        'giftPrice'     => ['tagId' => 6, 'entity' => 'Gift\GiftPrice',            'dao' => 'Gift\GiftPriceDao'],
        'userPassReset' => ['tagId' => 7, 'entity' => 'User\UserPasswordReset',    'dao' => 'User\UserPasswordResetDao'],
        'invite'        => ['tagId' => 8, 'entity' => 'User\Invite',               'dao' => 'User\Invite'],
        'userProfile'   => ['tagId' => 9, 'entity' => 'User\UserProfile',          'dao' => 'User\UserProfileDao'],
        'token'         => ['tagId' => 10, 'entity' => 'Referral\Token',           'dao' => 'Referral\Token'],
        'coupon'        => ['tagId' => 11, 'entity' => 'Coupon\Coupon',            'dao' => 'none'],
        'acquisition'   => ['tagId' => 12, 'entity' => 'Referral\Acquisition',     'dao' => 'none'],
        'address'       => ['tagId' => 13, 'entity' => 'User\UserAddress',         'dao' => 'User\UserAddressDao'],
    ],
    
    'map' => [
        /*******************************************************************************************
         * // TemplateID should have a matching entry on the \Pley\Enum\Mail\MailTemplateEnum class
         * Template ID => [
         *    'subject'    => 'Subject line on the email', // May contain %s for a inline string replacement
         * 
         *    // The value here will map to an actual Laravel path as follows
         *    //     app/views/path/to/template/name.blade.php
         *    'template'   => 'path.to.template.name',
         * 
         *    // This element represents the required Tag Replacers for the template to render, these
         *    // keys map to the `tagReplacerMap` node on this configuration, which contains
         *    // information about the tag replacer variable to use as well as the Entity mapped to it.
         *    'requiredTagList' => ['tagName' (, ...)],
         * 
         *    // Optional field that if supplied and different from NULL, will pull indicate which
         *    // alternate email address to use to send the respective email.
         *    // The key refers to a key in the `fromAlternate` node of the `mail` configuraion file.
         *    // e.g.
         *    //   If key = 'thirdParty'
         *    //   Computed config key would be 'mail.fromAlternate.thirdParty'
         *    'alternateFrom' => 'key_name'
         * ]
         ******************************************************************************************/
        1 => [
            'subject'         => 'Welcome to Pley!',
            'template'        => 'email.template.welcome',
            'requiredTagList' => ['user', 'subscription', 'paymentPlan'],
        ],
        2 => [
            'subject'         => 'Your gift confirmation',
            'template'        => 'email.template.gift.sender',
            'requiredTagList' => ['gift', 'giftPrice', 'subscription', 'paymentPlan'],
        ],
        3 => [
            'subject'         => 'Yoo - Hoo! you just got a %s gift',
            'template'        => 'email.template.gift.recipient',
            'requiredTagList' => ['gift', 'giftPrice', 'subscription', 'paymentPlan'],
        ],
        4 => [
            'subject'         => 'Your %s subscription has been cancelled',
            'template'        => 'email.template.subscription.cancel',
            'requiredTagList' => ['user', 'subscription', 'userProfile'],
        ],
        5 => [
            'subject'         => 'Reset your Pley password',
            'template'        => 'email.template.passwordReset',
            'requiredTagList' => ['user', 'userPassReset'],
        ],
        6 => [
            'subject'         => 'Your Pley Password Has Changed',
            'template'        => 'email.template.passwordChange',
            'requiredTagList' => ['user'],
        ],
        7 => [
            'subject'         => '%s',
            'template'        => 'email.template.invite.userInviteRequest',
            'requiredTagList' => ['user', 'invite', 'token'],
        ],
        8 => [
            'subject'         => 'Fun is arriving!',
            'template'        => 'email.template.shipping.inTransit',
            'requiredTagList' => ['userProfile', 'subscription'],
        ],
        9 => [
            'subject'         => '%s has activated their Pley gift',
            'template'        => 'email.template.gift.redemption',
            'requiredTagList' => ['gift', 'giftPrice', 'subscription', 'paymentPlan'],
        ],
        10 => [
            'subject'         => 'We weren\'t able to charge your card',
            'template'        => 'email.template.subscription.paymentFailed',
            'requiredTagList' => ['user', 'subscription'],
        ],
        11 => [
            'subject'         => 'Your %s box has been skipped.',
            'template'        => 'email.template.subscription.boxSkipped',
            'requiredTagList' => ['user', 'subscription'],
        ],
        12 => [
            'subject'         => 'Your Pleybox discount code inside',
            'template'        => 'email.template.popup.socialShare',
            'requiredTagList' => ['coupon'],
        ],
        13 => [
            'subject'         => 'It’s time to Pley!',
            'template'        => 'email.template.subscription.reactivate',
            'requiredTagList' => ['subscription'],
        ],
        14 => [
            'subject'         => 'Your Pley box has been skipped.',
            'template'        => 'email.template.subscription.unpaid',
            'requiredTagList' => ['user', 'subscription'],
        ],
        15 => [
            'subject'         => 'Confirmation: You’re now on the Waitlist for a %s Pleybox subscription',
            'template'        => 'email.template.waitlist.created',
            'requiredTagList' => ['user', 'subscription'],
        ],
        16 => [
            'subject'         => 'Please update your credit card information so we can send you your PleyBox',
            'template'        => 'email.template.waitlist.paymentFailed',
            'requiredTagList' => ['user', 'subscription'],
        ],
        200 => [
            'subject'         => 'Congratulations! You earned referral credit',
            'template'        => 'email.template.referral.reward',
            'requiredTagList' => ['user', 'acquisition'],
        ],
        300 => [
            'subject'         => 'Address validation error!',
            'template'        => 'email.template.address.validationError',
            'requiredTagList' => ['user', 'address'],
        ],
    ]
];
