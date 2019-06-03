<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Util;

/**
 * The <kbd>Math</kbd> class is a generic class that holds static methods for math operations that
 * can be reused across the application.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Util
 * @subpackage Util
 */
abstract class Math
{
    /**
     * Returns the factorial value of the supplied number.
     * @param int $number
     * @return int
     * @throws \InvalidArgumentException if the supplied argument is not a positive INTERGER
     */
    public static function factorial($number)
    {
        if (!filter_var($number, FILTER_VALIDATE_INT) || $number < 0) {
            throw new \InvalidArgumentException("Argument must be natural number, '{$number}' given.");
        }
        
        if ($number <= 1) {
            return 1;
        }
        
        $factorial = 1;
        for ($i = $number; $i >= 2; $i--) {
            $factorial *= $i;
        }
    }
    
    /**
     * Returns the factorial of a number as a list of the factorial results from 0 to number where
     * the key of the array represents the number which the factorial operation will be performed on,
     * and the value the result of the factorial operation.
     * <p>Sometimes the factorial operation is required within a cycle where each step requires the
     * factorial value of the following sequence, so calling the factorial function wastes operations
     * on values that have already been calculated, so this just stores all those values.</p>
     * <p>The resulting list looks like the following for the factorial of 3<br/>
     * <pre>array(
     * &nbsp;   0 => 1,
     * &nbsp;   1 => 1,
     * &nbsp;   2 => 2,
     * &nbsp;   3 => 6
     * )</pre></p>
     * @param int $number
     * @return int[]
     * @throws \InvalidArgumentException if the supplied argument is not a positive INTERGER
     */
    public static function factorialList($number)
    {
        if (filter_var($number, FILTER_VALIDATE_INT) === false || $number < 0) {
            throw new \InvalidArgumentException("Argument must be natural number, '{$number}' given.");
        }
        
        if ($number == 0) {
            return [1];
        }
        
        if ($number == 1) {
            return [1, 1];
        }
        
        $factorialTotal = 1;
        $factorialList  = [1, 1]; // index 0 and 1 filled by default
        for ($i = 2; $i <= $number; $i++) {
            $factorialTotal *= $i;
            $factorialList[] = $factorialTotal;
        }
        
        return $factorialList;
    }
    
    /**
     * Performs the summatory from <kbd>$startValue</kbd> to <kbd>$endValue</kbd> on the supplied
     * function. (Σ start->end fn(i))
     * <p>As a standard summatory, each step is increased by 1 from <kbd>$startValue</kbd> until
     * reaching <kbd>$endValue</kbd>, however, an optional parameter can be passed to change the
     * increment value.</p>
     * @param \Closure $closure    The function that the Summatory will operate on, function must expect
     *      only 1 argument which is the step value in the summatory.
     * @param int      $startValue
     * @param int      $endValue
     * @param int      $stepValue  (Optional)<br/>Default: 1</br>The value used for the increments
     *      on each step of the summatory 
     * @return mixed
     * @throws \InvalidArgumentException If an argument is not Integer or has a bad value.
     */
    public static function summatory(\Closure $closure, $startValue, $endValue, $stepValue = 1)
    {
        if (filter_var($startValue, FILTER_VALIDATE_INT) === false || $startValue < 0) {
            throw new \InvalidArgumentException(
                "Argument `startValue` must be natural number, '{$startValue}' given."
            );
        }
        
        if (filter_var($endValue, FILTER_VALIDATE_INT) === false || $endValue <= $startValue) {
            throw new \InvalidArgumentException(
                "Argument `endValue` must be natural number and greater than `startValue`, '{$endValue}' given."
            );
        }
        
        if (filter_var($stepValue, FILTER_VALIDATE_INT) === false || $stepValue < 1) {
            throw new \InvalidArgumentException("Argument `stepValue` must be natural number >= 1, '{$stepValue}' given.");
        }
        
        $sum = 0;
        for ($i = $startValue; $i <= $endValue; $i += $stepValue) {
            $sum += $closure($i);
        }
        
        return $sum;
    }
}
