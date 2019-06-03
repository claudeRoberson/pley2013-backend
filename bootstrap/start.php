<?php

/*
  |--------------------------------------------------------------------------
  | Create The Application
  |--------------------------------------------------------------------------
  |
  | The first thing we will do is create a new Laravel application instance which serves as the
  | "glue" for all the components of Laravel, and is the IoC container for the system binding all of
  | the various parts.
  |
  | We have extended the functionality of the Laravel Application so that we can override the 
  | session middleware and thus allow for a clean way to read the sessions through our personalized
  | header.
  | And avoid adding cookies on every response as well as avoiding writing session for every single
  | request whether the user is authenticated or not.
  | Now only User Login or User Registration will allow to create new sessions and if the sessions
  | exist and are valid, will be saved there after.
  | 
  | Original call was
  | $app = new Illuminate\Foundation\Application;
 */

$app = new Pley\Laravel\Foundation\Application();

/*
  |--------------------------------------------------------------------------
  | Detect The Application Environment
  |--------------------------------------------------------------------------
  |
  | Laravel takes a dead simple approach to your application environments
  | so you can just specify a machine name for the host that matches a
  | given environment, then we will automatically detect it for you.
  |
 */
$localEnvironment = array('default' => array('default'));
if(file_exists(__DIR__ . '/../.env')){
    Dotenv::load(__DIR__ .'/../');
    $env = $app->detectEnvironment(
        function()
        {
            return getenv('APP_ENV');
        }
    );
}else{
    $env = $app->detectEnvironment($localEnvironment);
}
/*
  |--------------------------------------------------------------------------
  | Bind Paths
  |--------------------------------------------------------------------------
  |
  | Here we are binding the paths configured in paths.php to the app. You
  | should not be changing these here. If you need to change these you
  | may do so within the paths.php file and they will be bound here.
  |
 */

$app->bindInstallPaths(require __DIR__ . '/paths.php');

/*
  |--------------------------------------------------------------------------
  | Load The Application
  |--------------------------------------------------------------------------
  |
  | Here we will load this Illuminate application. We will keep this in a
  | separate location so we can isolate the creation of an application
  | from the actual running of the application with a given request.
  |
 */

$framework = $app['path.base'] . '/vendor/laravel/framework/src';

require $framework . '/Illuminate/Foundation/start.php';

/*
  |--------------------------------------------------------------------------
  | Custom Validators
  |--------------------------------------------------------------------------
  |
  | Here we overriding the default Laravel Validator class with one of our own that extends the
  | Laravel's one and provides additional validator methods.
  | The resolver has to be called after the Fountation `start` has been made or the \Validator
  | facade won't be loaded and cause a runtime exception.
  |
 */

Validator::resolver(function($translator, $data, $rules, $messages) {
    return new \Pley\Laravel\Validation\Validator($translator, $data, $rules, $messages);
});


// Initializing Stripe Key for Billing Vendor
\Stripe\Stripe::setApiKey(\Config::get('stripe.apiKey'));

// Initializing Hatchbuck vendor
\Hatchbuck\Hatchbuck::setApiKey(
    \Config::get('hatchbuck.apiKey'),
    new \Hatchbuck\Auth\Auth(
        new \Hatchbuck\Auth\AuthPair(\Config::get('hatchbuck.cert.path')),
        new \Hatchbuck\Auth\AuthPair(\Config::get('hatchbuck.key.path'))
    )
);

// Initializing NatGeo API
\Pley\NatGeo\NatGeo::setApiKey(
    new \Pley\NatGeo\Auth\Auth(
        \Config::get('nat-geo.credentials.portalId'), 
        \Config::get('nat-geo.credentials.password')
    ), 
    \Config::get('nat-geo.userPrefix')
);

/*
  |--------------------------------------------------------------------------
  | Return The Application
  |--------------------------------------------------------------------------
  |
  | This script returns the application instance. The instance is given to
  | the calling script so we can separate the building of the instances
  | from the actual running of the application and sending responses.
  |
 */

return $app;
