<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Event;

use \Pley\Config\ConfigInterface as Config;
use Pley\Entity\User\User;
use Pley\Enum\Referral\RewardEnum;
use Pley\Referral\RewardManager;
use Pley\Repository\Referral\AcquisitionRepository;
use Pley\Repository\Referral\RewardRepository;
use Pley\Repository\Referral\TokenRepository;
use Pley\Repository\User\InviteRepository;
use Pley\Repository\User\UserRepository;

/**
 * Event subscriber for referral related events
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class UserEventSubscriber extends AbstractEventSubscriber
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;
    /** @var RewardManager */
    protected $_rewardManager;
    /** @var InviteRepository */
    protected $_inviteRepository;
    /** @var RewardRepository */
    protected $_rewardRepository;
    /** @var TokenRepository */
    protected $_tokenRepository;
    /** @var AcquisitionRepository */
    protected $_acquisitionRepository;
    /** @var UserRepository */
    protected $_userRepository;

    public function __construct(
        Config $config,
        RewardManager $rewardManager,
        InviteRepository $inviteRepository,
        RewardRepository $rewardRepository,
        TokenRepository $tokenRepository,
        AcquisitionRepository $acquisitionRepository,
        UserRepository $userRepository
    )
    {
        $this->_config = $config;
        $this->_rewardManager = $rewardManager;
        $this->_inviteRepository = $inviteRepository;
        $this->_rewardRepository = $rewardRepository;
        $this->_tokenRepository = $tokenRepository;
        $this->_acquisitionRepository = $acquisitionRepository;
        $this->_userRepository = $userRepository;
    }

    /**
     * Check user referral acquisitions and assign user id
     * @param User $user
     */
    public function updateReferralEntitiesWithUser(User $user)
    {
        $rewardRecord = $this->_rewardRepository->findByReferralEmail($user->getEmail());

        if (!$rewardRecord) {
            return;
        }
        if (in_array($rewardRecord->getStatusId(), [RewardEnum::REWARD_STATUS_PENDING, RewardEnum::REWARD_WAITING_REFERRAL_SUBSCRIPTION])) {
            //this user has sent referral links, but has no acquisitions yet
            //just update all the referral related entries with user id
            $this->_inviteRepository->updateEntriesWithUser($user);
            $this->_tokenRepository->updateEntriesWithUser($user);
            $this->_acquisitionRepository->updateEntriesWithUser($user);
            $rewardRecord->setUserId($user->getId());
            $this->_rewardRepository->save($rewardRecord);
            return;
        }
    }

    /** {@inheritDoc} */
    protected function _getEventToMethodData()
    {
        return [
            [\Pley\Enum\EventEnum::USER_ACCOUNT_CREATE, 'updateReferralEntitiesWithUser'],
        ];
    }
}