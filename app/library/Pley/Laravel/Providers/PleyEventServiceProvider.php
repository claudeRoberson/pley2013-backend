<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Laravel\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Events service provider
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley\Laravel
 * @subpackage ServiceProvider
 */
class PleyEventServiceProvider extends ServiceProvider
{
    /** Registers the service provider */
    public function register()
    {
        \Event::subscribe($this->app->make(\Pley\Event\ProfileEventSubscriber::class));
        \Event::subscribe($this->app->make(\Pley\Event\ProfileSubscriptionEventSubscriber::class));
        \Event::subscribe($this->app->make(\Pley\Event\ReferralEventSubscriber::class));
        \Event::subscribe($this->app->make(\Pley\Event\NatGeoEventSubscriber::class));
        \Event::subscribe($this->app->make(\Pley\Event\WaitlistEventSubscriber::class));
        \Event::subscribe($this->app->make(\Pley\Event\UserEventSubscriber::class));
    }
}
