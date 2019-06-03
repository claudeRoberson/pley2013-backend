<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Laravel\Foundation\Session;

use \Illuminate\Session\SessionInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpKernel\HttpKernelInterface;

use \Pley\Http\Session\Session;

/**
 * The <kbd>Middleware</kbd> class extends the Laravel's Foundation Session Middleware so that
 * we can stop creating sessions for every request if no session is passed by the client and avoid
 * using cookies as mechanisim for session communication (since some clients may not be web clients
 * and thus, not support cookies).
 * <p>It also allows us to save sessions only if they have been flagged as not fresh (Should only be
 * done by User Login or User Registration).</p>
 * <p>Here are the flow scenarios<br/>:
 * <table border="1">
 *   <tr>
 *     <th>Session passed?</th>
 *     <th>Valid Session? (if passed)</th>
 *     <th>Auth/Register Controller</th>
 *     <th>session saved?</th>
 *   </tr>
 *   <tr><td>NO</td><td>NO</td><td>NO</td><td>NO</td><tr/>
 *   <tr><td>NO</td><td>NO</td><td>YES</td><td>YES</td><tr/>
 *   <tr><td>YES</td><td>NO</td><td>NO</td><td>NO</td><tr/>
 *   <tr><td>YES</td><td>NO</td><td>YES</td><td>YES(new session)</td><tr/>
 *   <tr><td>YES</td><td>YES</td><td>NO</td><td>YES</td><tr/>
 *   <tr><td>YES</td><td>YES</td><td>YES</td><td>YES</td><tr/>
 * </table></p>
 * 
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Laravel.Foundation.Session
 */
class Middleware extends \Illuminate\Session\Middleware
{
    /**
     * Internal variable used to know if we created a new session or we are reusing one passed
     * through the headers
     * @var boolean
     */
    protected $_isNewSession = false;
    
    /**
     * Handle the given request and get the response.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int                                       $type    [Optional]
     * @param boolean                                   $catch   [Optional]
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $this->checkRequestForArraySessions($request);

        if ($this->sessionConfigured()) {
            /* @var $session \Illuminate\Session\Store */
            $session = $this->startSession($request);

            // Now that the session has been started, we check the internal flag to see if it is a
            // new session.
            // If so, we set a session attribute with the same information so that when we call 
            // `$this->closeSession()` we know we don't need to save the session 
            // The attribute should only be updated by either the User Login or User Registration
            // and thus allowing the session to be saved there on.
            // 
            // Note: this has to be done after session is started, for starting a session overrides
            // all session attributes.
            if ($this->_isNewSession) {
                $session->set(Session::IS_FRESH_KEY, true);
            }
            
            $request->setSession($session);
        }

        $response = $this->app->handle($request, $type, $catch);

        // Again, if the session has been configured we will need to close out the session
        // so that the attributes may be persisted to some storage medium. We will also
        // add the session identifier cookie to the application response headers now.
        if ($this->sessionConfigured()) {
            $this->closeSession($session);
        }

        return $response;
    }

    /**
     * Get the session implementation from the manager.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Illuminate\Session\SessionInterface
     */
    public function getSession(Request $request)
    {
        $SITE_SESSION_KEY         = 'tbses';
        $BACKEND_SITE_SESSION_KEY = 'tbases';

        /* @var $session \Illuminate\Session\Store */
        $session = $this->manager->driver();

        // Trying to get the session from our known headers.
        $sessionId = null;
        if ($request->headers->has($SITE_SESSION_KEY)) {
            $sessionId = $request->headers->get($SITE_SESSION_KEY);
            
        } else if ($request->headers->has($BACKEND_SITE_SESSION_KEY)) {
            $sessionId = $request->headers->get($BACKEND_SITE_SESSION_KEY);
        }
        
        // If we found the session id, then decrypt it since the value is passed encrypted
        if (!empty($sessionId)) {
            $sessionId = \Crypt::decrypt($sessionId);
        }
        
        $session->setId($sessionId);

        // Since we don't want to be writing, we create a flag to mark this session as fresh, so
        // we can add a session variable with the same information after the session has been started
        if (empty($sessionId)) {
            $this->_isNewSession = true;
        }
        
        return $session;
    }

    /**
     * Close the session handling for the request.
     *
     * @param  \Illuminate\Session\SessionInterface  $session
     * @return void
     */
    protected function closeSession(SessionInterface $session)
    {
        // To avoid writing sessions for every request, we check if the session is flagged as
        // fresh, if so, the we DO NOT save.
        // Only upon User Login or User Registration will a new session be flagged as not fresh
        $isFresh = $session->get(Session::IS_FRESH_KEY);
        if (!$isFresh) {
            $session->save();
        }

        $this->collectGarbage($session);
    }

}
