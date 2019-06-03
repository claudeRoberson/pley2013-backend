<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Laravel\Validation;

/**
 * The <kbd>Validator</kbd> class extends the Laravel Validator class to add more custom checks.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Laravel.Validation
 * @subpackage Validation
 */
class Validator extends \Illuminate\Validation\Validator
{
    /**
     * Validate that an attribute contains only alphabetic characters and spaces.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAlphaSpace($attribute, $value)
    {
        return preg_match('/^[\pL\pM ]+$/u', $value);
    }
    
    /**
     * Validate that an attribute contains only alpha dash characters and spaces.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAlphaSpaceDash($attribute, $value)
    {
        return preg_match('/^[\pL\pM _-]+$/u', $value);
    }

    /**
     * Validate that an attribute contains only alphabetic characters, spaces and the dot character.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAlphaDotSpace($attribute, $value)
    {
        return preg_match('/^[\pL\pM\pN .]+$/u', $value);
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters and spaces.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAlphaNumSpace($attribute, $value)
    {
        return preg_match('/^[\pL\pM\pN ]+$/u', $value);
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters, dashes, underscores
     * and spaces.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAlphaDashSpace($attribute, $value)
    {
        return preg_match('/^[\pL\pM\pN _-]+$/u', $value);
    }

    /**
     * Validate that an attribute contains only alpha-numeric characters, dots, dashes, underscores
     * and spaces.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validateAlphaDashDotSpace($attribute, $value)
    {
        return preg_match('/^[\pL\pM\pN .,_-]+$/u', $value);
    }

    /**
     * Validate that an attribute is a positive integer.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @return bool
     */
    protected function validatePositiveInteger($attribute, $value)
    {
        $isInteger = filter_var($value, FILTER_VALIDATE_INT) !== false;
        
        if (!$isInteger) {
            return false;
        }
        
        return $value >= 0;
    }

}
