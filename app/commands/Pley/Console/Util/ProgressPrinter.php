<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Console\Util;

/**
 * The <kbd>ProgressPrinter</kbd> Is a helper library to allow printing dots onto the command line
 * as a way of showing progress.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Console.Util
 * @subpackage Console
 * @subpackage Util
 */
class ProgressPrinter
{
    /**
     * Variable used to know when to print a new line 
     * @var int DEFAULT 120
     */
    private $_countBreak = 120;
    /**
     * Variable used to track which iteration are we on to print know whether to print a new line
     * before printing a progress dot
     * @var int
     */
    private $_iteration  = 0;
    
    /**
     * Change the default count break to the supplied value
     * @param int $countBreak
     */
    public function setCountBreak($countBreak)
    {
        $this->_countBreak = $countBreak;
    }

    /**
     * Prints a dot, and a new line if needed based on the Count Break
     */
    public function step()
    {
        if ($this->_iteration != 0 && $this->_iteration % $this->_countBreak == 0) {
            echo "\n";
        }
        echo '.';
        $this->_iteration++;
    }
    
    /**
     * Prints a new line to indicate the progress has finished.
     * <p>Also resets the internal iteration counter, so if the <kbd>step()</kbd> method is called
     * again, it will start as new.</p>
     */
    public function finish()
    {
        if ($this->_iteration != 0) {
            echo "\n";
            $this->_iteration = 0;
        }
    }
}
