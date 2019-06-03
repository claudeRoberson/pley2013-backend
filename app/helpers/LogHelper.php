<?php /** @copyright Pley (c) 2015, All Rights Reserved */

/**
 * The <kbd>LogHelper</kbd> Provides with basic methods to empty the Logger instance and thus allow
 * to log to a specific log without affecting the default ones and then reset the instance with the
 * base handlers.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class LogHelper
{
    /**
     * Pops all Handlers from the Logger instance and returns a reference to all of them so they 
     * can be reset back.
     * @return \Monolog\Handler\HandlerInterface[]
     * @see ::resetHandlers($resetHandlerList)
     */
    public static function popAllHandlers()
    {
        /* @var $monolog \Monolog\Logger */
        $monolog = \Log::getMonolog();
        
        $handlerList = $monolog->getHandlers();
        
        for ($i = 0; $i < count($handlerList); $i++) {
            $monolog->popHandler();
        }
        
        return $handlerList;
    }
    
    /**
     * Resets the Logger instance with the supplied list of handlers.
     * The supplied list is most probably a result of poping initial handlers to be able to write
     * to a specific log and then reset the default handlers back.
     * @param \Monolog\Handler\HandlerInterface[] $resetHandlerList
     * @see ::popAllHandlers()
     */
    public static function resetHandlers($resetHandlerList)
    {
        /* @var $monolog \Monolog\Logger */
        $monolog = \Log::getMonolog();
        
        $handlerList = $monolog->getHandlers();
        
        for ($i = 0; $i < count($handlerList); $i++) {
            $monolog->popHandler();
        }
        
        foreach ($resetHandlerList as $handler) {
            $monolog->pushHandler($handler);
        }
    }
    
    /**
     * Sets to ingore the Empty Context and Extra nodes from all the current handlers formatters.
     */
    public static function ignoreHandlersEmptyContextAndExtra()
    {
        /* @var $monolog \Monolog\Logger */
        $monolog = \Log::getMonolog();
        
        $handlerList = $monolog->getHandlers();
        foreach ($handlerList as $monologHandler) {
            $formatter = $monologHandler->getFormatter();
            if ($formatter instanceof \Monolog\Formatter\LineFormatter) {
                $formatter->ignoreEmptyContextAndExtra();
            }
        }
    }
}
