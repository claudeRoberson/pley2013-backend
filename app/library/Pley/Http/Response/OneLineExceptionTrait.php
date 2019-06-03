<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Http\Response;

/**
 * The <kbd>OneLineExceptionTrait</kbd> provides the implementation for the <kbd>OneLineExceptionInterface</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Http.Response
 * @subpackage Exception
 */
trait OneLineExceptionTrait
{
    /**
     * Return the one line message from the exception including where it happened and the exception
     * associated to it.
     * @return string
     */
    public function getOneLineMessage()
    {
        return sprintf('%s: \'%s\' in %s:%d',
            get_class($this),    // Class Name
            $this->getMessage(),
            $this->getFile(),
            $this->getLine()
        );
    }
}
