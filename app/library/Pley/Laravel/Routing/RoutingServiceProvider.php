<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Laravel\Routing;

/**
 * The <kbd>RoutingServiceProvider</kbd> class overrides the Laravel implementation to allow us to
 * modify a specific method on the Router class so that we can log what was the URI called when a
 * route doesn't match in the RouterCollection method.
 * That allows us to either detect failure calls from Frontend or potential attack attemps.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Laravel.Routing
 */
class RoutingServiceProvider extends \Illuminate\Routing\RoutingServiceProvider
{
    /**
     * Register the router instance with our own.
     */
    protected function registerRouter()
    {
        $this->app['router'] = $this->app->share(function($app) {
            $router = new Router($app['events'], $app);

            // If the current application environment is "testing", we will disable the
            // routing filters, since they can be tested independently of the routes
            // and just get in the way of our typical controller testing concerns.
            if ($app['env'] == 'testing') {
                $router->disableFilters();
            }

            return $router;
        });
    }

}
