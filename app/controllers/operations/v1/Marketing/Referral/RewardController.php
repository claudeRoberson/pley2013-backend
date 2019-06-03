<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace operations\v1\Marketing\Referral;

use Pley\Coupon\CouponManager;
use Pley\Enum\Referral\RewardEnum;
use Pley\Referral\RewardManager;
use Pley\Repository\Referral\RewardRepository;
use Pley\Repository\User\InviteRepository;
use Pley\Repository\User\UserRepository;
use Pley\Repository\Referral\AcquisitionRepository;
use Pley\Repository\Referral\ProgramRepository;
use Pley\Util\DateTime;

/**
 * The <kbd>RewardController</kbd> responsible on making CRUD operations on a rewards entity.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class RewardController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Referral\RewardManager */
    protected $_rewardManager;
    /** @var \Pley\Repository\Referral\RewardRepository */
    protected $_rewardRepository;
    /** @var \Pley\Repository\User\UserRepository */
    protected $_userRepository;
    /** @var \Pley\Repository\Referral\AcquisitionRepository */
    protected $_acquisitionRepository;
    /** @var \Pley\Repository\Referral\ProgramRepository */
    protected $_referralProgramRepository;
    /** @var \Pley\Repository\User\InviteRepository */
    protected $_inviteRepository;
    /** @var \Pley\Coupon\CouponManager */
    protected $_couponManager;


    public function __construct(
        RewardManager $rewardManager,
        RewardRepository $rewardRepository,
        UserRepository $userRepository,
        AcquisitionRepository $acquisitionRepository,
        ProgramRepository $referralProgramRepository,
        InviteRepository $inviteRepository,
        CouponManager $couponManager
    )
    {
        parent::__construct();
        $this->_rewardManager = $rewardManager;
        $this->_rewardRepository = $rewardRepository;
        $this->_userRepository = $userRepository;
        $this->_acquisitionRepository = $acquisitionRepository;
        $this->_referralProgramRepository = $referralProgramRepository;
        $this->_inviteRepository = $inviteRepository;
        $this->_couponManager = $couponManager;
    }

    // GET /marketing/referral/reward
    public function getUserReferralRewards()
    {
        \RequestHelper::checkGetRequest();
        $response = [];
        $limit = 200;
        $offset = 0;

        $rewards = $this->_rewardManager->getAllReferralRewards($limit, $offset);
        foreach ($rewards as $reward) {
            $rewardData = $reward->toArray();
            $rewardData['userId'] = $reward->getUserId();
            $rewardData['userEmail'] = $reward->getReferralUserEmail();
            $rewardData['status'] = RewardEnum::asString($reward->getStatusId());
            $response[] = $rewardData;
        }
        return \Response::json($response);
    }

    // GET /marketing/referral/program
    public function getReferralPrograms()
    {
        \RequestHelper::checkGetRequest();
        $response = [];
        $programs = $this->_referralProgramRepository->all();
        foreach ($programs as $program) {
            $acquisitionCoupon = $this->_couponManager->getCoupon($program->getAcquisitionCouponId());
            $programData = $program->toArray();
            $programData['acquisitionCouponDiscount'] = $acquisitionCoupon->getDiscountAmount();
            $response[] = $programData;
        }
        return \Response::json($response);
    }

    // GET /marketing/referral/{{referral_email}}/detail
    public function getUserReferralDetails($referralEmail)
    {
        \RequestHelper::checkGetRequest();
        $response = [
            'referrerDetail' => [],
            'engagements' => [],
            'rewardOptions' => []
        ];
        $reward = $this->_rewardManager->getUserReferralDetailsByEmail($referralEmail);
        $rewardOptions = $this->_rewardManager->getRewardOptions();

        $acquisitions = $this->_acquisitionRepository->findByReferralUserEmail($referralEmail);
        foreach ($acquisitions as $acquisition) {
            $response['engagements'][] = $acquisition->toArray();
        }
        foreach ($rewardOptions as $option) {
            $response['rewardOptions'][] = $option->toArray();
        }
        $response['referrerDetail'] = $reward->toArray();
        $response['referrerDetail']['invitesSentNum'] = count($this->_inviteRepository->findByReferralEmail($referralEmail));
        $response['referrerDetail']['userEmail'] = $referralEmail;
        $response['referrerDetail']['status'] = RewardEnum::asString($reward->getStatusId());
        return \Response::json($response);
    }

    // POST /marketing/referral/{{user_id}}/reward
    public function grantRewardToUser($userId)
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();
        $rewardData = \Input::json('rewardData');

        $reward = $this->_rewardRepository->findByUserId($userId);
        $reward->setStatusId(RewardEnum::REWARD_STATUS_REWARDED)
            ->setRewardedComment(isset($rewardData['comment']) ? $rewardData['comment'] : null)
            ->setRewardedOptionId($rewardData['rewardOptionId'])
            ->setRewardedAt(DateTime::date(time()));

        $this->_rewardRepository->save($reward);
        return \Response::json($reward, 201);
    }
}