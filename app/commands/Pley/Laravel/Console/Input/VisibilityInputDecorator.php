<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Laravel\Console\Input;

use Symfony\Component\Console\Input\Input;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputInterface;

/**
 * The <kbd>VisibilityInputDecorator</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Laravel.Console.Input
 * @subpackage Console
 * @subpackage Input
 */
class VisibilityInputDecorator extends Input implements InputInterface
{
    /** @var \Symfony\Component\Console\Input\Input */
    protected $_input;
    
    /**
     * Copy Constructor to decorate the supplied <kbd>Input</kdb> object.
     * @param \Symfony\Component\Console\Input\Input $input
     */
    public function __construct(Input $input)
    {
        $this->_input = $input;
    }
    
    /**
     * Processes command line arguments.
     */
    protected function parse()
    {
        $this->_input->parse();
    }

    /**
     * Returns the first argument from the raw parameters (not parsed).
     *
     * @return string The value of the first argument or null otherwise
     */
    public function getFirstArgument()
    {
        return $this->_input->getFirstArgument();
    }

    /**
     * Returns the value of a raw option (not parsed).
     *
     * This method is to be used to introspect the input parameters
     * before they have been validated. It must be used carefully.
     *
     * @param string|array $values  The value(s) to look for in the raw parameters (can be an array)
     * @param mixed        $default The default value to return if no result is found
     *
     * @return mixed The option value
     */
    public function getParameterOption($values, $default = false)
    {
        return $this->_input->getParameterOption($values, $default);
    }

    /**
     * Returns true if the raw parameters (not parsed) contain a value.
     *
     * This method is to be used to introspect the input parameters
     * before they have been validated. It must be used carefully.
     *
     * @param string|array $values The values to look for in the raw parameters (can be an array)
     *
     * @return bool true if the value is contained in the raw parameters
     */
    public function hasParameterOption($values)
    {
        return $this->_input->hasParameterOption($values);
    }

    /**
     * Binds the current Input instance with the given arguments and options.
     *
     * @param InputDefinition $definition A InputDefinition instance
     */
    public function bind(InputDefinition $definition)
    {
        $this->_input->bind($definition);
    }

    /**
     * Escapes a token through escapeshellarg if it contains unsafe chars.
     *
     * @param string $token
     *
     * @return string
     */
    public function escapeToken($token)
    {
        return $this->_input->escapeToken($token);
    }

    /**
     * Returns the argument value for a given argument name.
     *
     * @param string $name The argument name
     *
     * @return mixed The argument value
     *
     * @throws \InvalidArgumentException When argument given doesn't exist
     */
    public function getArgument($name)
    {
        return $this->_input->getArgument($name);
    }

    /**
     * Returns the argument values.
     *
     * @return array An array of argument values
     */
    public function getArguments()
    {
        return $this->_input->getArguments();
    }

    /**
     * Returns the option value for a given option name.
     *
     * @param string $name The option name
     *
     * @return mixed The option value
     *
     * @throws \InvalidArgumentException When option given doesn't exist
     */
    public function getOption($name)
    {
        return $this->_input->getOption($name);
    }

    /**
     * Returns the options values.
     *
     * @return array An array of option values
     */
    public function getOptions()
    {
        return $this->_input->getOptions();
    }

    /**
     * Returns true if an InputArgument object exists by name or position.
     *
     * @param string|int $name The InputArgument name or position
     *
     * @return bool true if the InputArgument object exists, false otherwise
     */
    public function hasArgument($name)
    {
        return $this->_input->hasArgument($name);
    }

    /**
     * Returns true if an InputOption object exists by name.
     *
     * @param string $name The InputOption name
     *
     * @return bool true if the InputOption object exists, false otherwise
     */
    public function hasOption($name)
    {
        return $this->_input->hasOption($name);
    }
    
    /**
     * Returns whether the Argument was supplied by the user or not.
     * @param string $name The Argument name
     * @return boolean
     */
    public function isUserArgument($name)
    {
        return array_key_exists($name, $this->_input->arguments);
    }
    
    /**
     * Returns whether the Option was supplied by the user or not.
     * @param string $name The option name
     * @return boolean
     */
    public function isUserOption($name)
    {
        return array_key_exists($name, $this->_input->options);
    }

    /**
     * Checks if the input is interactive.
     *
     * @return bool Returns true if the input is interactive
     */
    public function isInteractive()
    {
        $this->_input->isInteractive();
    }

    /**
     * Sets an argument value by name.
     *
     * @param string $name  The argument name
     * @param string $value The argument value
     *
     * @throws \InvalidArgumentException When argument given doesn't exist
     */
    public function setArgument($name, $value)
    {
        $this->_input->setArgument($name, $value);
    }

    /**
     * Sets the input interactivity.
     *
     * @param bool $interactive If the input should be interactive
     */
    public function setInteractive($interactive)
    {
        $this->_input->setInteractive($interactive);
    }

    /**
     * Sets an option value by name.
     *
     * @param string      $name  The option name
     * @param string|bool $value The option value
     *
     * @throws \InvalidArgumentException When option given doesn't exist
     */
    public function setOption($name, $value)
    {
        $this->_input->setOption($name, $value);
    }

    /**
     * Validates the input.
     *
     * @throws \RuntimeException When not enough arguments are given
     */
    public function validate()
    {
        $this->_input->validate();
    }

}
