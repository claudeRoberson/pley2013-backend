<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Enum\Referral;

/**
 * The <kbd>RewardEnum</kbd> Holds constants that represent
 * reward types, which could be given to a users.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Enum
 * @subpackage Enum
 */

class RewardEnum
{
    const REWARD_STATUS_PENDING = 1;
    const REWARD_STATUS_REWARDED = 2;
    const REWARD_STATUS_DENIED = 3;
    const REWARD_WAITING_REFERRAL_SUBSCRIPTION = 4;

    /**
     * Maps and returns the string value for a given invite status ID.
     * @param int $statusId
     * @return int
     * @throws \UnexpectedValueException If the size id is not supported.
     */
    public static function asString($statusId)
    {
        switch ($statusId) {
            case self::REWARD_STATUS_PENDING :
                return 'No Acquisitions';
            case self::REWARD_STATUS_REWARDED :
                return 'Rewarded';
            case self::REWARD_STATUS_DENIED :
                return 'Denied';
            case self::REWARD_WAITING_REFERRAL_SUBSCRIPTION :
                return 'Waiting Referral Subscription';
            default :
                throw new \UnexpectedValueException("Reward status ID `{$statusId}` not supported");
        }
    }
}