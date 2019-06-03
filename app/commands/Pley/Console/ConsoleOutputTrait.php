<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Console;

/**
 * The <kbd>ConsoleOutputTrait</kbd> trait provides with the common overrides the parent Command's
 * output methods to add a common prefix string for the command and be able to track logs
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Console
 */
trait ConsoleOutputTrait
{
    /** @var int Variable used to identify this run */
    private $_runId;
    /** @var boolean Indicates whether output should also be sent to logs */
    private $_logOutput = false;
    /** @var boolean Indicates that the log file has been set */
    private $_islogFileSet = false;
    
    /**
     * Sets whether output strings should be logged
     * @param boolean $status
     */
    protected function _setLogOutput($status)
    {
        $this->_logOutput = (boolean)$status;
        
        // If the output is set to TRUE and the Log File has not yet been set, then initialize it
        if ($this->_logOutput && !$this->_islogFileSet) {
            $className = (new \ReflectionClass(static::class))->getShortName();
            \LogHelper::popAllHandlers();
            \Log::useDailyFiles(storage_path(). "/logs/{$className}.log");
            
            $this->_islogFileSet = true;
        }
    }
    
    /**
     * Write a string as standard output.
     *
     * @param string  $string  The message to print
     */
    public function line($string)
    {
        parent::line($this->_getOutputPrefix() . $string);
        $this->_log($string);
    }
    
    /**
     * Write a string as information output.
     *
     * @param string  $string  The message to print
     */
    public function info($string)
    {
        parent::info($this->_getOutputPrefix() . $string);
        $this->_log($string);
    }
    
    /**
     * Write a string as error output.
     *
     * @param string  $string  The message to print
     */
    public function error($string)
    {
        parent::error($this->_getOutputPrefix() . $string);
        $this->_log($string, true);
    }
    
    /**
     * Write a string as question output.
     *
     * @param string  $string  The message to print
     */
    public function question($string)
    {
        parent::question($this->_getOutputPrefix() . $string);
        $this->_log($string);
    }
    
    /**
     * Write a string as comment output.
     *
     * @param string  $string  The message to print
     */
    public function comment($string)
    {
        parent::comment($this->_getOutputPrefix() . $string);
        $this->_log($string);
    }
    
    protected function _parseExceptionString(\Exception $e)
    {
        $exceptionString = (string)$e;
        $exceptionLines = explode("\n", $exceptionString);
        
        $newTrace = [];
        foreach ($exceptionLines as $line) {
            $newTrace[] = $line;
            if (strpos($line, '/Illuminate/Console/Command.php') !== false ) {
                break;
            }
        }
        
        return implode("\n", $newTrace);
    }
    
    /**
     * Helper method to log output if the flag was set to allow the output logging.
     * @param string  $string
     * @param boolean $isError [Optional]<br/>Default <kbd>false</kbd>
     */
    private function _log($string, $isError = false)
    {
        if ($this->_logOutput) {
            if ($isError) {
                \Log::error($this->_getOutputPrefix(true) . $string);
            } else {
                \Log::info($this->_getOutputPrefix(true) . $string);
            }
        }
    }
    
    /**
     * Helper method to create the Output Prefix for this Console command
     * @return string
     */
    private function _getOutputPrefix($forLog = false)
    {
        $prefix = "[DAEMON][$this->name][{$this->_getRunId()}] ";
        
        return $forLog? $prefix : ('[' . date('Y-m-d H:i:s') . ']' . $prefix);
    }
    
    /**
     * Helper method to retrieve a unique value for this run instance
     * @return int
     */
    private function _getRunId()
    {
        if (!isset($this->_runId)) {
            $this->_runId = time();
        }
        
        return $this->_runId;
    }
}
