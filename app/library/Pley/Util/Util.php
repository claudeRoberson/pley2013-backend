<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Util;

/**
 * The <kbd>Util</kbd> class is a generic class that holds static methods that cannot be grouped
 * into a separate class for common functionality.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Util
 * @subpackage Util
 */
abstract class Util
{
    /**
     * Regular Expresion to find a string that matches an `email` by the most standard structures
     * @var string
     */
    const REGEX_EMAIL = '#[\w.%+-]+@[\w.-]+\.[A-Za-z]{2,6}#';
    
    /**
     * Swaps the values of the variables supplied
     * @param mixed $a
     * @param mixed $b
     */
    public static function swap(&$a, &$b)
    {
        $swapTmp = $a;
        $a = $b;
        $b = $swapTmp;
    }
    
    /**
     * Returns the trimmed value of the supplied string or NULL if the trimmed value yields an empty
     * string.
     * @param string $str
     * @return string|null
     */
    public static function nullTrim($str)
    {
        if (is_null($str) || !is_string($str)) {
            return null;
        }
        
        $trimmedStr = trim($str);
        if (empty($trimmedStr)) {
            $trimmedStr = null;
        }
        
        return $trimmedStr;
    }
    
    /**
     * Returns the Email within the contact info variable if found.
     * 
     * @param String $contactInfo
     * @return string|null The email contained on the contact info variable, or <kbd>NULL</kbd> if
     *      no email was found.
     */
    public static function getEmail($contactInfo)
    {
        $isEmailMatch = preg_match(self::REGEX_EMAIL, $contactInfo, $matches);
        if (!$isEmailMatch) {
            return null;
        }
        
        return $matches[0];
    }
}
