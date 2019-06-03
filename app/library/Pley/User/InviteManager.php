<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\User;

use Pley\Config\ConfigInterface as Config;
use Pley\Entity\User\Invite;
use Pley\Referral\RewardManager;
use Pley\Repository\User\InviteRepository;
use Pley\Repository\User\UserRepository;
use Pley\Mail\AbstractMail as Mail;
use Pley\Enum\Mail\MailTemplateEnum;
use Pley\Mail\MailUser;
use Pley\Mail\MailTagCollection;
use Pley\Enum\InviteEnum;
use Pley\Entity\User\User;
use Pley\Referral\TokenManager;
use Pley\Subscription\SubscriptionManager;

/**
 * The <kbd>InviteManager</kbd> class for a friend invites related operations.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class InviteManager
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Repository\User\UserRepository */
    protected $_userRepository;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mailer;
    /** @var \Pley\Repository\User\InviteRepository */
    protected $_inviteRepository;
    /** @var \Pley\Referral\TokenManager */
    protected $_tokenManager;
    /** @var \Pley\Repository\Referral\AcquisitionRepository */
    protected $_acquisitionRepository;
    /** @var \Pley\Repository\Referral\RewardRepository */
    protected $_rewardRepository;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionManager;

    public function __construct(
        Config $config,
        Mail $mailer,
        UserRepository $userRepository,
        InviteRepository $inviteRepository,
        TokenManager $tokenManager,
        SubscriptionManager $subscriptionManager
    )
    {
        $this->_config = $config;
        $this->_mailer = $mailer;
        $this->_userRepository = $userRepository;
        $this->_inviteRepository = $inviteRepository;
        $this->_tokenManager = $tokenManager;
        $this->_subscriptionManager = $subscriptionManager;
    }

    public function processInvites($inviteList, User $user)
    {
        $existingInviteList = $this->_inviteRepository->findByUserId($user->getId());
        $existingInviteMap = [];

        if(count($existingInviteList) > 100){
            throw new \Exception('Too many invites.');
        }

        foreach ($existingInviteList as $existingInvite) {
            /**
             * @var $existingInvite \Pley\Entity\User\Invite
             */
            $existingInviteMap[$existingInvite->getInviteEmail()] = $existingInvite;
        }
        unset($existingInviteList);

        // Creating the list to add and remind Invites
        /* @var $addList array */
        $addList = [];
        /* @var $existingMembersList \Pley\Entity\User\User[] */
        $existingMembersList = [];
        $resendInvites = [];

        // Categorize the supplied contact list into those to add and those to remind
        foreach ($inviteList as $inviteDetail) {
            $inviteEmail = $inviteDetail['email'];

            // Checking that the user's email is not in the list of invites for this is not correct
            // but could happen as a side effect of the CloudSponge functionality where the user
            // has him/herself on their own contact list.
            if ($inviteEmail == $user->getEmail()) {
                continue;
            }
            // If the requested invite is a user already in their list, then only remind if the
            // invite has not joined yet
            if (isset($existingInviteMap[$inviteEmail])) {
                //skip if such invite already exists
                if (isset($existingInviteMap[$inviteEmail])) {
                    $resendInvites[] = $existingInviteMap[$inviteEmail];
                    continue;
                }
            } else {
                $existingUser = $this->_userRepository->findByEmail($inviteEmail);
                // If a new invite is requested, but the supplied email matches an Existing user
                // in our system, then we just add it to the list of existing memebers.
                if (!empty($existingUser)) {
                    $existingMembersList[] = [
                        'user' => $existingUser,
                        'name' => $inviteDetail['name']
                    ];

                    // Otherwise put in Add list to send new invite request
                } else {
                    $addList[] = $inviteDetail;
                }
            }
        }
        
        $mailTagCollection = new MailTagCollection($this->_config);
        $mailTagCollection->addEntity($user);

        $subject = ucfirst(strtolower($user->getFirstName())) . ' ' . ucfirst(strtolower($user->getLastName())) . ' invited you to join Pley!';
        $mailOptions = [
            'onBehalfOf' => MailUser::withUser($user),
            'subjectName' => $subject
        ];

        $this->_processNewInvites($addList, $user, $mailTagCollection, $mailOptions);
        $this->_resendInvites($resendInvites, $user, $mailTagCollection, $mailOptions);
        $this->_processExistingMemberInvites($existingMembersList, $user);
        return;
    }

    public function processNonUserInvites($inviteList, User $user)
    {
        $existingInviteList = $this->_inviteRepository->findByReferralEmail($user->getEmail());
        $existingInviteMap = [];

        if(count($existingInviteList) > 100){
            throw new \Exception('Too many invites.');
        }

        foreach ($existingInviteList as $existingInvite) {
            /**
             * @var $existingInvite \Pley\Entity\User\Invite
             */
            $existingInviteMap[$existingInvite->getInviteEmail()] = $existingInvite;
        }
        unset($existingInviteList);

        // Creating the list to add and remind Invites
        /* @var $addList array */
        $resendInvites = [];
        $addList = [];

        // Categorize the supplied contact list into those to add and those to remind
        foreach ($inviteList as $inviteDetail) {
            $inviteEmail = $inviteDetail['email'];

            // Checking that the user's email is not in the list of invites for this is not correct
            // but could happen as a side effect of the CloudSponge functionality where the user
            // has him/herself on their own contact list.
            if ($inviteEmail == $user->getEmail()) {
                continue;
            }
            // If the requested invite is a user already in their list, then only remind if the
            // invite has not joined yet
            if (isset($existingInviteMap[$inviteEmail])) {
                //skip if such invite already exists
                if (isset($existingInviteMap[$inviteEmail])) {
                    $resendInvites[] = $existingInviteMap[$inviteEmail];
                    continue;
                }
            } else {
                $existingUser = $this->_userRepository->findByEmail($inviteEmail);
                // If a new invite is requested, but the supplied email matches an Existing user
                // in our system, then we just add it to the list of existing memebers.
                if (!empty($existingUser)) {
                    continue;
                    // Otherwise put in Add list to send new invite request
                } else {
                    $addList[] = $inviteDetail;
                }
            }
        }

        $mailTagCollection = new MailTagCollection($this->_config);
        $mailTagCollection->addEntity($user);

        $subject = 'You\'ve been invited to join Pley!';

        $mailOptions = [
            'onBehalfOf' => MailUser::withUser($user),
            'subjectName' => $subject

        ];
        $this->_processNewInvites($addList, $user, $mailTagCollection, $mailOptions);
        $this->_resendInvites($resendInvites, $user, $mailTagCollection, $mailOptions);
        return;
    }

    /**
     * Helper method to add the new invites for the user and send the invite email to the supplied list.
     * @param array $invites List of Array entries that have the format returned
     *      by the <kbd>::_parseContactList()</kbd> method.
     * @param array $invites
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Mail\MailTagCollection $mailTagCollection
     * @param array $mailOptions
     */
    protected function _processNewInvites($invites, User $user, MailTagCollection $mailTagCollection, $mailOptions)
    {
        if (empty($invites)) {
            return;
        }

        foreach ($invites as $inviteDetail) {
            $token = $this->_tokenManager->create($user, \Pley\Enum\Referral\TokenEnum::TYPE_EMAIL);
            $invite = new Invite();
            $inviteEmail = $inviteDetail['email'];
            $inviteName = $inviteDetail['name'];
            $invite->setUserId($user->getId())
                ->setReferralTokenId($token->getId())
                ->setReferralUserEmail($user->getEmail())
                ->setInviteEmail($inviteEmail)
                ->setInviteName($inviteName)
                ->setReminderCount(0)
                ->setStatus(InviteEnum::STATUS_PENDING);
            $this->_inviteRepository->save($invite);

            $mailTagCollection->addEntity($invite);
            $mailTagCollection->addEntity($token);
            $mailTagCollection->setCustom('discountAmount', $this->_tokenManager->getTokenCouponDiscount($token));

            $mailUserTo = new MailUser($inviteEmail, $inviteName);

            // Sending email to the receiver user
            $this->_mailer->send(
                MailTemplateEnum::USER_INVITE_REQUEST,
                $mailTagCollection,
                $mailUserTo,
                $mailOptions
            );
        }
    }

    /**
     * Helper method to add the new invites for the user and send the invite email to the supplied list.
     * @param array $invites List of Array entries that have the format returned
     *      by the <kbd>::_parseContactList()</kbd> method.
     * @param \Pley\Entity\User\Invite[] $invites
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Mail\MailTagCollection $mailTagCollection
     * @param array $mailOptions
     */
    protected function _resendInvites($invites, User $user, MailTagCollection $mailTagCollection, $mailOptions)
    {
        if (empty($invites)) {
            return;
        }
        /**
         * @var $invites \Pley\Entity\User\Invite[]
         */
        foreach ($invites as $invite) {
            $token = $this->_tokenManager->find($invite->getReferralTokenId());
            if(!$token){
                continue;
            }
            $invite->setReminderCount($invite->getReminderCount()+1);
            $this->_inviteRepository->save($invite);

            $mailTagCollection->addEntity($invite);
            $mailTagCollection->addEntity($token);
            $mailTagCollection->setCustom('discountAmount', $this->_tokenManager->getTokenCouponDiscount($token));

            $mailUserTo = new MailUser($invite->getInviteEmail(), $invite->getInviteName());

            // Sending email to the receiver user
            $this->_mailer->send(
                MailTemplateEnum::USER_INVITE_REQUEST,
                $mailTagCollection,
                $mailUserTo,
                $mailOptions
            );
        }
    }


    /**
     * Helper method to add invites as Existing Members
     * @param array $existingMemberList A list of Arrays containing the following
     * structure <kbd>array('user' => \Pley\Entity\User\User, 'name' => inviteNameString)</kbd>
     * @param \Pley\Entity\User\User $user
     */
    protected function _processExistingMemberInvites($existingMemberList, User $user)
    {
        if (empty($existingMemberList)) {
            return;
        }
        foreach ($existingMemberList as $existingMemberDetail) {
            /**
             * @var $existingUser \Pley\Entity\User\User
             */
            $existingUser = $existingMemberDetail['user'];

            $inviteName = $existingMemberDetail['name'];
            $invite = new Invite();
            $invite->setUserId($user->getId())
                /**
                 * setting referral token id to zero,
                 * there's no need to create a token for existing user, just a dummy invite
                 */
                ->setReferralTokenId(0)
                ->setInviteEmail($existingUser->getEmail())
                ->setInviteName($inviteName)
                ->setReminderCount(0)
                ->setStatus(InviteEnum::STATUS_EXISTING_MEMBER);
            $this->_inviteRepository->save($invite);
        }
    }
}