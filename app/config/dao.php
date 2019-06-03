<?php /** @copyright Pley (c) 2015, All Rights Reserved */

// This configuration is used to define the type of cache used by the DAOs
return [
    // Types of Cache = inMemory, dynamic, static
    // Note that the through the DAO Service Provider (PleyDaoServiceProvider), if `static` or
    // `dynamic` are selected, these will be decorators wrapping inMemory
    // +  `static`  => responds to `staticDriver` on the "cache" config file
    // +  `dynamic` => responds to `driver` on the "cache" config file
    
    // This configuration will need more work to make the wrapping more flexible for future cases.
    // TODO, once we get to implementing decorators or more specific caching, adapt this config.
    
    // The `isShared` attribute specifies how will the instance be created and used
    // +  true  = As a Singleton
    // +  false = As a new instance everytime a reference is required
    
    
    // DAOs defined in the `static` section represent those that handle Entities that rarely change
    // and are not affected by user interaction, these entities will usually be cached by the application
    // server, for example, using APCu
    'staticDao' =>[
        'cacheType' => 'inMemory',
        
        // All DAOs are assumed to be located in the \Pley\Dao directory. (start always with \)
        'daoMap' => [
            // Website DAOs
            '\Subscription\SubscriptionDao'  => ['isShared' => true],
            '\Subscription\ItemDao'          => ['isShared' => true],
            '\Subscription\ItemPartDao'      => ['isShared' => true],
            '\Subscription\ItemPartStockDao' => ['isShared' => true],
            
            '\Payment\PaymentPlanDao'        => ['isShared' => true],
            
            // Operations DAOs (Customer Service, Warehouse, etc)
            //'\Part\ItemDesignDao'       => ['isShared' => true],
        ]
    ],
    
    // DAOs defined in the `dynamic` section represent those that handle Entities that are changed
    // by user interaction, and thus will use caching mechanism like MemCache, MemBase, CouchBase
    'dynamicDao' => [
        'cacheType' => 'inMemory',

        // All DAOs are assumed to be located in the \Pley\Dao directory. (start always with \)
        'daoMap' => [
            '\Mail\EmailLogDao'                          => ['isShared' => true],
            
            '\User\UserDao'                              => ['isShared' => true],
            '\User\UserAddressDao'                       => ['isShared' => true],
            '\User\UserProfileDao'                       => ['isShared' => true],
            '\User\UserPasswordResetDao'                 => ['isShared' => true],
            '\User\UserCouponRedemptionDao'              => ['isShared' => true],

            '\Payment\PaymentPlanXVendorPaymentPlanDao'  => ['isShared' => true],
            '\Payment\UserPaymentMethodDao'              => ['isShared' => true],
            
            '\Profile\ProfileSubscriptionDao'            => ['isShared' => true],
            '\Profile\ProfileSubscriptionTransactionDao' => ['isShared' => true],
            '\Profile\ProfileSubscriptionPlanDao'        => ['isShared' => true],
            '\Profile\ProfileSubscriptionShipmentDao'    => ['isShared' => true],
            
            '\Gift\GiftDao'                              => ['isShared' => true],
            '\Gift\GiftPriceDao'                         => ['isShared' => true],

            // Operations DAOs (Customer Service, Warehouse, etc)
            '\Operations\OperationsUserDao'              => ['isShared' => true],
        ]
    ],
];
