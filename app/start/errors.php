<?php /** @copyright Pley (c) 2014, All Rights Reserved */

use \Illuminate\Http\Response as ResponseCode; // Required to get access to the HTTP Response codes

use \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * The <kbd>JsonErrorHandler</kbd> class allows to handle Exeptions thrown by our system and return
 * a JSON compatible response for them.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class JsonErrorHandler
{
    /**
     * Flag used to instruct this handler whether to return formatted exception or let it flow
     * through so that the default Laravel's handler displays the whole stack.
     * <p>By default it is set to <kbd>true</kbd>, disable it by setting it to false on your
     * <kbd>/app/start/local.php</kbd> file.
     * @var boolean
     */
    public static $isHandleOn = true;
    
    public static function getHandlerClosure()
    {
        return function(\Exception $exception) {
            return static::handle($exception);
        };
    }
    
    /**
     * Returns a <kbd>JsonResponse</kbd> object formatted for the supplied exception.
     * 
     * @param \Exception $exception The exception to match to an HTTP Status Code
     * @param int        $httpCode  [Optional]<br/>An HTTP Status Code to override
     * @param string     $message   [Optional]<br/>An optional message to add to the response.
     * @return \Illuminate\Http\JsonResponse
     */
    public static function handle(\Exception $exception, $httpCode = null, $message = null)
    {
        $arrayResponse = array();
        $arrayResponse['code'] = $exception->getCode();
        
        $httpResponseCode = static::_getHttpCode($exception, $httpCode);
        
        if (get_class($exception) == 'Exception') {
            $message = 'Unexpected Problem';
        }
        
        // Only when NOT in production, show the actual error message for easy debugging
        if (App::environment() != 'production') {
            $arrayResponse['exception'] = static::_getMessage($exception);
        }
        
        if (isset($message)) {
            $arrayResponse['msg'] = $message;
        }

        //push the exception to Sentry.io if service is set
        if(app('sentry') !== null){
            $ignoredExceptions = app('config')->get('sentry.ignored_exceptions');
            if (!in_array(get_class($exception), $ignoredExceptions)) {
                app('sentry')->captureException($exception);
            }
        }

        static::_logException($exception);
        
        if (static::$isHandleOn) {
            return Response::json($arrayResponse, $httpResponseCode);
        }
        
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
            
        } else if ($exception instanceof MethodNotAllowedHttpException) {
            $httpCode = $exception->getStatusCode();
            
        }
        
        return $httpCode;
    }
    
    /**
     * Returns the Exception message
     * <p>Some exceptions don't have messages but have extra metadata that can be used as a message,
     * so this method helps creating such message based on the exception type.</p>
     * 
     * @param \Exception $exception
     * @return string
     */
    protected static function _getMessage(\Exception $exception)
    {
        $message = $exception->getMessage();
        
        if ($exception instanceof MethodNotAllowedHttpException) {
            $message = 'Allowed: ' . implode(',', $exception->getHeaders());
            
        }
        
        return $message;
    }
    
    /**
     * Parses the Exception into a string and send it to the Error Logs.
     * @param \Exception $exception
     */
    protected static function _logException(\Exception $exception)
    {
        $clientIp = implode(' > ', \Request::getClientIps());
        
        // If the special interface for Separate Exception log matches, then pop all handlers and
        // inject the specific one.
        if ($exception instanceof \Pley\Http\Response\SeparateExceptionLogInterface) {
            \LogHelper::popAllHandlers();
            \Log::useDailyFiles(storage_path(). "/logs/{$exception->getLogName()}.log");
            \LogHelper::ignoreHandlersEmptyContextAndExtra();
        }
        
        if ($exception instanceof \Pley\Http\Response\OneLineExceptionInterface) {
            \Log::error("[{$clientIp}] " . $exception->getOneLineMessage());
            return;
        }
        
        $traceLinesLimit = 100;
        if (!$exception instanceof \Pley\Http\Response\ExceptionInterface) {
            $traceLinesLimit = 6;
        }

        // Getting the stack trace for the supplied exception
        $printableStackTrace = static::_parseException($exception, $traceLinesLimit);
        
        // If the supplied exception wrapped another exception, then print that one too.
        if ($exception->getPrevious() != null) {
            $prevPrintableStack = 'Previous ' . static::_parseException(
                $exception->getPrevious(), $traceLinesLimit, $printableStackTrace
            );
            $printableStackTrace .= $prevPrintableStack;
        }

        \Log::error("[{$clientIp}] " . $printableStackTrace);
    }
    
    /**
     * Takes an exception and parses it into a string that can be sent to the error logs.
     * <p>The parsing is as close as possible to how just letting the exception be logged natively
     * would look like, however we add some additional filters as we don't want to print the
     * Laravel framework's stack as that is part of each exception and doesn't add any value to
     * the log.</p>
     * <p>Doing this also allows us to print the outer most exception first, instead of last as it
     * would natively do, and allows us to filter till the first common trace line.</p>
     * 
     * @param \Exception $ex
     * @param int        $lineLimit    (Optional)<br/>If supplied, a limit of how many trace lines
     *      to allow in the parse to avoid clogging the Error log file.
     * @param string     $wrapperExStr (Optional)<br/>If supplied, means this exception was wrapped
     *      and thus we only want to parse up to the first common trace.
     * @return string
     */
    protected static function _parseException(\Exception $ex, $lineLimit = null, $wrapperExStr = null)
    {
        $messageTemplate = "exception '%s' with message '%s' in %s:%d";
        $className       = get_class($ex);
        $message         = sprintf($messageTemplate, $className, $ex->getMessage(), $ex->getFile(), $ex->getLine());

        $traceList       = $ex->getTrace();
        $parsedTraceList = [];
        foreach ($traceList as $index => $stackDataMap) {
            $strTrace = static::_parseExceptionTrace($stackDataMap);
            
            // We noticed that Laravel uses closures to invoke the Controllers, and since we don't
            // really care about the Laravel stack trace in terms of our Error Logs, we just stop
            // capturing when we get to them.
            if (strpos($strTrace, '[internal function]: api') !== false
                    || strpos($strTrace, '[internal function]: operations') !== false
                    || strpos($strTrace, '[internal function]: webhook') !== false) {
                break;
            }
            
            // Also, if a hard line limit is supplied, use it to limit the number of trace lines captured
            if (isset($lineLimit) && $index >= $lineLimit) {
                break;
            }
            
            $strTraceLine      = '#' . $index . ' ' . $strTrace;
            $parsedTraceList[] = $strTraceLine;
            
            // If a wrapper exception string is supplied, it means that the exception we are currently
            // parsing, is a previous exception, and as such, will have a common stack trace, so
            // we avoid printing the rest of it as it's included in the wrapper exception trace.
            if (strpos($wrapperExStr, $strTrace)) {
                break;
            }
        }

        $exString = $message . "\n"
                  . "Stack trace:\n"
                  . implode("\n", $parsedTraceList) . "\n";
        return $exString;
    }
    
    /**
     * Converts a Map representing an Exception Trace line, and prases it into a string that looks
     * like the native printing if the exception was let to cascade.
     * @param array $traceData
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected static function _parseExceptionTrace($traceData)
    {
        $stackTemplate = "%s: %s(%s)";
        
        $source = '[internal function]';
        if (isset($traceData['file'])) {
            $source = sprintf('%s(%s)', $traceData['file'], $traceData['line']);
        }
        
        $args = '';
        if (isset($traceData['args'])) {
            $argList = [];
            foreach ($traceData['args'] as $arg) {
                if (is_object($arg)){
                    $argList[] = sprintf('Object(%s)', get_class($arg));
                    continue;
                }
                if (is_null($arg)) {
                    $argList[] = 'NULL';
                    continue;
                }
                if (is_array($arg)) {
                    $argList[] = 'Array';
                    continue;
                }
                if (is_bool($arg)) {
                    $argList[] = $arg? 'true' : 'false';
                    continue;
                }
                if (is_string($arg)) {
                    $singleLine = str_replace("\n", '\n', $arg);
                    if (strlen($singleLine) > 20) {
                        $singleLine = substr($singleLine, 0, 17) . '...';
                    }
                    $argList[] = $singleLine;
                    continue;
                }
                
                $argList[] = $arg;
            }
            $args = implode(', ', $argList);
        }
        
        $function = $traceData['function'];
        if (isset($traceData['class'])) {
            $function = $traceData['class'] . $traceData['type'] . $traceData['function'];
        }
        
        $stackDetail = sprintf($stackTemplate, $source, $function, $args);
        
        return $stackDetail;
    }
}
