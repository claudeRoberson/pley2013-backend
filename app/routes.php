<?php

//--------------------------------------------------------------------------
// Application Routes
//--------------------------------------------------------------------------

Route::pattern('intId', '[0-9]+');
Route::pattern('intId2', '[0-9]+');

// -------------------------------------------------------------------------------------------------
// Route group with API versioning for User facing endpoints
Route::group(['prefix' => 'api/v1'], function () {
    // Global calls not related to Users
    Route::get('subscription/{intId}/signup/{countryCode}', 'api\v1\Subscription\SubscriptionController@signupInfoByCountry');
    Route::get('subscription/{intId}/signup/{countryCode}/{stateCode}', 'api\v1\Subscription\SubscriptionController@signupInfoByCountryState');

    Route::get('subscription/{intId}/signup', 'api\v1\Subscription\SubscriptionController@infoForSignup');

    Route::post('address/verify', 'api\v1\AddressController@verify');

    // Calls to create a user and subscribe
    Route::post('user/register/account', 'api\v1\User\UserRegistrationController@newAccount');

    // Calls related to users
    Route::post('user/login', 'api\v1\User\UserAuthController@login');
    Route::post('user/logout', 'api\v1\User\UserAuthController@logout');

    Route::get('user/account', 'api\v1\User\UserAccountController@account');
    Route::get('user/account/for-subscription', 'api\v1\User\UserAccountController@forNewSubscription');

    Route::put('user/password/change', 'api\v1\User\UserPasswordController@change');
    Route::post('user/password/reset', 'api\v1\User\UserPasswordController@resetRequest');
    Route::get('user/password/reset/{token}', 'api\v1\User\UserPasswordController@checkToken');
    Route::put('user/password/reset/{token}', 'api\v1\User\UserPasswordController@resetRedeem');

    Route::post('user/address', 'api\v1\User\UserAddressController@add');
    Route::put('user/address/{intId}', 'api\v1\User\UserAddressController@update');
    Route::get('user/address/{intId}', 'api\v1\User\UserAddressController@get');
    Route::delete('user/address/{intId}', 'api\v1\User\UserAddressController@remove');
    Route::post('user/address/{intId}/notify', 'api\v1\User\UserAddressController@notifyInvalidAddress');

    Route::post('user/profile', 'api\v1\User\Profile\UserProfileController@add');
    Route::put('user/profile/{intId}', 'api\v1\User\Profile\UserProfileController@update');
    Route::delete('user/profile/{intId}', 'api\v1\User\Profile\UserProfileController@remove');

    Route::post('user/payment-method', 'api\v1\User\UserPaymentMethodController@add');
    Route::put('user/payment-method/{intId}', 'api\v1\User\UserPaymentMethodController@update');
    Route::put('user/payment-method/{intId}/default', 'api\v1\User\UserPaymentMethodController@setDefault');
    Route::delete('user/payment-method/{intId}', 'api\v1\User\UserPaymentMethodController@remove');

    Route::post('/user/profile/subscription/paid', 'api\v1\User\Profile\ProfileSubscriptionController@addPaid');
    Route::post('/user/profile/subscription/gift', 'api\v1\User\Profile\ProfileSubscriptionController@addGift');
    Route::put('/user/profile/subscription/{intId}/address', 'api\v1\User\Profile\ProfileSubscriptionController@swapAddress');
    Route::get('/user/profile/subscription/{intId}/autorenew-stop', 'api\v1\User\Profile\ProfileSubscriptionController@getDetailsForAutoRenewStop');
    Route::put('/user/profile/subscription/{intId}/autorenew-stop', 'api\v1\User\Profile\ProfileSubscriptionController@autoRenewStop');
    Route::put('/user/profile/subscription/{intId}/skip-box', 'api\v1\User\Profile\ProfileSubscriptionController@skipBox');
    Route::get('/user/profile/subscription/{intId}/skip-box-info', 'api\v1\User\Profile\ProfileSubscriptionController@skipBoxInfo');
    Route::put('/user/profile/subscription/{intId}/reactivate', 'api\v1\User\Profile\ProfileSubscriptionController@reactivate');

    // Referrals functionality
    Route::get('/user/referral/token', 'api\v1\User\UserReferralController@getUniversalTokens');
    Route::get('/user/referral/info', 'api\v1\User\UserReferralController@getReferralInfo');
    Route::get('/user/referral/details/{token}', 'api\v1\User\UserReferralController@getReferralDetails');
    Route::post('/referral/token', 'api\v1\User\UserReferralController@createFacebookReferralToken');

    // Invite a Friend
    Route::post('user/invite/friend/email', 'api\v1\User\UserInviteController@inviteFriendEmail');
    Route::get('user/invite', 'api\v1\User\UserInviteController@getUserInvites');

    Route::post('invite/friend/email', 'api\v1\User\UserInviteController@nonUserInviteEmail');

    Route::get('gift/subscription/{intId}', 'api\v1\Gift\GiftController@infoForGift');
    Route::get('gift/token/{token}', 'api\v1\Gift\GiftController@getTokenDetails');
    Route::post('gift/add', 'api\v1\Gift\GiftController@addGift');

    Route::get('/shipping/address/{addressId}/rate', 'api\v1\Shipping\RateController@getShippingRate');

    Route::get('coupon/code/{code}', 'api\v1\Coupon\CouponController@getCodeInfo');
    Route::get('coupon/code/{code}/{countryCode}', 'api\v1\Coupon\CouponController@getCodeInfoByCountry');

    // Nat Geo specific links.
    Route::get('nat-geo/subscription/{intId}/experience-url', 'api\v1\User\Profile\NatGeoProfileSubscriptionController@getExperienceUrl');

    // Popup Event
    Route::get('popup/event-list', 'api\v1\Frontend\PopupEventController@getPopupEventList');
    Route::post('popup/event', 'api\v1\Frontend\PopupEventController@registerPopupEvent');

    // Countdowns
    Route::post('countdown/get', 'api\v1\Frontend\CountdownController@getCountdown');
    Route::get('countdown/get', 'api\v1\Frontend\CountdownController@getCountdown');

    //Notifications
    Route::post('notification/subscribe/reveal', 'api\v1\Notification\NotificationSubscriberController@subscribeRevealNotifications');

    //Service related endpoints
    Route::post('service/auth/login', 'api\v1\Service\AuthController@login');
    Route::post('service/auth/register', 'api\v1\Service\AuthController@register');

    //PayPal related endpoints
    Route::post('checkout/paypal/init-subscription', 'api\v1\Checkout\PaypalController@initSubscription');
    Route::post('checkout/paypal/execute-subscription', 'api\v1\Checkout\PaypalController@executeSubscription');
    Route::post('checkout/paypal/complete-registration', 'api\v1\Checkout\PaypalController@completeRegistration');

    //Marketing related endpoints
    Route::get('marketing/special-coupons', 'api\v1\Marketing\SpecialCouponsController@getSpecialCoupons');
});

