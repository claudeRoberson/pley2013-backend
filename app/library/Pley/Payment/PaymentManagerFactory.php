<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Payment;

use \Pley\Payment\Impl\Stripe\StripePaymentManager;
use \Pley\Payment\Impl\Paypal\PaypalPaymentManager;

/**
 * The <kbd>PaymentManagerFactory</kbd> provides access to specific PaymentManager implementations.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Payment
 * @subpackage Payment
 */
class PaymentManagerFactory
{
    private static $_instanceCache = [];
    
    /**
     * Get the Payment Manager for the supplied Vendor ID
     * @param int $vendorId
     * @return \Pley\Payment\AbstractPaymentManager
     */
    public static function getManager($vendorId)
    {
        \Pley\Enum\PaymentSystemEnum::validate($vendorId);
        
        switch ($vendorId) {
            case \Pley\Enum\PaymentSystemEnum::STRIPE:
                return static::_getManagerInstance($vendorId, StripePaymentManager::class);
            case \Pley\Enum\PaymentSystemEnum::PAYPAL:
                return static::_getManagerInstance($vendorId, PaypalPaymentManager::class);
            default:
                return static::_getManagerInstance($vendorId, StripePaymentManager::class);
        }
    }
    
    /**
     * Return a cached version of the supplied Manager Class Implementation.
     * @param int    $vendorId  The Vendor Identifier
     * @param string $className Fully qualified name of the class to return and cache.
     * @return \Pley\Payment\AbstractPaymentManager
     */
    private static function _getManagerInstance($vendorId, $className)
    {
        if (!isset(static::$_instanceCache[$vendorId])) {
            static::$_instanceCache[$vendorId] = new $className();
        }

        return static::$_instanceCache[$vendorId];
    }
}
