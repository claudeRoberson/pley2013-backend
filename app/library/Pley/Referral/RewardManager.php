<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Referral;

use Pley\Entity\Referral\Reward;
use Pley\Entity\Referral\RewardOption;
use Pley\Enum\Referral\RewardEnum;
use Pley\Payment\PaymentManagerFactory;
use Pley\Repository\Referral\AcquisitionRepository;
use Pley\Repository\Referral\RewardOptionRepository;
use Pley\Repository\Referral\ProgramRepository;
use Pley\Repository\Referral\TokenRepository;
use Pley\Repository\Referral\RewardRepository;
use Pley\Entity\Referral\Acquisition;
use Pley\Entity\User\User;
use Pley\Repository\User\UserRepository;
use Pley\Dao\Profile\ProfileSubscriptionPlanDao;

/**
 * The <kbd>RewardManager</kbd> class for a referral reward related operations.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class RewardManager
{
    /**
     * @var TokenRepository
     */
    protected $_tokenRepository;

    /**
     * @var RewardRepository
     */
    protected $_rewardRepository;

    /**
     * @var AcquisitionRepository
     */
    protected $_acquisitionRepository;

    /**
     * @var UserRepository
     */
    protected $_userRepository;

    /**
     * @var RewardOptionRepository
     */
    protected $_rewardOptionRepository;

    /**
     * @var ProgramRepository
     */
    protected $_referralProgramRepository;

    /** @var ProfileSubscriptionPlanDao */

    protected $_profileSubsPlanDao;

    /**
     * @var \Pley\Payment\PaymentManagerFactory
     */
    protected $_paymentManagerFactory;


    public function __construct(
        TokenRepository $tokenRepository,
        RewardRepository $rewardRepository,
        AcquisitionRepository $acquisitionRepository,
        UserRepository $userRepository,
        RewardOptionRepository $rewardOptionRepository,
        ProgramRepository $referralProgramRepository,
        PaymentManagerFactory $paymentManagerFactory,
        ProfileSubscriptionPlanDao $profileSubscriptionPlanDao
    )
    {
        $this->_tokenRepository = $tokenRepository;
        $this->_rewardRepository = $rewardRepository;
        $this->_acquisitionRepository = $acquisitionRepository;
        $this->_userRepository = $userRepository;
        $this->_rewardOptionRepository = $rewardOptionRepository;
        $this->_referralProgramRepository = $referralProgramRepository;
        $this->_paymentManagerFactory = $paymentManagerFactory;
        $this->_profileSubsPlanDao = $profileSubscriptionPlanDao;
    }

    /**
     * Create a new reward entry for a given user if not exists .
     * @param \Pley\Entity\User\User $user
     * @return void
     */
    public function createIfNotExists(User $user)
    {
        if ($this->_rewardRepository->findByReferralEmail($user->getEmail())) {
            return;
        }

        $reward = new Reward();
        $reward->setUserId($user->getId())
            ->setReferralUserEmail($user->getEmail())
            ->setAcquiredUsersNum(0)
            ->setStatusId(RewardEnum::REWARD_STATUS_PENDING);
        $this->_rewardRepository->save($reward);
        return;
    }

    /**
     * Increments an acquired users number in a reward record
     * @param Acquisition $acquisition
     */
    public function logReward(Acquisition $acquisition)
    {
        if (!$acquisition->getSourceUserId()) {
            //handling reward record for referral user, which is not registered yet
            $reward = $this->_rewardRepository->findByReferralEmail($acquisition->getReferralUserEmail());
            $reward->setAcquiredUsersNum($reward->getAcquiredUsersNum() + 1);
            $reward->setStatusId(RewardEnum::REWARD_WAITING_REFERRAL_SUBSCRIPTION);
            $this->_rewardRepository->save($reward);
            return;
        }

        $user = $this->_userRepository->find($acquisition->getSourceUserId());

        $profileSubsPlan = $this->_profileSubsPlanDao->findLastByUserAndVPaymentSystem($user->getId(), $user->getVPaymentSystemId());

        if (!$profileSubsPlan) {
            return;
        }

        $paymentManager = $this->_paymentManagerFactory->getManager($user->getVPaymentSystemId());

        $reward = $this->_rewardRepository->findByUserId($acquisition->getSourceUserId());
        $reward->setAcquiredUsersNum($reward->getAcquiredUsersNum() + 1);
        $reward->setStatusId(RewardEnum::REWARD_STATUS_REWARDED);

        $paymentManager->addCredit(
            $user,
            $profileSubsPlan,
            $acquisition->getRewardAmount(),
            'User acquisition reward credit');
        $this->_rewardRepository->save($reward);
        return;
    }

    public function setUserRewardStatus(User $user, $status){
        $reward = $this->_rewardRepository->findByReferralEmail($user->getEmail());
        if($reward){
            $reward->setStatusId($status);
            $this->_rewardRepository->save($reward);
        }
    }

    /**
     * Combines a list of all rewards including associated token for operations controller
     * @return Reward[]
     */
    public function getAllReferralRewards($limit, $offset)
    {
        $rewards = $this->_rewardRepository->where("1 ORDER BY `id` DESC LIMIT ? OFFSET ?", [$limit, $offset]);

        foreach ($rewards as $reward) {
            $reward->setTokens($this->_tokenRepository->findByUserId($reward->getUserId()));
        }
        return $rewards;
    }

    /**
     * Combines a reward details including acquisitions and tokens
     * for a given user ID.
     * @param $userId
     * @return Reward
     */
    public function getUserReferralDetails($userId)
    {
        $reward = $this->_rewardRepository->findByUserId($userId);

        $userIssuedTokens = $this->_tokenRepository->findByUserId($reward->getUserId());
        foreach ($userIssuedTokens as $token) {
            $token->setAcquisitions($this->_acquisitionRepository->findByTokenId($token->getId()));
        }
        $reward->setTokens($userIssuedTokens);
        return $reward;
    }

    /**
     * Combines a reward details including acquisitions and tokens
     * for a given user ID.
     * @param $referralEmail
     * @return Reward
     */
    public function getUserReferralDetailsByEmail($referralEmail)
    {
        $reward = $this->_rewardRepository->findByReferralEmail($referralEmail);

        $userIssuedTokens = $this->_tokenRepository->findByReferralUserEmail($reward->getReferralUserEmail());
        foreach ($userIssuedTokens as $token) {
            $token->setAcquisitions($this->_acquisitionRepository->findByTokenId($token->getId()));
        }
        $reward->setTokens($userIssuedTokens);
        return $reward;
    }

    /**
     * Loads active reward options
     * for a given user ID.
     * @return RewardOption[]
     */
    public function getRewardOptions()
    {
        return $this->_rewardOptionRepository->findActive();
    }

    /**
     * Get the sum of acquisition rewards by referral email
     * for a given user ID.
     * @return float
     */

    public function getTotalAcquisitionRewardAmount($referralEmail)
    {
        $acquisitions = $this->_acquisitionRepository->findByReferralUserEmail($referralEmail);
        $total = 0.00;
        foreach ($acquisitions as $acquisition) {
            $total += $acquisition->getRewardAmount();
        }
        return (float)$total;
    }

    /**
     * Get pending user reward amount from current session
     * @return float
     */

    public function getLoggedInUserReferralRewardAmount(){
        $amount = 0.00;
        $user =  \Auth::user();
        if($user){
            $amount = $this->getTotalPendingAcquisitionRewardAmount($user->email);
        }
        return $amount;
    }

    /**
     * Get the sum of acquisition rewards by referral email for users, which hasn't
     * been rewarded yet
     * for a given user ID.
     * @return float
     */

    public function getTotalPendingAcquisitionRewardAmount($referralEmail)
    {
        $total = 0.00;
        $reward = $this->_rewardRepository->findByReferralEmail($referralEmail);

        if ($reward && $reward->getStatusId() === \Pley\Enum\Referral\RewardEnum::REWARD_WAITING_REFERRAL_SUBSCRIPTION) {
            $acquisitions = $this->_acquisitionRepository->findByReferralUserEmail($referralEmail);
            foreach ($acquisitions as $acquisition) {
                $total += $acquisition->getRewardAmount();
            }
        }
        return (float)$total;
    }

    /**
     * Defines a given credit amount for each acquired user
     * @param \Pley\Entity\Referral\Token | null $token
     * @return float
     */
    public function getAcquisitionRewardAmount(\Pley\Entity\Referral\Token $token = null)
    {
        $referralProgram = null;
        if (!$token) {
            $referralProgram = $this->_referralProgramRepository->getDefaultReferralProgram();
        } else {
            $referralProgram = $this->_referralProgramRepository->find($token->getReferralProgramId());
        }
        return $referralProgram->getRewardCredit();
    }
}