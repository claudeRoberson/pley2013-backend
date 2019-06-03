<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Enum;

/**
 * The <kbd>ItemPartEnum</kbd> represents types of parts an Item can have so that based on the type
 * we can make some choices when creating a Shipment for a User.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
class TransactionEnum extends AbstractEnum
{
    const CHARGE  = 1;
    const CREDIT  = 2;
    const DECLINE = 3;
    const REFUND  = 4;
    const DISPUTE = 5;
    const FAILED  = 6;
}
