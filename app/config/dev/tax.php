<?php /** @copyright Pley (c) 2014, All Rights Reserved */

return [
    /*
      |--------------------------------------------------------------------------
      | URLs related to CDN assets
      |--------------------------------------------------------------------------
      |
      | List of URLs on the CDN for the different types needed
     */
    'pragmaRx' => [
        'webServiceUrl'  => 'https://api.zippopotam.us',
        'webServiceName' => 'Zippopotamus',
        'country'        => 'US'
    ],
    'avalara'  => [
        'accountNumber'             => '1100059486',
        'licenseKey'                => '43C43DFD7493883A',
        'serviceUrl'                => 'https://development.avalara.net',
        'states'                    => ['CA', 'TX', 'NY', 'FL', 'IL', 'MA', 'PA', 'VA', 'CO', 'MD', 'AZ', 'CT', 'MO',
            'SC', 'AL', 'LA', 'NM', 'ID', 'ME', 'MS', 'HI'],
        'lookupByZipApiKey'         => 'XOXraHmmluomK5M9Hd6CK9VixRBkEWL66vr+Hv5iMhNWPRDowWVw2pSu7rYab5VJ8gRV+65zS/1OKEENa+qn8w==',
        'webServiceUrlForTaxLookup' => 'https://taxrates.api.avalara.com:443',
    ]
];
