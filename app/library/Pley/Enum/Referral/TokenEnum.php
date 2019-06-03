<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Enum\Referral;

/**
 * The <kbd>TokenEnum</kbd> Holds constants that represent
 * reward types, which could be given to a users.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */

class TokenEnum
{
    const TYPE_SOCIAL = 1;

    const TYPE_EMAIL = 2;

    /**
     * Maps and returns the string value for a given token type ID.
     * @param int $typeId
     * @return int
     * @throws \UnexpectedValueException If the size id is not supported.
     */
    public static function asString($typeId)
    {
        switch ($typeId) {
            case self::TYPE_SOCIAL :
                return 'Universal';
            case self::TYPE_EMAIL :
                return 'Email';
            default :
                throw new \UnexpectedValueException("Token type ID `{$typeId}` not supported");
        }
    }
}