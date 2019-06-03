<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Laravel\Routing\Exception;

use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use \Pley\Http\Response\OneLineExceptionInterface;

/**
 * The <kbd>RouteNotFoundException</kbd> class extends the Symfony exception specifically for the
 * purpose of allowing us to track the Route URI used when a match was not found.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Laravel.Routing.Exception
 */
class RouteNotFoundException extends NotFoundHttpException implements OneLineExceptionInterface
{
    use \Pley\Http\Response\OneLineExceptionTrait;
    
    public function __construct($routeURI, \Exception $previous = null, $code = 0)
    {
        $message = 'URI: ' . $routeURI;
        parent::__construct($message, $previous, $code);
    }
}
