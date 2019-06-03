<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Coupon;

/**
 * The <kbd>CouponManager</kbd> class deals with many operations related to coupon and discounts functionality.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Coupon
 * @subpackage Coupon
 */
class CouponManager
{
    private static $_INVITE_COUPON_CODE = '_InV1+3_';
    
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Repository\Coupon\CouponRepository */
    protected $_couponRepository;
    /** @var \Pley\Repository\Referral\ProgramRepository */
    protected $_programRepository;
    /** @var \Pley\Dao\User\UserCouponRedemptionDao */
    protected $_userCouponRedemptionDao;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionManager;

    public function __construct(
        \Pley\Db\AbstractDatabaseManager $dbManager,
        \Pley\Repository\Coupon\CouponRepository $couponRepository,
        \Pley\Repository\Referral\ProgramRepository $programRepository,
        \Pley\Dao\User\UserCouponRedemptionDao $userCouponRedemptionDao,
        \Pley\Subscription\SubscriptionManager $subscriptionManager
    )
    {
        $this->_dbManager               = $dbManager;
        $this->_couponRepository        = $couponRepository;
        $this->_programRepository       = $programRepository;
        $this->_userCouponRedemptionDao = $userCouponRedemptionDao;
        $this->_subscriptionManager     = $subscriptionManager;

    }

    /**
     * Return the <kbd>Coupon</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\Coupon\Coupon
     */
    public function getCoupon($id)
    {
        return $this->_couponRepository->find($id);
    }

    /**
     * Return the <kbd>Coupon</kbd> entity for the supplied code or null if not found.
     * @param string $code
     * @return \Pley\Entity\Coupon\Coupon
     */
    public function getByCode($code)
    {
        return $this->_couponRepository->findByCode($code);
    }

    /**
     * Returns a list of all <kbd>Coupon</kbd> entities.
     * @return \Pley\Entity\Coupon\Coupon[]
     */
    public function getAllCoupons()
    {
        return $this->_couponRepository->all();
    }

    /**
     * Returns a list of <kbd>Coupon</kbd> entities, which are active and special.
     * @return \Pley\Entity\Coupon\Coupon[]
     */
    public function getSpecialCoupons()
    {
        return $this->_couponRepository->findEnabledSpecial();
    }

    /**
     * Returns the <kbd>Coupon</kbd> entity specific to the Invite Friend functionality.
     * @param \Pley\Entity\Referral\Token | null $token
     * @return \Pley\Entity\Coupon\Coupon
     */
    public function getAcquisitionCoupon(\Pley\Entity\Referral\Token $token = null)
    {
        $referralProgram = null;
        if(!$token){
            $referralProgram = $this->_programRepository->getDefaultReferralProgram();
        }else{
            $referralProgram = $this->_programRepository->find($token->getReferralProgramId());
        }
        return $this->_couponRepository->find($referralProgram->getAcquisitionCouponId());
    }
    
    /**
     * Returns coupons which has been redeemed by a given user.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\Coupon\Coupon[]
     */
    public function getCouponsUsedByUser(\Pley\Entity\User\User $user)
    {
        return $this->_couponRepository->getCouponsUsedByUser($user);
    }

    /**
     * Calculates a payment plan first charge discount based on a coupon given
     * @param \Pley\Entity\Payment\VendorPaymentPlan $paymentPlan
     * @param float $baseAmount
     * @param \Pley\Entity\Coupon\Coupon | null $coupon
     * @throws \Pley\Exception\Coupon\CouponTypeInvalidException when coupon type ID does not exist
     * @throws \Pley\Exception\Coupon\CouponDiscountInvalidException when discount amount is greater than base amount
     * @return float
     */
    public function calculateDiscount(\Pley\Entity\Payment\VendorPaymentPlan $vendorPaymentPlan, $baseAmount, $coupon = null)
    {
        if (is_null($coupon)) {
            return 0;
        }
        $discountAmount = 0;
        switch ($coupon->getType()) {
            case (\Pley\Enum\CouponTypeEnum::FIXED):
            case (\Pley\Enum\CouponTypeEnum::INVITE_REDEEM):
                $discountAmount = $coupon->getDiscountAmount();
                break;
            case (\Pley\Enum\CouponTypeEnum::PERCENTAGE):
                $discountAmount = round($vendorPaymentPlan->getUnitPrice() * ($coupon->getDiscountAmount() / 100), 2);
                break;
            default:
                throw new \Pley\Exception\Coupon\CouponTypeInvalidException($coupon, $coupon->getType());
        }
        if ($baseAmount - $discountAmount < 0) {
            throw new \Pley\Exception\Coupon\CouponDiscountInvalidException($coupon, $coupon->getType());
        }
        return $discountAmount;
    }

