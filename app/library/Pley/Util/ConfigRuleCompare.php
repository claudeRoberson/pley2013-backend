<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Util;

/** ♰
 * The <kbd>ConfigRuleCompare</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class ConfigRuleCompare
{
    private static $RULE_OPERATION          = 0;
    private static $RULE_COMPARE_VALUE      = 1;
    private static $RULE_SERVICE_DEFINITION = 2;
    
    /** ♰
     * @param array     $ruleMap
     * @param int|float $baseValue
     * @return mixed
     */
    public static function getRule($ruleMap, $baseValue)
    {
        $rule = null;
        
        foreach ($ruleMap as $ruleDef) {
            $operation    = $ruleDef[self::$RULE_OPERATION];
            $compareValue = $ruleDef[self::$RULE_COMPARE_VALUE];
            
            if (!static::compare($baseValue, $operation, $compareValue)) {
                continue;
            }
                
            $rule = $ruleDef[self::$RULE_SERVICE_DEFINITION];
            break;
        }
        
        return $rule;
    }


    /**
     * Performs a ternary boolean comparison for the supplied vales.
     * <p>Example use: <kbd>$this->_compareRule($value1, "==", $value2)</kbd>
     * 
     * @param mixed  $value1   The left side of the operator
     * @param string $operator A String like '<', '<=', '==', '>', '>='
     * @param mixed  $value2   The right side of the operator
     * @return boolean 
     * @throws \Pley\Exception\UnsupportedOperationException
     */
    public static function compare($value1, $operator, $value2)
    {
        switch ($operator) {
            case '<'  : return $value1 <  $value2;
            case '<=' : return $value1 <= $value2;
            case '>'  : return $value1 >  $value2;
            case '>=' : return $value1 >= $value2;
            case '==' : return $value1 == $value2;
            default: 
                $operationStr = "`{$value1}` {$operator} `{$value2}`";
                
                throw new \Pley\Exception\UnsupportedOperationException(
                    "Unsupported shipping rule operation from config file. ({$operationStr})"
                );
        }
    }
}
