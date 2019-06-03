<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Laravel\Routing;

use Illuminate\Http\Request;

/**
 * The <kbd>RouteCollection</kbd> class overrides the Laravel implementation so that we can log
 * the URI used when a route mismatch occurs.
 * That allows us to either detect failure calls from Frontend or potential attack attemps.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Laravel.Routing
 */
class RouteCollection extends \Illuminate\Routing\RouteCollection
{
    /**
     * Find the first route matching a given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Routing\Route
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function match(Request $request)
    {
        $routes = $this->get($request->getMethod());

        // First, we will see if we can find a matching route for this current request
        // method. If we can, great, we can just return it so that it can be called
        // by the consumer. Otherwise we will check for routes with another verb.
        $route = $this->check($routes, $request);

        if (!is_null($route)) {
            return $route->bind($request);
        }

        // If no route was found, we will check if a matching is route is specified on
        // another HTTP verb. If it is we will need to throw a MethodNotAllowed and
        // inform the user agent of which HTTP verb it should use for this route.
        $others = $this->checkForAlternateVerbs($request);

        if (count($others) > 0) {
            return $this->getOtherMethodsRoute($request, $others);
        }

        throw new Exception\RouteNotFoundException($request->path());
    }
}
