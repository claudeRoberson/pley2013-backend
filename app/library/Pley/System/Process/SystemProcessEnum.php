<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\System\Process;

/**
 * The <kbd>SystemProcessEnum</kbd> class declares constants to help readability when working with
 * process control operations such as `pcntl_fork` and others.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.System.Process
 * @subpackage System
 */
abstract class SystemProcessEnum
{
    /**
     * PID to identify that creating a Child process failed after calling <kbd>pcntl_fork()</kbd>
     * @var int
     */
    const FORK_PID_FAILED = -1;
    /**
     * PID to identify the Child process after calling <kbd>pcntl_fork()</kbd>
     * @var int
     */
    const FORK_PID_CHILD   = 0;
    
    /**
     * Wait for any child process.
     * @var int
     */
    const PID_WAIT_ANY_CHILD = -1;
    /**
     * Wait for any child process whose process group ID is equal to that of the calling process.
     * @var int
     */
    const PID_WAIT_GROUP_ID  = 0;
    
    /**
     * The default exit status for process calls that finished successfully
     * @var int
     */
    const EXIT_STATUS_SUCCESS = 0;
}
