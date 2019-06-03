<?php // Pley (c) 2014, All Rights Reserved

use \Illuminate\Http\Response as ResponseCode; // Required to get access to the HTTP Response codes


/**
 * The <kbd>ResponseHelper</kbd> provides methods to simplify handling common responses between
 * controllers.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class ResponseHelper
{
    /**
     * Returns a <kbd>JsonResponse</kbd> object formatted for the supplied exception.
     * 
     * @param \Exception $exception The exception to match to an HTTP Status Code
     * @param int        $httpCode  [Optional]<br/>An HTTP Status Code to override
     * @param string     $message   [Optional]<br/>An optional message to add to the response.
     * @return \Illuminate\Http\JsonResponse
     */
    public static function exceptionJson(\Exception $exception, $httpCode = null, $message = null)
    {
        $arrayResponse = array();
        $arrayResponse['code'] = $exception->getCode();
        
        $httpResponseCode = static::_getHttpCode($exception, $httpCode);
        
        if (get_class($exception) == 'Exception') {
            // If exception is not identified, then log it
            Log::error($exception);
            $message = 'Unexpected Problem';
        }
        
        
        // Only when NOT in production, show the actual error message for easy debugging
        if (App::environment() != 'production') {
            $arrayResponse['exception'] = $exception->getMessage();
        }
        
        if (isset($message)) {
            $arrayResponse['msg'] = $message;
        }
        
        return Response::json($arrayResponse, $httpResponseCode);
    }
    
    /**
     * Helper function that will set the Session Header to the response object.
     * <p>Returns the updated response object so that it can be used after the session has been set.
     * </p>
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param string                                     $headerName [Optional]<br/>Default 'pses'
     * @return \Symfony\Component\HttpFoundation\Response Returns the updated response object
     */
    public static function setSessionHeader($response, $headerName = 'tbses')
    {
        // Getting the current session ID
        $sessionId    = Session::getId();
        
        // This encrypted session id, will be decripted for any other call by means of the
        // `app/filters.php` "auth.session" pre-filter
        $encSessionId = Crypt::encrypt($sessionId);
        
        // Adding the session to the header
        $response->headers->set($headerName, $encSessionId);
        
        // For the time being, because we are having issues with cross-domain and headers, we are
        // returning the session as part of the JSON Reponse body as well
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $dataMap = $response->getData(true);
            $dataMap[$headerName] = $encSessionId;
            
            $response->setData($dataMap);
        }
        
        return $response;
    }
    
    /**
     * Returns the HTTP Status Code for the supplied exception.
     * 
     * @param \Exception $exception        The exception to check what HTTP Status Code to use.
     * @param int        $httpCodeOverride [Optional]<br/>HTTP Status Code to override
     * @return int
     */
    protected static function _getHttpCode(\Exception $exception, $httpCodeOverride = null)
    {
        if (is_int($httpCodeOverride)) {
            return $httpCodeOverride;
        }
        
        $httpCode = ResponseCode::HTTP_INTERNAL_SERVER_ERROR;
        
        if ($exception instanceof \Pley\Http\Response\ExceptionInterface) {
            $httpCode = $exception->getHttpCode();
            
        } else if ($exception instanceof ModelNotFoundException) {
            $httpCode = ResponseCode::HTTP_BAD_REQUEST;
        }
        
        return $httpCode;
    }
}
