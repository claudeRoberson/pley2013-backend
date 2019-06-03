<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Laravel\Providers;

/**
 * The <kbd>PleyShippingServiceProvider</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley\Laravel
 * @subpackage ServiceProvider
 */
class PleyShippingServiceProvider extends \Illuminate\Support\ServiceProvider
{
    const SHIPPING_PATH  = '\\Pley\\Shipping\\';
    const IMPL_EASY_POST = 'EasyPost';
    
    public function register()
    {
        $implToUse = self::IMPL_EASY_POST;
        
        $bindPath = self::SHIPPING_PATH . 'AbstractShipmentManager';
        $implPath = self::SHIPPING_PATH . 'Impl\\'. $implToUse . '\\ShipmentManager';
        
        $this->app->bind($bindPath, $implPath);
    }
}
