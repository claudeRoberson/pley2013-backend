<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Enum\Paypal;

use Pley\Enum\AbstractEnum;

/**
 * The <kbd>AgreementStateEnum</kbd> class represents Paypal agreement states
 *
 * @author Vsevolod Yatsiuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class AgreementStateEnum extends AbstractEnum
{
    const ACTIVE = 'Active';

    const CANCELLED = 'Cancelled';
}