<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Referral;

use Pley\Coupon\CouponManager;
use Pley\Entity\User\User;

use Pley\Repository\Referral\AcquisitionRepository;
use Pley\Repository\Referral\TokenRepository;
use Pley\Repository\Referral\ProgramRepository;
use Pley\Entity\Referral\Token;

/**
 * The <kbd>TokenManager</kbd> class for a referral tokens related operations.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class TokenManager
{
    /**
     * @var TokenRepository
     */
    protected $_tokenRepository;
    /**
     * @var AcquisitionRepository
     */
    protected $_acquisitionRepository;

    /** @var ProgramRepository */
    protected $_programRepository;

    /**
     * @var RewardManager
     */
    protected $_rewardManager;

    /**
     * @var CouponManager
     */
    protected $_couponManager;

    public function __construct(
        TokenRepository $tokenRepository,
        RewardManager $rewardManager,
        CouponManager $couponManager,
        AcquisitionRepository $acquisitionRepository,
        ProgramRepository $programRepository
    )
    {
        $this->_tokenRepository = $tokenRepository;
        $this->_rewardManager = $rewardManager;
        $this->_couponManager = $couponManager;
        $this->_acquisitionRepository = $acquisitionRepository;
        $this->_programRepository = $programRepository;
    }

    /**
     * Create a new token based on type given
     * @param \Pley\Entity\User\User $user
     * @param int $type
     * @return \Pley\Entity\Referral\Token
     */
    public function create(User $user, $type = \Pley\Enum\Referral\TokenEnum::TYPE_SOCIAL)
    {
        $token = new Token();
        $token->setActive(1)
            ->setToken(\Pley\Util\Token::uuid())
            ->setTokenTypeId($type)
            ->setReferralProgramId($this->_programRepository->getDefaultReferralProgram()->getId())
            ->setUserId($user->getId())
            ->setReferralUserEmail($user->getEmail());
        $this->_rewardManager->createIfNotExists($user);
        return $this->_tokenRepository->save($token);
    }

    /**
     * Redeems a given token
     * @param \Pley\Entity\Referral\Token $token
     * return void
     */
    public function redeem(Token $token)
    {
        switch ($token->getTokenTypeId()) {
            case \Pley\Enum\Referral\TokenEnum::TYPE_EMAIL:
                $token->setActive(0);
                break;
            case \Pley\Enum\Referral\TokenEnum::TYPE_SOCIAL:
                break; //do nothing, universal tokens can be redeemed infinitely
        }
        $this->_tokenRepository->save($token);
    }

    /**
     * Get all user tokens by a given token type
     * @param \Pley\Entity\User\User $user
     * @param int $type
     * @return \Pley\Entity\Referral\Token[]
     */
    public function getUserTokensByType(User $user, $type = \Pley\Enum\Referral\TokenEnum::TYPE_SOCIAL)
    {
        $tokens = $this->_tokenRepository->findByUserIdAndType($user->getId(), $type);
        foreach ($tokens as $token) {
            $token->setAcquisitions($this->_acquisitionRepository->findByTokenId($token->getId()));
        }
        return $tokens;
    }

    /**
     * Get all user token by a given token
     * @param string $token
     * @return \Pley\Entity\Referral\Token
     */
    public function findByToken($token)
    {
        return $this->_tokenRepository->findByToken($token);
    }

    /**
     * Get all user token by a given token
     * @param int $id
     * @return \Pley\Entity\Referral\Token
     */
    public function find($id)
    {
        return $this->_tokenRepository->find($id);
    }

    /**
     * Get a a coupon discount from a supplied token
     * @param \Pley\Entity\Referral\Token | null $token
     * @return float
     */
    public function getTokenCouponDiscount(\Pley\Entity\Referral\Token $token = null)
    {
        $referralProgram = null;
        if (!$token) {
            $referralProgram = $this->_programRepository->getDefaultReferralProgram();
        } else {
            $referralProgram = $this->_programRepository->find($token->getReferralProgramId());
        }
        $coupon = $this->_couponManager->getCoupon($referralProgram->getAcquisitionCouponId());
        return $coupon->getDiscountAmount();
    }
}