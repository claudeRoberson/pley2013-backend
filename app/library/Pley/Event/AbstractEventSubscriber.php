<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Event;

/**
 * The <kbd>AbstractEventSubscriber</kbd> class defines common behaviors for Event Subscribers.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Event
 * @subpackage Event
 */
abstract class AbstractEventSubscriber
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher $eventDispatcher
     * @return array
     */
    public function subscribe($eventDispatcher)
    {
        $eventToMethodData = $this->_getEventToMethodData();
        
        foreach ($eventToMethodData as $handlerDefinition) {
            list ($eventId, $handlerMethodName) = $handlerDefinition;
            
            $this->_register($eventDispatcher, $eventId, $handlerMethodName);
        }
    }
    
    /**
     * Return a List of event Definitions where each definition is a list that contains the
     * event identifier and the name of the method that will handle it.
     * @return array
     */
    protected abstract function _getEventToMethodData();
    
    /**
     * Helper method to register an event listener with a method of this class
     * @param  \Illuminate\Events\Dispatcher $eventDispatcher
     * @param string                         $eventDesc
     * @param string                         $handlerMethod
     */
    private function _register($eventDispatcher, $eventDesc, $handlerMethod)
    {
        $handlerPath = get_class($this) . '@' . $handlerMethod;
        $eventDispatcher->listen($eventDesc, $handlerPath);
    }
}