Route::group(['prefix' => 'api/v2'], function () {
    // Calls to create a user and subscribe
    Route::post('user/register/account', 'api\v2\User\UserRegistrationController@newAccount');
    Route::post('user/register/waitlist-billing', 'api\v2\User\UserRegistrationController@waitlistBilling');
    Route::post('user/register/waitlist-gift', 'api\v2\User\UserRegistrationController@waitlistGift');
    Route::post('user/register/profile', 'api\v2\User\UserRegistrationController@addProfile');
    Route::post('user/register/address', 'api\v2\User\UserRegistrationController@addAddress');

    Route::get('user/waitlist/inventory-for-release', 'api\v2\User\UserWaitlistController@isInventoryForRelease');
    Route::post('user/waitlist/share-release', 'api\v2\User\UserWaitlistController@shareRelease');

    Route::post('/user/profile/waitlist-billing', 'api\v2\User\Profile\ProfileSubscriptionController@addWaitlistBilling');
    Route::post('/user/profile/waitlist-gift', 'api\v2\User\Profile\ProfileSubscriptionController@addWaitlistGift');
    Route::delete('/user/profile/waitlist/{intId}', 'api\v2\User\Profile\ProfileSubscriptionController@cancelWaitlist');

    // Endpoints, which ignore waitlist entry creation
    Route::post('user/register/billing', 'api\v2\User\UserRegistrationController@subscriptionWithBilling');
    Route::post('user/register/gift', 'api\v2\User\UserRegistrationController@subscriptionWithGiftToken');
});

// -------------------------------------------------------------------------------------------------
// Route group with API versioning for Warehouse facing endpoints

