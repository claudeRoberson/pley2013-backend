<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Event;

use \Pley\Config\ConfigInterface as Config;
use Pley\Entity\Referral\Acquisition;
use Pley\Entity\Referral\Token;
use Pley\Enum\InviteEnum;
use Pley\Referral\RewardManager;
use Pley\Repository\User\InviteRepository;
use Pley\Repository\User\UserRepository;

/**
 * Event subscriber for referral related events
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ReferralEventSubscriber extends AbstractEventSubscriber
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;
    /** @var RewardManager */
    protected $_rewardManager;
    /** @var InviteRepository */
    protected $_inviteRepository;
    /** @var UserRepository */
    protected $_userRepository;

    public function __construct(
        Config $config,
        RewardManager $rewardManager,
        InviteRepository $inviteRepository,
        UserRepository $userRepository
    )
    {
        $this->_config = $config;

        $this->_rewardManager    = $rewardManager;
        $this->_inviteRepository = $inviteRepository;
        $this->_userRepository   = $userRepository;

    }

    /**
     * Increments an acquired users number in a reward record
     * @param Acquisition $acquisition
     * @param Token $token
     */
    public function logUserReward(Acquisition $acquisition, Token $token)
    {
        // For the time being, we only support credits to the source User if they are a paid user
        // cause they have a billing account we can credit.
        // So, if the user does not have a billing account, for the time being they won't get any
        // credits until a new feature to store credits on our end until they add billing is developed

        if($acquisition->getSourceUserId()){
            $user = $this->_userRepository->find($acquisition->getSourceUserId());
            if (empty($user->getVPaymentSystemId())) {
                return;
            }
        }else{
            $user = \Pley\Entity\User\User::dummy();
            $user->setEmail($acquisition->getReferralUserEmail());
        }

        $this->_rewardManager->logReward($acquisition);
        $this->_sendRewardNotificationEmail($acquisition, $user);
    }

    /**
     * Updates user invite on acquisition creation
     * @param Acquisition $acquisition
     * @param Token $token
     */
    public function updateInvite(Acquisition $acquisition, Token $token)
    {
        $invite = $this->_inviteRepository->findByTokenId($token->getId());
        if (!$invite) {
            return;
        }
        $invite->setStatus(InviteEnum::STATUS_JOINED);
        $this->_inviteRepository->save($invite);
    }

    /**
     * Sends the email to user, when he gets rewarded
     * @param \Pley\Entity\User\User $user
     */
    protected function _sendRewardNotificationEmail(\Pley\Entity\Referral\Acquisition $acquisition, \Pley\Entity\User\User $user)
    {
        $this->_mail = \App::make('\Pley\Mail\AbstractMail');

        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($user);
        $mailTagCollection->addEntity($acquisition);
        if(!$user->getId()){
            $mailTagCollection->setCustom('isRegistered', false);
            $mailTagCollection->setCustom('totalRewardAmount',$this->_rewardManager->getTotalAcquisitionRewardAmount($user->getEmail()) );
        }else{
            $mailTagCollection->setCustom('isRegistered', true);
        }

        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::REFERRAL_REWARD_GRANTED;

        $mailUserTo = \Pley\Mail\MailUser::withUser($user);

        try {
            $this->_mail->send($mailTemplateId, $mailTagCollection, $mailUserTo);
        } catch (\Exception $ex) {
            \Log::error((string)$ex);
        }
    }

    /** {@inheritDoc} */
    protected function _getEventToMethodData()
    {
        return [
            [\Pley\Enum\EventEnum::REFERRAL_ACQUISITION_CREATE, 'logUserReward'],
            [\Pley\Enum\EventEnum::REFERRAL_ACQUISITION_CREATE, 'updateInvite'],
        ];
    }
}