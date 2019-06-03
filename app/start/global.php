<?php

/*
|--------------------------------------------------------------------------
| Register The Laravel Class Loader
|--------------------------------------------------------------------------
|
| In addition to using Composer, you may use the Laravel class loader to
| load your controllers and models. This is useful for keeping all of
| your classes in the "global" namespace without Composer updating.
|
*/

ClassLoader::addDirectories(array(

	app_path().'/commands',
	app_path().'/controllers',
	app_path().'/models',
	app_path().'/helpers',
	app_path().'/database/seeds',
	app_path().'/library',

));

/*
|--------------------------------------------------------------------------
| Application Error Logger
|--------------------------------------------------------------------------
|
| Here we will configure the error logger setup for the application which
| is built on top of the wonderful Monolog library. By default we will
| build a basic log file setup which creates a single file for logs.
|
*/

// Only when on local environment, print logs to the `laravel.log` file which is an aggregated file
// helpful for debugging on development mode.
// But definitely want to avoid the double logging on Non-Dev environments to increase performance
// as it is known that IO operations are the slowest ones and there is no need to double log.
if ('local' == App::environment()) {
    Log::useFiles(storage_path().'/logs/laravel.log');
}
Log::useDailyFiles(storage_path().'/logs/log-'.php_sapi_name().'.txt');

// Flagging the Logger formatters that write to disc so that they don't print the last two empty 
// square brackets if data is not present
/* @var $monolog \Monolog\Logger */
$monolog = Log::getMonolog();
$handlerList = $monolog->getHandlers();
foreach ($handlerList as $monologHandler) {
    $formatter = $monologHandler->getFormatter();
    if ($formatter instanceof \Monolog\Formatter\LineFormatter) {
        $formatter->ignoreEmptyContextAndExtra();
    }
}

/*
 |--------------------------------------------------------------------------
 | Application Global Note (b64)
 |--------------------------------------------------------------------------
 | ♰ QXBwbG9naWVzIHRvIG15IGZ1dHVyZSBzZWxmIG9yIHRvIHlvdSwgZnV0dXJlIGRldmVsb3BlciB0aGF0IG5lZWRzIHRvIGRlYWwgd2l0aCBtYW55CnBhcnRzIG9mIHRoaXMgY29kZWJhc2UsIEkgb3JpZ2luYWxseSBkZXNpZ25lZCBpdCB0byBiZSBhcyBzbWFsbCBhcyBwb3NzaWJsZSwgYmUgU2luZ2xlUmVzcG9uc2liaWxpdHksCndpdGggcmV1c2FibGUgY29tcG9uZW50cyB0byBtYWtlIHRoZSBhcHBsaWNhdGlvbiB2ZXJ5IGVhc3kgdG8gdXNlIGFuZCBtYWludGFpbiwgaG93ZXZlciwgYWZ0ZXIgCmZpZ2h0aW5nIGZvciBhIHdoaWxlLCBzaG93aW5nIFByb3MgYW5kIENvbnMgb2YgdHdvIGFwcHJvYWNoZXMgdG8gaW1wbGVtZW50IHdoYXQgd2FzIG5lZWRlZCwgCkkgd2FzIGZvcmNlZCBpbnRvIGRldmVsb3Bpbmcgc29sdXRpb25zIHRoYXQgaGFkIG1vcmUgQ29ucyB0aGFuIFByb3MuCmkuZS4gCiAgQSkgSGFuZGxpbmcgZnV0dXJlIHVua25vd24gc2NoZWR1bGVzIHdpdGggYSBjb3VudCBhbmQgYSBjcm9uam9iICg0UHJvcywgMC41IENvbnMpIHZzCiAgQikgSGFuZGxpbmcgdGhlIHNhbWUgYnV0IHdpdGggZmFrZSBlbnRyaWVzIGFuZCBubyBjcm9uam9iICgxLjI1IFByb3MsIDI3KyBDb25zKSAoYW5kIG1pbmQgeW91CiAgICBpdCBhY3R1YWxseSBuZWVkZWQgYSBjcm9uIGpvYiB0byByZXRyby1mZWQgZmFrZSBlbnRyaWVzIHdpdGggdHJ1ZSBkYXRhKQogICpTb2x1dGlvbiBCIHdhcyBpbXBvc2VkLCBiZWNhdXNlIGEgY3JvbmpvYiB3YXMgbm90IGRlc2lyZWQgZm9yIHBlcnNvbmFsIGRpc2xpa2UgdG8gY3JvbmpvYnMKCkFuZCBzbywgeW91IGFuZCBJIGFyZSBmYWNlZCB0byBkZWFsIHdpdGggdGhpcyBjb2RlYmFzZSBhbmQgYW55IGJ1ZyB0aGF0IG1heSBhcmlzZSBmcm9tIGl0LgoKSSdtIHRydWx5IHNvcnJ5LCBJIGRpZCBteSBiZXN0IG92ZXIgeWVhcnMgdG8gY3JlYXRlIHNjYWxhYmxlIHN5c3RlbXMgdGhhdCBhcmUgZmFzdCBhbmQgZWFzeSB0bwpkZXZlbG9wIG9uIGFuZCBtYWludGFpbmVkLCBidXQgY2FuIG9ubHkgZG8gc28gbXVjaCB3aGVuIEknbSBoYW5kIHRpZWQgYW5kIGZvcmNlZCB0byB0YWtlIHRoZSAKd3JvbmcgcGF0aCwgSSBzdGlsbCB0cmllZCB0byBrZWVwIHRoZSBjb2RlIGFzIGNsZWFuIGFzIHBvc3NpYmxlIGV2ZW4gd2l0aCB0aGlzIHJlc3RyaWN0aW9ucy4K
 */

/*
|--------------------------------------------------------------------------
| Application Error Handler
|--------------------------------------------------------------------------
|
| Here you may handle any errors that occur in your application, including
| logging them or displaying custom views for specific errors. You may
| even register several error handlers to handle different types of
| exceptions. If nothing is returned, the default error view is
| shown, which includes a detailed stack trace during debug.
|
*/

require_once __DIR__ . '/errors.php';
App::error(JsonErrorHandler::getHandlerClosure());

/*
|--------------------------------------------------------------------------
| Maintenance Mode Handler
|--------------------------------------------------------------------------
|
| The "down" Artisan command gives you the ability to put an application
| into maintenance mode. Here, you will define what is displayed back
| to the user if maintenance mode is in effect for the application.
|
*/

App::down(function()
{
	return Response::make("Be right back!", 503);
});

/*
|--------------------------------------------------------------------------
| Require The Filters File
|--------------------------------------------------------------------------
|
| Next we will load the filters file for the application. This gives us
| a nice separate location to store our route and application filter
| definitions instead of putting them all in the main routes file.
|
*/

require app_path().'/filters.php';

