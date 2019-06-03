<?php /** @copyright Pley (c) 2014, All Rights Reserved */

return [
    /*
     |--------------------------------------------------------------------------
     | 3rd Party Shipping API Secret Key
     |--------------------------------------------------------------------------
     |
     | Currently we only support EasyPost, so this key only refers to the EasyPost API Secret Key.
     | If/when we start supporting more carriers, we may want to change the structure into something
     | more like the `database.php` config, and update how the config service provider read and use
     | this configuration.
     | 
     | This is the key for the TEST Environment
     */
    'apiKey' => 'X6jYrjDBcskh0bCZdBk0ww',

    'carrierOverride' => [
        'enabled' => false,
        'service' => 'USPS_PRIORITY'
    ],
];