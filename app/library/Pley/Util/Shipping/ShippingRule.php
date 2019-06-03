<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Util\Shipping;

use \Pley\Exception\UnsupportedOperationException;

/**
 * The <kbd>ShippingRule</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package 
 * @subpackage
 */
class ShippingRule
{
    public static function rateFromRuleSet($ruleList, $shipmentWeightOz, $zone)
    {
        $RULE_OPERATION     = 0;
        $RULE_COMPARE_VALUE = 1;
        $RULE_DEFINITION    = 2;
        
        $rate = null;
        
        // Iterating the rate rules to find the one that matches the weight supplied
        foreach ($ruleList as $ruleDef) {
            $operation    = $ruleDef[$RULE_OPERATION];
            $compareValue = $ruleDef[$RULE_COMPARE_VALUE];
            $rateDef      = $ruleDef[$RULE_DEFINITION];
         
            // The compare call looks something like this once values are passed from variables
            //   e.g. ShippingRule::compareRule($shipmentWeightOzRoundUp, '<=', 15)
            if (!self::compareRule($shipmentWeightOz, $operation, $compareValue)) {
                continue;
            }
            
            // If the rate Definition is a string, that is the rate regardless of the zone
            if (is_string($rateDef)) {
                $rate = $rateDef;
                
            // Otherwise, the rate Definition is a new map of rates per zone
            } else {
                $rate = $rateDef[$zone];
            }
            
            // We've processed the rule, so we should not attempt any other rule
            break;
        }
        
        return $rate;
    }
    
    /**
     * Performs a ternary boolean comparsion for the supplied vales.
     * <p>Example use: <kbd>ShippingRule::compareRule($value1, "==", $value2)</kbd>
     * 
     * @param mixed  $value1   The left side of the operator
     * @param string $operator A String like '<', '<=', '==', '>', '>='
     * @param mixed  $value2   The right side of the operator
     * @return boolean 
     * @throws \Pley\Exception\UnsupportedOperationException
     */
    public static function compareRule($value1, $operator, $value2)
    {
        switch ($operator) {
            case '<'  : return $value1 <  $value2;
            case '<=' : return $value1 <= $value2;
            case '>'  : return $value1 >  $value2;
            case '>=' : return $value1 >= $value2;
            case '==' : return $value1 == $value2;
            default: 
                $operationStr = "`{$value1}` {$operator} `{$value2}`";
                
                throw new UnsupportedOperationException(
                    "Unsupported shipping rule operation from config file. ({$operationStr})"
                );
        }
    }
}