    /**
     * Gets a coupon discount type
     * @param \Pley\Entity\Coupon\Coupon $coupon
     * @return bool
     */
    public function isMaxUsagesExceeded($coupon)
    {
        if ($coupon->getMaxUsages() !== null && $coupon->getMaxUsages() !== 0
            && $this->_userCouponRedemptionDao->getRedemptionsCount($coupon) >= $coupon->getMaxUsages()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Gets a coupon discount type
     * @param \Pley\Entity\Coupon\Coupon $coupon
     * @return int|null
     */
    public function getDiscountType($coupon)
    {
        if (is_null($coupon)) {
            return null;
        }
        return \Pley\Enum\TransactionDiscountTypeEnum::COUPON;
    }

    /**
     * Gets a coupon ID from coupon entity
     * @param \Pley\Entity\Coupon\Coupon $coupon
     * @return int|null
     */
    public function getDiscountSourceId($coupon)
    {
        if (is_null($coupon)) {
            return null;
        }
        return $coupon->getId();
    }

    /**
     * Creates a <kbd>UserCouponRedemption</kbd> entity and saves it.
     * @param \Pley\Entity\Coupon\Coupon $coupon
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscriptionTransaction $transaction
     * @return float
     */
    public function logRedemption(
        \Pley\Entity\Coupon\Coupon $coupon,
        \Pley\Entity\User\User $user,
        \Pley\Entity\Profile\ProfileSubscriptionTransaction $transaction
    )
    {
        $redemption = new \Pley\Entity\User\UserCouponRedemption(
            null,
            $user->getId(),
            $coupon->getId(),
            $transaction->getId(),
            $transaction->getProfileSubscriptionId(),
            \Pley\Util\Time\DateTime::date(time())
        );
        $this->_userCouponRedemptionDao->save($redemption);
    }

    /**
     * Validates and sets a <kbd>Coupon</kbd> for further processing.
     * @param string $couponCode
     * @param \Pley\Entity\User\User $user
     * @param int $subscriptionId
     * @param int $paymentPlanId
     * @return \Pley\Entity\Coupon\Coupon
     */
    public function validateCouponCode($couponCode, \Pley\Entity\User\User $user, $subscriptionId, $paymentPlanId)
    {
        $coupon = $this->getByCode($couponCode);
        if (!$coupon) {
            throw new \Pley\Exception\Coupon\CouponNotFoundException($couponCode, $user);
        }
        if (!$coupon->isEnabled()) {
            throw new \Pley\Exception\Coupon\CouponDisabledException($coupon, $user);
        }
        if ($coupon->getSubscriptionId() !== null
            && $coupon->getSubscriptionId() != $subscriptionId) {
                throw new \Pley\Exception\Coupon\CouponSubscriptionMismatchException($coupon, $user, $subscriptionId);
        }
        if ($coupon->isExpired()) {
            throw new \Pley\Exception\Coupon\CouponExpiredException($coupon, $user);
        }
        if ($coupon->getMaxUsages() !== null && $coupon->getMaxUsages() !== 0
            && $this->_userCouponRedemptionDao->getRedemptionsCount($coupon) >= $coupon->getMaxUsages()) {
                throw new \Pley\Exception\Coupon\CouponMaxUsagesExceededException($coupon, $user);
        }
        if ($coupon->getMinBoxes() !== null
            && $coupon->getMinBoxes() > $this->_subscriptionManager->getSubscriptionBoxCount($subscriptionId, $paymentPlanId)) {
                throw new \Pley\Exception\Coupon\CouponPaymentPlanBoxException(
                    $coupon, $user, $subscriptionId, $paymentPlanId
                );
        }
        if ($this->_userCouponRedemptionDao->getRedemptionsPerUser($coupon, $user) >= $coupon->getUsagesPerUser()) {
            throw new \Pley\Exception\Coupon\CouponMaxUsagesPerUserExceededException($coupon, $user);
        }
        return $coupon;
    }
}
