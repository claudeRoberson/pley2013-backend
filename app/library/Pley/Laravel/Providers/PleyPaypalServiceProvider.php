<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Laravel\Providers;

use Pley\Billing\PaypalManager;
use PayPal;

/**
 * The <kbd>PleyPaypalServiceProvider</kbd>
 *
 * @author Seva Yatsiuk
 * @version 1.0
 * @package Pley\Laravel
 * @subpackage ServiceProvider
 */
class PleyPaypalServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $apiCredentials = \Config::get('paypal.credentials');
        $config = [
            'mode'=> \Config::get('paypal.mode'),
            'log.LogEnabled' => true,
            'log.FileName' => storage_path() . '/logs/PayPal.log',
            'log.LogLevel' => \Config::get('paypal.logLevel'),
            'http.CURLOPT_CONNECTTIMEOUT' => 30,
            'returnBaseUrl' => \Config::get('paypal.returnBaseUrl')
        ];


        $this->app->bind('PayPal\Rest\ApiContext', function () use ($apiCredentials, $config) {
            $apiContext = new PayPal\Rest\ApiContext(
                new PayPal\Auth\OAuthTokenCredential(
                    $apiCredentials['clientId'],
                    $apiCredentials['clientSecret']
                )
            );
            $apiContext->setConfig($config);
            return $apiContext;
        });
    }
}
