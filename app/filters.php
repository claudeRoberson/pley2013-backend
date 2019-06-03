<?php

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

App::before(function($request)
{
    // Since our backend handles two systems (website and warehouse), we have to replace
    // the base authentication model at execution time so the warehouse APIs can use the
    // respective User model.
    if (strpos($request->path(), 'operations') === 0) {
        Config::set('auth.model', 'OperationsUser');
    }
    
    // Set the current IP (REMOTE_ADDR) as a trusted proxy
    Request::setTrustedProxies([$request->getClientIp()]);
});


App::after(function($request, $response)
{
    //
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/

Route::filter('auth', function()
{
    if (Auth::guest())
    {
        if (Request::ajax())
        {
            return Response::make('Unauthorized', 401);
        }
        else
        {
            return Redirect::guest('login');
        }
    }
});


Route::filter('auth.attempt', function() {
    $sessionId = Request::header('sessionId');
    $userId = Request::header('userId');

    $user = User::where('id','=',intval($userId))->where('remember_token','=',$sessionId, 'AND')->get();
    if (sizeof($user) != 1) {
        return Response::json(array('call_name' => 'auth.basic', 'status' => 'error', 'message' => 'cannot login'));
    }
    if ($user[0]->remember_token !== $sessionId) {
        return Response::json(array('call_name' => 'auth.basic', 'status' => 'error', 'message' => 'cannot login'));
    }
    return $user[0]->remember_token;

});


/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('guest', function()
{
    if (Auth::check()) return Redirect::to('/');
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/

Route::filter('csrf', function()
{
    if (Session::token() != Input::get('_token'))
    {
        throw new Illuminate\Session\TokenMismatchException;
    }
});
