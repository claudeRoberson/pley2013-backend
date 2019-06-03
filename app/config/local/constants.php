<?php /** @copyright Pley (c) 2014, All Rights Reserved */

return [
    /*
      |--------------------------------------------------------------------------
      | URLs related to CDN assets
      |--------------------------------------------------------------------------
      |
      | List of URLs on the CDN for the different types needed
     */
    'cdn' => [
        'storage' => [
            // Known values for storage type are:
            //   S3, local
            'type'          => 'S3',
            'defaultBucket' => 'website-images',
        ],
        'url'     => [
        //'key1' => 'http://local.api.pley.com/local_cdn/path',
        //'key2' => 'https://dnqe9n02rny0n.cloudfront.net/path',
        ],
    ],
    
    'facebook' => [
        'pageId'      => 1082973655047506,
        'accessToken' => 'CAAM91BfS0kgBAAoekS8HuQZBSOE3TUFJM1oLEckG1aRsAMBrpidTu21ZBZAU4cFQd70u8ohUN6v0Wdc1xZA8mVTBoBX7T5fGShRsh1YzYHaO2htE2e5naVSBE1ZBS96ubY7AznNI2zzGeVTKzR0W90ZAIExdySfq7pgiUbZBm3ZB9KtsOTPyMLyQHf6jEbnSJ1XrZBOF3vJh4RgZDZD',
        'website'     => 'https://dev2.pley.com/',
        'sharePage'   => 'https://www.facebook.com/localbookpage/posts/'
    ],

    'nps' => [
        'delighted' =>  [
            'apiKey'       => 'orUMyP6Kqhef3ZOJy89dXdxUqXd4O8pC',
            'sandbox'      => true,
            'sandboxEmail' => 'vsevolod.yatsuk@agileengine.com'
        ]
    ],
];
