<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace api\v1\User;

use \Pley\Coupon\CouponManager;
use \Pley\Referral\RewardManager;
use \Pley\Referral\TokenManager;
use \Pley\Dao\User\UserDao;
use Pley\Repository\Referral\TokenRepository;

/**
 * Controller class for managing user referral tokens
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package api.v1
 */
class UserReferralController extends \api\v1\BaseController
{
    /** @var \Pley\Referral\TokenManager */
    protected $_tokenManager;
    /** @var \Pley\Coupon\CouponManager */
    protected $_couponManager;
    /** @var \Pley\Referral\RewardManager */
    protected $_rewardManager;
    /** @var \Pley\Repository\Referral\TokenRepository */
    protected $_tokenRepository;
    /** @var \Pley\Dao\User\UserDao */
    protected $_userDao;

    public function __construct(
        TokenManager $tokenManager,
        CouponManager $couponManager,
        RewardManager $rewardManager,
        TokenRepository $tokenRepository,
        UserDao $userDao)
    {
        $this->_tokenManager = $tokenManager;
        $this->_couponManager = $couponManager;
        $this->_rewardManager = $rewardManager;
        $this->_tokenRepository = $tokenRepository;
        $this->_userDao = $userDao;
    }

    // GET /user/referral/info
    public function getReferralInfo()
    {
        \RequestHelper::checkGetRequest();
        $acquisitionCoupon = $this->_couponManager->getAcquisitionCoupon(null);

        $response = [
            'inviteRewardAmount' => $this->_rewardManager->getAcquisitionRewardAmount(null),
            'referralDiscountAmount' => $acquisitionCoupon->getDiscountAmount(),
        ];

        return \Response::json($response);
    }

    /**
     * Get universal referral token
     * GET /user/referral/token/
     */
    public function getUniversalTokens()
    {
        \RequestHelper::checkGetRequest();

        $user = $this->_checkAuthenticated();
        $response = [
            'success' => true,
            'tokens' => []
        ];
        $tokens = $this->_tokenManager->getUserTokensByType(
            $user,
            \Pley\Enum\Referral\TokenEnum::TYPE_SOCIAL
        );
        if (!count($tokens)) {
            $tokens[] = $this->_tokenManager->create($user, \Pley\Enum\Referral\TokenEnum::TYPE_SOCIAL);
        }
        foreach ($tokens as $token) {
            $response['tokens'][] = $token->toArray();
        }
        return \Response::json($response);
    }

    /**
     * Create FB universal share TOKEN
     * POST /referral/token
     */
    public function createFacebookReferralToken()
    {
        \RequestHelper::checkPostRequest();
        $response = [];

        $json = \Input::json()->all();
        $user = $this->_getLoggedUser();

        if(!$user){
            $rules = [
                'referralEmail' => 'required',
            ];

            \ValidationHelper::validate($json, $rules);

            $user = \Pley\Entity\User\User::dummy();
            $user->setEmail($json['referralEmail']);
        }


        $tokens = $this->_tokenRepository->findByReferralEmailAndType(
            $user->getEmail(),
            \Pley\Enum\Referral\TokenEnum::TYPE_SOCIAL
        );
        if(!$tokens){
            $token = $this->_tokenManager->create($user, \Pley\Enum\Referral\TokenEnum::TYPE_SOCIAL);
        }else{
            $token = current($tokens);
        }
        $response['token'] = $token->getToken();

        return \Response::json($response);
    }

    // GET /user/referral/details/{$token}
    public function getReferralDetails($tokenId)
    {
        \RequestHelper::checkGetRequest();

        $token = $this->_tokenManager->findByToken($tokenId);
        \ValidationHelper::entityExist($token, \Pley\Entity\Referral\Token::class);

        $inviterUserId = $token->getUserId();
        $inviterUser = $this->_userDao->find($inviterUserId);

        $acquisitionCoupon = $this->_couponManager->getAcquisitionCoupon($token);

        $response = [
            'success' => true,
            'token' => $tokenId,
            'user' => [
                'firstName' => ($inviterUser) ? $inviterUser->getFirstName() : null,
                'lastName' => ($inviterUser) ? $inviterUser->getLastName() : null,
            ],
            'discountAmount' => $acquisitionCoupon->getDiscountAmount(),
            'isActive' => $token->isActive(),
            'createdAt' => $token->getCreatedAt(),
        ];
        return \Response::json($response);
    }

}