Route::group(['prefix' => 'operations/v1'], function () {
    Route::post('auth/login', 'operations\v1\BackendUserAuthController@login');

    Route::get('assemby/subscription/', 'operations\v1\Shipment\AssemblyController@getSubscriptionList');
    Route::get('assemby/subscription/{intId}/active-schedule', 'operations\v1\Shipment\AssemblyController@activeSchedule');
    Route::get('assemby/subscription/{intId}/active-schedule/item/{intId2}', 'operations\v1\Shipment\AssemblyController@activeScheduleForItem');
    Route::put('assemby/subscription/{intId}/item/{intId2}/process', 'operations\v1\Shipment\AssemblyController@processItemAndNext');
    Route::put('assemby/subscription/{intId}/item/{intId2}/batch', 'operations\v1\Shipment\AssemblyController@batchProcess');
    Route::put('assemby/subscription/{intId}/item/{intId2}/batch/new', 'operations\v1\Shipment\AssemblyController@batchProcessNewOnly');
    Route::get('assemby/label-img/{intId}', 'operations\v1\Shipment\AssemblyController@getPngLabel');

    Route::put('shipment/{intId}/label', 'operations\v1\Shipment\LabelController@purchaseShipmentLabel');
    Route::post('shipment/label/refund', 'operations\v1\Shipment\LabelController@refundShipmentLabels');

    Route::post('cs/user/search/recent', 'operations\v1\CustomerService\User\UserSearchController@getCurrent');
    Route::post('cs/user/search', 'operations\v1\CustomerService\User\UserSearchController@search');
    Route::get('cs/user/{intId}', 'operations\v1\CustomerService\User\UserDetailController@getUser');
    Route::put('cs/user/{intId}/email', 'operations\v1\CustomerService\User\UserUpdateController@updateEmail');

    Route::post('cs/user/{intId}/note-create', 'operations\v1\CustomerService\User\UserNoteController@create');
    Route::delete('cs/user/note-delete/{id}', 'operations\v1\CustomerService\User\UserNoteController@delete');
    Route::get('cs/user/{intId}/note-get-all', 'operations\v1\CustomerService\User\UserNoteController@getAll');

    Route::delete('cs/profile/subscription/{intId}/full-cancel', 'operations\v1\CustomerService\User\Profile\ProfileSubscriptionController@fullCancel');

    Route::post('marketing/coupon', 'operations\v1\Marketing\Coupon\CouponController@create');
    Route::get('marketing/coupon/{id}', 'operations\v1\Marketing\Coupon\CouponController@get');
    Route::put('marketing/coupon/{id}', 'operations\v1\Marketing\Coupon\CouponController@update');
    Route::delete('marketing/coupon/{id}', 'operations\v1\Marketing\Coupon\CouponController@delete');

    Route::get('marketing/banner', 'operations\v1\Marketing\Banner\RevealBannerController@getAll');
    Route::post('marketing/banner', 'operations\v1\Marketing\Banner\RevealBannerController@create');
    Route::get('marketing/banner/{id}', 'operations\v1\Marketing\Banner\RevealBannerController@get');
    Route::put('marketing/banner/{id}', 'operations\v1\Marketing\Banner\RevealBannerController@update');

    Route::get('marketing/coupon', 'operations\v1\Marketing\Coupon\CouponGridController@index');
    Route::get('marketing/coupon/search/{term}', 'operations\v1\Marketing\Coupon\CouponGridController@search');

    Route::get('marketing/referral/reward', 'operations\v1\Marketing\Referral\RewardController@getUserReferralRewards');
    Route::get('marketing/referral/{referralEmail}/detail', 'operations\v1\Marketing\Referral\RewardController@getUserReferralDetails');
    Route::post('marketing/referral/{userId}/reward', 'operations\v1\Marketing\Referral\RewardController@grantRewardToUser');
    Route::get('marketing/referral/program', 'operations\v1\Marketing\Referral\RewardController@getReferralPrograms');

    Route::get('marketing/invites/csv', 'operations\v1\Marketing\Referral\InviteController@getInvitesAsCsv');

    Route::post('warehouse/stock/induction', 'operations\v1\Warehouse\Stock\StockController@createPartStockInduction');
    Route::get('warehouse/subscription', 'operations\v1\Warehouse\Stock\StockController@getSubscriptions');
    Route::get('warehouse/box/{id}/parts', 'operations\v1\Warehouse\Stock\StockController@getBoxParts');

    Route::get('warehouse/stock', 'operations\v1\Warehouse\Stock\StockGridController@index');
    Route::get('warehouse/stock/search/{term}', 'operations\v1\Warehouse\Stock\StockGridController@search');
    Route::get('warehouse/subscription/{subscriptionId}/running-totals', 'operations\v1\Warehouse\Stock\RunningTotalsController@getRunningTotals');
    Route::get('warehouse/subscription/in-order', 'operations\v1\Warehouse\Stock\RunningTotalsController@getOrderedSubscriptions');

    Route::post('cs/gift/search', 'operations\v1\CustomerService\Gift\GiftController@search');


    Route::get('payment/plans', 'operations\v1\Payment\PaymentPlanController@listPaymentPlans');

    Route::post('payment/paypal/billing-plans', 'operations\v1\Payment\Paypal\BillingPlanController@createBillingPlan');
    Route::get('payment/paypal/billing-plans', 'operations\v1\Payment\Paypal\BillingPlanController@listBillingPlans');
    Route::get('payment/paypal/billing-plans/{id}', 'operations\v1\Payment\Paypal\BillingPlanController@getBillingPlanInfo');

    Route::get('payment/paypal/agreements/{id}', 'operations\v1\Payment\Paypal\BillingAgreementController@getBillingAgreementInfo');
});

Route::group(['prefix' => 'operations/v2'], function () {
    Route::get('waitlist/items/subscription/{subscriptionId}', 'operations\v2\Waitlist\ReleaseWaitlistController@getWaitlistItemsForSubscription');
    Route::post('waitlist/release/subscription/{subscriptionId}', 'operations\v2\Waitlist\ReleaseWaitlistController@releaseWaitlistItems');
    Route::get('bi/calcStats/nu3t423gt52u536566', 'operations\v2\BI\BIController@handle');
});

// -------------------------------------------------------------------------------------------------
// Route group with endpoint to listener controllers for webhook services
Route::group(['prefix' => 'webhook/v1'], function () {
    Route::post('easy-post/event', 'webhook\v1\EasyPostListenerController@listen');
    Route::post('stripe/event', 'webhook\v1\StripeListenerController@handle');
    Route::post('paypal/event', 'webhook\v1\PaypalListenerController@handle');

});
