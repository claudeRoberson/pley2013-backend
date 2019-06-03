<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Laravel\Routing;

use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

/**
 * The <kbd>Router</kbd> class overrides the Laravel implementation to allow us to
 * use our overriden implementation of RouterCollection to be able to log what was the URI used
 * when a route mismatch occurs.
 * That allows us to either detect failure calls from Frontend or potential attack attemps.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Laravel.Routing
 */
class Router extends \Illuminate\Routing\Router
{
    /**
     * Create a new Router instance.
     *
     * @param  \Illuminate\Events\Dispatcher   $events
     * @param  \Illuminate\Container\Container $container
     */
    public function __construct(Dispatcher $events, Container $container = null)
    {
        parent::__construct($events, $container);
        
        $this->routes = new RouteCollection();
    }
}
