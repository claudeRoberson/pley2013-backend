<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\Enum;

/**
 * The <kbd>DbSyncLockEnum</kbd> Holds constants that represent a specific DB record where multiple
 * processess can lock on when dealing with a specific task.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */
abstract class DbSyncLockEnum
{
    const SHIPMENT_LABEL_PURCHASE = 1;
}
