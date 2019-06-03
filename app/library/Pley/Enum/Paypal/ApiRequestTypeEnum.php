<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Enum\Paypal;

use Pley\Enum\AbstractEnum;

/**
 * The <kbd>ApiRequestTypeEnum</kbd> class represents Paypal API request types
 *
 * @author Vsevolod Yatsiuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ApiRequestTypeEnum extends AbstractEnum
{
    const AGREEMENT_CREATE = 'agreement.create';
    const AGREEMENT_EXECUTE = 'agreement.execute';
    const AGREEMENT_UPDATE = 'agreement.update';
}