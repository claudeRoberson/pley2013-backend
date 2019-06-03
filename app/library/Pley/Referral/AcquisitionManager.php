<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Referral;

use Pley\Repository\Referral\AcquisitionRepository;
use Pley\Repository\Referral\TokenRepository;
use Pley\Entity\Referral\Acquisition;
use Pley\Entity\User\User;

/**
 * The <kbd>AcquisitionManager</kbd> class for a referral engagements related operations.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class AcquisitionManager
{
    /**
     * @var TokenRepository
     */
    protected $_tokenRepository;

    /**
     * @var AcquisitionRepository
     */
    protected $_acquisitionRepository;

    /**
     * @var TokenManager
     */
    protected $_tokenManager;

    /**
     * @var RewardManager
     */
    protected $_rewardManager;

    public function __construct(
        TokenRepository $tokenRepository,
        AcquisitionRepository $acquisitionRepository,
        TokenManager $tokenManager,
        RewardManager $rewardManager
    )
    {
        $this->_tokenRepository = $tokenRepository;
        $this->_acquisitionRepository = $acquisitionRepository;
        $this->_tokenManager = $tokenManager;
        $this->_rewardManager = $rewardManager;
    }

    /**
     * @param \Pley\Entity\User\User $acquiredUser
     * @param $token
     * @return \Pley\Entity\Referral\Acquisition
     */
    public function registerAcquisition(User $acquiredUser, $token)
    {
        $referralToken = $this->_tokenManager->findByToken($token);
        if (!$referralToken) {
            return;
        }
        if(!$referralToken->isActive()){
            return;
        }
        $acquisition = new Acquisition();
        $acquisition->setReferralTokenId($referralToken->getId())
            ->setSourceUserId($referralToken->getUserId())
            ->setReferralUserEmail($referralToken->getReferralUserEmail())
            ->setAcquiredUserId($acquiredUser->getId())
            ->setRewardAmount($this->_rewardManager->getAcquisitionRewardAmount($referralToken));

        $this->_acquisitionRepository->save($acquisition);
        $this->_tokenManager->redeem($referralToken);
        
        \Event::fire(\Pley\Enum\EventEnum::REFERRAL_ACQUISITION_CREATE, [
            'engagement' => $acquisition,
            'token'      => $referralToken
        ]);
        
        return $acquisition;
    }
}