<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Laravel\Foundation;

use \Stack\Builder;

/**
 * The <kbd>Application</kbd> class extends the Laravel's Foundation Application to allow us to
 * override the session middleware class that takes care of reading/creating and saving sessions.
 * <p>We do this so we can have a more API compatible system where sessions are only created by the
 * User Login or User Registration process.</p>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Laravel.Foundation
 */
class Application extends \Illuminate\Foundation\Application
{
    protected function getStackedClient()
    {
        $sessionReject = $this->bound('session.reject') ? $this['session.reject'] : null;

        $client = (new Builder)
                ->push('Illuminate\Cookie\Guard', $this['encrypter'])
                ->push('Illuminate\Cookie\Queue', $this['cookie'])
                // Here is where we override the default session middleware
                ->push('Pley\Laravel\Foundation\Session\Middleware', $this['session'], $sessionReject);

        $this->mergeCustomMiddlewares($client);

        return $client->resolve($this);
    }
    
    protected function registerRoutingProvider()
    {
        $this->register(new \Pley\Laravel\Routing\RoutingServiceProvider($this));
    }

}
