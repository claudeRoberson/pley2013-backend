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
     | @TODO: Replace this key with the production key.
     */
    'apiKey' => 'X6jYrjDBcskh0bCZdBk0ww',

    /*
     |--------------------------------------------------------------------------
     | The warehouse address
     |--------------------------------------------------------------------------
     |
     | Currently we only support one warehouse.
     | 
     | @TODO: Figure out how to support multiple warehouse addresses for shipment and returns.
     |        We could put an extra column on the inventory to identify where that specific item
     |        is located and based on that determine the addresses.
     */
    'address' => [
        'name'     => 'Pley Fulfillment Center',
        'address1' => '6710 GRADE LN STE 610',
        'address2' => null,
        'city'     => 'LOUISVILLE',
        'state'    => 'KY',
        'zipCode'  => '40213',
        'country'  => 'US',
    ],

    /*
     |--------------------------------------------------------------------------
     | UPS MailInnovations definitions
     |--------------------------------------------------------------------------
     |
     | Currently we only support one warehouse.
     */
    'upsMailInnovations' => [
        // According to UPS, we define the cost center value, we can have many, the only restriction
        // is that it should be a 4 digit number starting from 1000
        // So, since we are not in need of specific multiple cost centers for now, just defining one
        // with the base number.
        'costCenter' => '1000',
    ],

    'allowedCountries' => [
        'US', //'CA', 'GB', 'AU' ,'NZ', 'ES', 'IE', 'IL', 'SG', 'NO'
    ],

    'allowedUsStates' =>[
            // States
            'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
            'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
            'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
            'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
            'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY',
            'HI',
            // Federal District
            'DC',
    ],

    /*
     |--------------------------------------------------------------------------
     | Tracking Number URLs based on carrier
     |--------------------------------------------------------------------------
     |
     | Carrier IDs are specified on \Pley\Enum\Shipping\CarrierServiceEnum
     | The URL is a string that can be parsed by the `sprintf()` function
     | e.g.
     |    'https://tracking.com/number=%s'
     |
     | So the replacement will be something like:
     |    $parsedUrl = sprintf($url, $trackingNumber);
     |
     */
    'trackingUrl' => [
        // USPS
        1000 => 'https://tools.usps.com/go/TrackConfirmAction.action?tLabels=%s',
        // UPS MailInnovations
        2100 => 'http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=%s',
        // UPS SurePost
        2200 => 'http://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=%s',
        // FedEx
        3000 => 'https://www.fedex.com/fedextrack/WTRK/index.html?tracknumbers=%s',
        // DHL FirstMile
        4100 => 'http://webtrack.dhlglobalmail.com/?trackingnumber=%s',
    ],

    'carrierOverride' => [
        'enabled' => false,
        'service' => 'USPS_PRIORITY'
    ],

    'rulesMap' => [
        'shippingZones'=>[
            1 => [
                'serviceMap' => [
                    ['<=', 16, 'UPS_SP_UNDER_1LB'],
                    ['>', 16, 'UPS_SP_OVER_1LB'],
                ]
            ],
            2 =>[
                'serviceMap' => [
                    ['<=', 16, 'UPS_SP_UNDER_1LB'],
                    ['>', 16, 'UPS_SP_OVER_1LB'],
                ]
            ],
            3 =>[
                'serviceMap' => [
                    ['<=', 16, 'UPS_SP_UNDER_1LB'],
                    ['>', 16, 'UPS_SP_OVER_1LB'],
                ]
            ]
        ]
    ],
];
