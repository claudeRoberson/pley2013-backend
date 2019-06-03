<?php

/*
|--------------------------------------------------------------------------
| Register The Artisan Commands
|--------------------------------------------------------------------------
|
| Each available Artisan command must be registered with the console so
| that it is available to be called. We'll register every command so
| the console gets access to each of the command object instances.
|
*/

Artisan::add(new \Pley\Console\Shipment\LabelPurchaserCommand());
Artisan::add(new \Pley\Console\Shipment\UndeliveredShipmentsCheckCommand());
Artisan::add(new \Pley\Console\Gift\GiftRecipientNotifierCommand());

Artisan::add(new \Pley\Console\DailyReport\PleyBoxMembersCommand());
Artisan::add(new \Pley\Console\VendorIntegration\Hatchbuck\HatchbuckCommand());

Artisan::add(new \Pley\Console\Tools\SubscriptionItemSequenceRetrofeedCommand());
Artisan::add(new \Pley\Console\Waitlist\SendOutPaymentReminders());
Artisan::add(new \Pley\Console\AfterBoxSurvey\ShipmentsReportCommand());
Artisan::add(new \Pley\Console\Billing\Stripe\PaymentRetryCommand());

// Commands used to Initialize after deployment, only meant to be run once.

Artisan::add(new \Pley\Console\Init\EntityAnnotationsPreCompileCommand());
Artisan::add(new \Pley\Console\Tools\DisneyInventorySynchronizerCommand());
Artisan::add(new \Pley\Console\Tools\ShiftDeadlineDisneyShipments());
Artisan::add(new \Pley\Console\Tools\RefundNatGeoCommand());

Artisan::add(new \Pley\Console\Tools\ChangeNatGeoScheduleCommand());
Artisan::add(new \Pley\Console\Tools\ShowSubscriptionShipmentsByMonth());
Artisan::add(new \Pley\Console\Tools\AssignChurnDates());

Artisan::add(new \Pley\Console\Tools\GetDisneyReceivedBoxes());
Artisan::add(new \Pley\Console\Tools\RescheduleDisneyMayBoxes());
Artisan::add(new \Pley\Console\Tools\RefundShippingLabels());
