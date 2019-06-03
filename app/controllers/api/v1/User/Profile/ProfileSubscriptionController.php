<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace api\v1\User\Profile;

use \Pley\Config\ConfigInterface as Config;
use Pley\Entity\Gift\Gift;
use Pley\Entity\Profile\ProfileSubscription;
use Pley\Entity\Subscription\Subscription;
use Pley\Enum\SubscriptionStatusEnum;
use \Pley\Mail\AbstractMail as Mail;

/**
 * The <kbd>ProfileSubscriptionController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class ProfileSubscriptionController extends \api\v1\BaseAuthController
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Mail\AbstractMail */
    protected $_mail;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionMgr;
    /** @var \Pley\User\UserSubscriptionManager */
    protected $_userSubscriptionMgr;
    /** @var \Pley\Dao\Subscription\SubscriptionDao */
    protected $_subscriptionDao;
    /** @var \Pley\Dao\User\UserProfileDao */
    protected $_userProfileDao;
    /** @var \Pley\Dao\User\UserAddressDao */
    protected $_userAddressDao;
    /** @var \Pley\Dao\Payment\UserPaymentMethodDao */
    protected $_userPaymentMethodDao;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_paymentPlanDao;
    /** @var \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao */
    protected $_vendorPaymentPlanDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionPlanDao */
    protected $_profileSubsPlanDao;
    /** @var \Pley\Dao\Gift\GiftDao */
    protected $_giftDao;
    /** @var \Pley\Dao\Gift\GiftPriceDao */
    protected $_giftPriceDao;
    /** @var \Pley\Payment\PaymentManagerFactory */
    protected $_paymentManagerFactory;
    /** @var \Pley\Coupon\CouponManager */
    protected $_couponManager;
    /** @var \Pley\User\UserManager */
    protected $_userManager;
    /** @var \Pley\Price\PriceManager */
    protected $_priceManager;

    public function __construct(Config $config, Mail $mail,
            \Pley\Db\AbstractDatabaseManager $dbManager,
            \Pley\Subscription\SubscriptionManager $subscriptionMgr,
            \Pley\User\UserSubscriptionManager $userSubscriptionMgr,
            \Pley\Dao\Subscription\SubscriptionDao $subscriptionDao,
            \Pley\Dao\User\UserProfileDao $userProfileDao,
            \Pley\Dao\User\UserAddressDao $userAddressDao,
            \Pley\Dao\Payment\UserPaymentMethodDao $userPaymentMethodDao,
            \Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
            \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao,
            \Pley\Dao\Profile\ProfileSubscriptionPlanDao $profileSubsPlanDao,
            \Pley\Dao\Gift\GiftDao $giftDao,
            \Pley\Dao\Gift\GiftPriceDao $giftPriceDao,
            \Pley\Payment\PaymentManagerFactory $paymentManagerFactory,
            \Pley\Coupon\CouponManager $couponManager,
            \Pley\User\UserManager $userManager,
            \Pley\Price\PriceManager $priceManager
    )
    {
        parent::__construct();
        
        $this->_config              = $config;
        $this->_mail                = $mail;
        $this->_dbManager           = $dbManager;
        $this->_subscriptionMgr     = $subscriptionMgr;
        $this->_userSubscriptionMgr = $userSubscriptionMgr;

        $this->_subscriptionDao      = $subscriptionDao;
        $this->_userProfileDao       = $userProfileDao;
        $this->_userAddressDao       = $userAddressDao;
        $this->_userPaymentMethodDao = $userPaymentMethodDao;
        $this->_paymentPlanDao       = $paymentPlanDao;
        $this->_vendorPaymentPlanDao = $vendorPaymentPlanDao;
        $this->_profileSubsDao       = $profileSubsDao;
        $this->_profileSubsPlanDao   = $profileSubsPlanDao;
        $this->_giftDao              = $giftDao;
        $this->_giftPriceDao         = $giftPriceDao;

        $this->_paymentManagerFactory = $paymentManagerFactory;
        $this->_couponManager       = $couponManager;
        $this->_userManager          = $userManager;

        $this->_priceManager = $priceManager;
    }

    // POST /user/profile/subscription/paid 
    public function addPaid()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();

        $validationRules = [
            'subscriptionId' => 'required|integer',
            'paymentPlanId'  => 'required|integer',
            'profileId'      => 'required|integer',
            'addressId'      => 'required|integer',
        ];
        \ValidationHelper::validate($json, $validationRules);

        $subscriptionId = $json['subscriptionId'];
        $paymentPlanId = $json['paymentPlanId'];

        // This should not happen unless somebody misconfigured the DB or is someone is trying to
        // hack the API call with incorrect data, that is why we throw a base exception instead of
        // a specialized exception.
        if (!$this->_userSubscriptionMgr->isCompatibleSubscription($subscriptionId, $paymentPlanId)) {
            throw new \Exception('Incompatible Payment Plan for Subscription');
        }

        $userProfile = $this->_userProfileDao->find($json['profileId']);
        $userAddress = $this->_userAddressDao->find($json['addressId']);
        \ValidationHelper::entityExist($userProfile, \Pley\Entity\User\UserProfile::class);
        \ValidationHelper::entityExist($userAddress, \Pley\Entity\User\UserAddress::class);

        // This is just a validation to make sure the data supplied is intrinsically related.
        if ($userProfile->getUserId() != $this->_user->getId() || $userAddress->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }
        //load and validate coupon if code has been provided
        $coupon = null;
        $couponCode = (isset($json['couponCode'])) ? $json['couponCode'] : null;
        if ($couponCode) {
            $coupon = $this->_couponManager->validateCouponCode(
                $couponCode, $this->_user, $subscriptionId, $paymentPlanId
            );
        }
        
        // Retrieving the default payment method
        $paymentMethod = $this->_userPaymentMethodDao->find($this->_user->getDefaultPaymentMethodId());
        
        // Creating the Paid subscription and getting the list of Items added as a result
        $newSubsResult = $this->_userSubscriptionMgr->addPaidSubscription(
            $this->_user, $userProfile, $paymentMethod, $subscriptionId, $paymentPlanId, $userAddress, $coupon
        );
        
        $this->_triggerNewSubscriptionEvent($newSubsResult);
        
        return \Response::json(['success' => true]);
    }

    // POST /user/profile/subscription/gift
    public function addGift()
    {
        \RequestHelper::checkPostRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();

        \ValidationHelper::validate($json, [
            'token' => 'required|string',
            'profileId' => 'required|integer',
            'addressId' => 'required|integer',
        ]);

        $userProfile = $this->_userProfileDao->find($json['profileId']);
        $userAddress = $this->_userAddressDao->find($json['addressId']);
        $gift = $this->_giftDao->findByToken(strtolower($json['token']));
        \ValidationHelper::entityExist($userProfile, \Pley\Entity\User\UserProfile::class);
        \ValidationHelper::entityExist($userAddress, \Pley\Entity\User\UserAddress::class);
        \ValidationHelper::entityExist($gift, \Pley\Entity\Gift\Gift::class);

        // This is just a validation to make sure the data supplied is intrinsically related.
        if ($userProfile->getUserId() != $this->_user->getId() || $userAddress->getUserId() != $this->_user->getId()) {
            throw new \Exception('Mismatching relationship');
        }

        if ($gift->isRedeemed()) {
            throw new \Pley\Exception\Gift\GiftRedeemedException($this->_user, $gift);
        }
        
        $newSubsResult = $this->_userSubscriptionMgr->addGiftSubscription(
            $this->_user, $userProfile, $gift, $userAddress
        );

        $this->_triggerNewSubscriptionEvent($newSubsResult);
        $this->_sendGiftRedeemedEmail($gift);

        return \Response::json(['success' => true]);
    }

    // PUT /user/profile/subscription/{intId}/address
    public function swapAddress($profileSubsId)
    {
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();
        \ValidationHelper::validate($json, ['addressId' => 'required|integer']);
        
        $profileSubscription = $this->_profileSubsDao->find($profileSubsId);
        $userAddress         = $this->_userAddressDao->find($json['addressId']);

        \ValidationHelper::entityExist($profileSubscription, \Pley\Entity\Profile\ProfileSubscription::class);
        \ValidationHelper::entityExist($userAddress, \Pley\Entity\User\UserAddress::class);
        
        if ($profileSubscription->getUserId() != $this->_user->getId()
                || $userAddress->getUserId() != $this->_user->getId()) {
            throw new \Exception('Subscription or Address does not belong to User ' . $this->_user->getId());
        }
        
        // Update reference only if a new address is supplied, otherwise no need to update
        if ($userAddress->getId() != $profileSubscription->getUserAddressId()) {
            $profileSubscription->setUserAddressId($userAddress->getId());
            $this->_profileSubsDao->save($profileSubscription);
        }
        
        return \Response::json(['success' => true]);
    }

    // GET /user/profile/subscription/{intId}/autorenew-stop
    public function getDetailsForAutoRenewStop($profileSubsId)
    {
        \RequestHelper::checkGetRequest();

        $profileSubscription = $this->_profileSubsDao->find($profileSubsId);

        $this->_validateForAutoRenewStop($profileSubscription);

        $subscription = $this->_subscriptionDao->find($profileSubscription->getSubscriptionId());
        $profile = $this->_userProfileDao->find($profileSubscription->getUserProfileId());

        $subsPlan = $this->_profileSubsPlanDao->findLastByProfileSubscription($profileSubsId);
        $paymentPlan = $this->_paymentPlanDao->find($subsPlan->getPaymentPlanId());

        $paymentManager = $this->_paymentManagerFactory->getManager($subsPlan->getVPaymentSystemId());
        $paymentSubscription = $paymentManager->getSubscription($this->_user, $subsPlan);

        $vendorPaymentPlan = $this->_vendorPaymentPlanDao->findByVendorPaymentPlanId($subsPlan->getVPaymentPlanId());

        $arrayResponse = [
            'subscription' => [
                'id' => $subscription->getId(),
                'name' => $subscription->getName(),
                'period' => $subscription->getPeriod(),
                'periodUnit' => $subscription->getPeriodUnit(),
                'status' => $profileSubscription->getStatus(),
            ],
            'profile' => [
                'id' => $profile->getId(),
                'firstName' => $profile->getFirstName(),
                'lastName' => $profile->getLastName(),
                'birthDate' => $profile->getBirthDate(),
                'gender' => $profile->getGender(),
                'picture' => $profile->getPicture(),
            ],
            'paymentPlan' => [
                'period' => $paymentPlan->getPeriod(),
                'periodUnit' => $paymentPlan->getPeriodUnit(),
                'price' => [
                    'total' => $this->_priceManager->toUserCurrency($vendorPaymentPlan->getTotal(), $this->_user),
                    'period' => $this->_priceManager->toUserCurrency($vendorPaymentPlan->getUnitPrice(), $this->_user),
                    'shipping' => $this->_priceManager->toUserCurrency($vendorPaymentPlan->getShippingPrice(), $this->_user)
                ],
                'base_currency_price' => [
                    'total' => $this->_priceManager->toBaseCurrency($vendorPaymentPlan->getTotal()),
                    'period' =>$this->_priceManager->toBaseCurrency($vendorPaymentPlan->getUnitPrice()),
                    'shipping' => $this->_priceManager->toBaseCurrency($vendorPaymentPlan->getShippingPrice())
                ],
                'nextCharge' => date('Y-m-d', $paymentSubscription->getPeriodDateEnd()),
            ]
        ];

        return \Response::json($arrayResponse);
    }

    // PUT /user/profile/subscription/{intId}/autorenew-stop
    public function autoRenewStop($profileSubsId)
    {
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();

        $json = \Input::json()->all();

        $validationRules = ['password' => 'required|string'];
        \ValidationHelper::validate($json, $validationRules);

        \ValidationHelper::validateCredentials($this->_user, $json['password']);

        $profileSubscription = $this->_profileSubsDao->find($profileSubsId);
        $this->_validateForAutoRenewStop($profileSubscription);

        $subscription = $this->_subscriptionDao->find($profileSubscription->getSubscriptionId());
        $userProfile = $this->_userProfileDao->find($profileSubscription->getUserProfileId());

        $that = $this;
        $this->_dbManager->transaction(function () use ($that, $profileSubscription) {
            $that->_autoRenewStopClosure($profileSubscription);
        });

        // Sending notification email
        $mailOptions = ['subjectName' => $subscription->getName()];
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($this->_user);
        $mailTagCollection->addEntity($subscription);
        $mailTagCollection->addEntity($userProfile);

        $mailUserTo = \Pley\Mail\MailUser::withUser($this->_user);

        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::SUBSCRIPTION_CANCEL;
        $this->_mail->send($mailTemplateId, $mailTagCollection, $mailUserTo, $mailOptions);

        return \Response::json(['success' => true]);
    }
    
    // PUT /user/profile/subscription/{intId}/reactivate
    public function reactivate($profileSubsId)
    {
        \RequestHelper::checkPutRequest();
        
        $profileSubscription = $this->_profileSubsDao->find($profileSubsId);
        $subscription = $this->_subscriptionMgr->getSubscription($profileSubscription->getSubscriptionId());

        $this->_valiadteReactivationState($profileSubscription);
        
        // If the subscription is already cancelled, we need to create a new subscription plan in a 
        // similar fashion to when creating a new subscription for the first time.
        if ($profileSubscription->getStatus() == \Pley\Enum\SubscriptionStatusEnum::CANCELLED) {
            // For now the reactivation of a cancelled subscription will be done on whatever plan they
            // previously selected due to the time constraints on development.
            $profileSubsPlan = $this->_profileSubsPlanDao->findLastByProfileSubscription($profileSubscription->getId());
            $paymentPlan     = $this->_paymentPlanDao->find($profileSubsPlan->getPaymentPlanId());
            
            $this->_reactivateCancelled($profileSubscription, $paymentPlan);
            
        // if it is still active but with auto-renew stop flag, then we just need to reflag it as 
        // auto-renew = true
        } else {
            $this->_reactivateAutoRenewStop($profileSubscription);
        }

        $this->_triggerSubscriptionReactivateEvent($subscription);

        return \Response::json(['success' => true]);
    }

    // GET /user/profile/subscription/{intId}/skip-box-info
    public function skipBoxInfo($profileSubsId)
    {
        \RequestHelper::checkGetRequest();
        $profileSubs = $this->_profileSubsDao->find($profileSubsId);

        $subscription = $this->_subscriptionMgr->getSubscription($profileSubs->getSubscriptionId());
        $shipmentsCollection = $this->_userManager->getSubscriptionShipmentCollection($profileSubs);
        $canSkipABox        = $this->_subscriptionMgr->canPauseProfileSubscription($profileSubs);
        $pendingShipmentsCollection = $shipmentsCollection->getPendingList();
        if(count($pendingShipmentsCollection)){
            $firstPendingShipment = $pendingShipmentsCollection[0];
            
            $currentPeriodDefGrp = $this->_subscriptionMgr->getSubscriptionDates($subscription, $firstPendingShipment->getScheduleIndex());
            $currentDeliveryStartTime = $currentPeriodDefGrp->getDeliveryStartPeriodDef()->getTimestamp();
            $currentDeliveryEndTime   = $currentPeriodDefGrp->getDeliveryEndPeriodDef()->getTimestamp();
            
            $nextPeriodDefGrp      = $this->_subscriptionMgr->getSubscriptionDates($subscription, $firstPendingShipment->getScheduleIndex() + 1);
            $nextDeliveryStartTime = $nextPeriodDefGrp->getDeliveryStartPeriodDef()->getTimestamp();
            $nextDeliveryEndTime   = $nextPeriodDefGrp->getDeliveryEndPeriodDef()->getTimestamp();

        // If there are no pending shipments, the customer has received their last box and waiting
        // for recurring charge to generate new shipments, so we have to calculate where the next delivery
        // would be and the where they would be skipping to.
        } else {
            $activeIdx = $this->_subscriptionMgr->getActivePeriodIndex($subscription);
            
            // The current delivery would be on the next period after the current
            $currentPeriodDefGrp = $this->_subscriptionMgr->getSubscriptionDates($subscription, $activeIdx + 1);
            $currentDeliveryStartTime = $currentPeriodDefGrp->getDeliveryStartPeriodDef()->getTimestamp();
            $currentDeliveryEndTime   = $currentPeriodDefGrp->getDeliveryEndPeriodDef()->getTimestamp();
            
            $nextPeriodDefGrp      = $this->_subscriptionMgr->getSubscriptionDates($subscription, $activeIdx + 2);
            $nextDeliveryStartTime = $nextPeriodDefGrp->getDeliveryStartPeriodDef()->getTimestamp();
            $nextDeliveryEndTime   = $nextPeriodDefGrp->getDeliveryEndPeriodDef()->getTimestamp();
        }
        $arrayResponse = [
            'subscription' => [
                'name'        => $subscription->getName(),
                'description' => $subscription->getDescription(),
                'period'      => $subscription->getPeriod(),
                'periodUnit'  => $subscription->getPeriodUnit(),
            ],
            'skipBoxInfo' =>[  
                'canSkipBox' => $canSkipABox,
                'currentBoxScheduleMap' => [
                    'deliveryTimeStart' => $currentDeliveryStartTime,
                    'deliveryTimeEnd'   => $currentDeliveryEndTime,
                ],
                'nextBoxScheduleMap' => [
                    'deliveryTimeStart' => $nextDeliveryStartTime,
                    'deliveryTimeEnd'   => $nextDeliveryEndTime,
                ],
            ]
        ];
        return \Response::json($arrayResponse);
    }

    // PUT /user/profile/subscription/{intId}/skip-box
    public function skipBox($profileSubsId)
    {
        \RequestHelper::checkPutRequest();

        $profileSubscription = $this->_profileSubsDao->find($profileSubsId);

        $that = $this;
        $this->_dbManager->transaction(function () use ($that, $profileSubscription) {
            $that->_skipABoxClosure($profileSubscription);
        });

        $this->_sendBoxSkippedEmail($profileSubscription);
        return \Response::json(['success' => true]);
    }

    /**
     * Triggers the Event related to creating a new subscription
     * @param \Pley\User\NewSubscriptionResult $newSubsResult
     */
    private function _triggerNewSubscriptionEvent(\Pley\User\NewSubscriptionResult $newSubsResult)
    {
        \Event::fire(\Pley\Enum\EventEnum::SUBSCRIPTION_CREATE, [
            'user'                  => $this->_user,
            'newSubscriptionResult' => $newSubsResult,
        ]);
    }
    
    /**
     * Sends the gift redemption email to the gift sender after the subscription has been added.
     * @param \Pley\Entity\Gift\Gift $gift
     */
    private function _sendGiftRedeemedEmail(Gift $gift)
    {
        $giftPrice    = $this->_giftPriceDao->find($gift->getGiftPriceId());
        $subscription = $this->_subscriptionMgr->getSubscription($gift->getSubscriptionId());
        $paymentPlan  = $this->_paymentPlanDao->find($giftPrice->getEquivalentPaymentPlanId());

        $mailOptions = ['subjectName' => ucfirst($gift->getToFirstName()) . ' ' . ucfirst($gift->getToLastName())];
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($gift);
        $mailTagCollection->addEntity($giftPrice);
        $mailTagCollection->addEntity($subscription);
        $mailTagCollection->addEntity($paymentPlan);
        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::GIFT_REDEEMED;

        $displayName = $gift->getFromFirstName() . ' ' . $gift->getFromLastName();
        $mailUserTo = new \Pley\Mail\MailUser($gift->getFromEmail(), $displayName);

        $this->_mail->send($mailTemplateId, $mailTagCollection, $mailUserTo, $mailOptions);
    }

    /**
     * Sends a skipped box notification email to a user.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubscription
     * @throws \Exception
     */
    private function _sendBoxSkippedEmail(ProfileSubscription $profileSubscription)
    {
        $profileSubs = $this->_profileSubsDao->find($profileSubscription->getId());
        $subscription = $this->_subscriptionMgr->getSubscription($profileSubscription->getSubscriptionId());

        $shipmentsCollection = $this->_userManager->getSubscriptionShipmentCollection($profileSubs);
        $pendingShipmentsCollection = $shipmentsCollection->getPendingList();
        if(!count($pendingShipmentsCollection)){
            throw new \Exception("No pending shipments for profile subscription.");
        }

        $firstPendingShipment = $pendingShipmentsCollection[0];
        $activePeriodDef = $this->_subscriptionMgr->getPeriodDefinitionForIndex($subscription, $firstPendingShipment->getScheduleIndex());
        $previousPeriodDef = $this->_subscriptionMgr->getPeriodDefinitionForIndex($subscription, $firstPendingShipment->getScheduleIndex() - 1);
        $skippedBoxMonth = date('F', $previousPeriodDef->getTimestamp());
        $nextBoxMonth = date('F', $activePeriodDef->getTimestamp());


        $mailOptions = ['subjectName' => $skippedBoxMonth];
        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);

        $mailTagCollection->addEntity($subscription);
        $mailTagCollection->addEntity($this->_user);
        $mailTagCollection->setCustom('skippedBoxMonth', $skippedBoxMonth);
        $mailTagCollection->setCustom('nextBoxMonth', $nextBoxMonth);
        $mailTemplateId = \Pley\Enum\Mail\MailTemplateEnum::BOX_SKIPPED;

        $displayName = $this->_user->getFirstName() . ' ' . $this->_user->getFirstName();
        $mailUserTo = new \Pley\Mail\MailUser($this->_user->getEmail(), $displayName);

        $this->_mail->send($mailTemplateId, $mailTagCollection, $mailUserTo, $mailOptions);
    }

    private function _validateForAutoRenewStop(\Pley\Entity\Profile\ProfileSubscription $profileSubscription)
    {
        // Validation of ownership
        if ($this->_user->getId() != $profileSubscription->getUserId()) {
            throw new \Exception("Incorrect relationship of User to ProfileSubscription");
        }

        // Validation of cancelability
        $invalidCancelStatusList = [
            \Pley\Enum\SubscriptionStatusEnum::CANCELLED,
            \Pley\Enum\SubscriptionStatusEnum::GIFT,
        ];
        if (in_array($profileSubscription->getStatus(), $invalidCancelStatusList)
            || !$profileSubscription->isAutoRenew()
        ) {
            throw new \Pley\Exception\User\Profile\NonCancelableSubscriptionException($this->_user, $profileSubscription);
        }
    }

    /**
     * Closure method to stop the auto-renew status of the supplied subscription and update the
     * payment plan as well as the subscription flags, as a transaction.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubscription
     */
    private function _autoRenewStopClosure(\Pley\Entity\Profile\ProfileSubscription $profileSubscription)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        $subscriptionPlan = $this->_profileSubsPlanDao->findLastByProfileSubscription($profileSubscription->getId());
        $paymentManager = $this->_paymentManagerFactory->getManager($subscriptionPlan->getVPaymentSystemId());

        // Stops the AutoRenew on the subscription and updates the plan reference with the last day
        // the subscription is active. (end of subscription period date)
        $paymentManager->stopSubscriptionAutoRenew(
            $this->_user, $subscriptionPlan, \Pley\Enum\SubscriptionCancelSourceEnum::USER
        );

        $unshippedNum = $this->_userSubscriptionMgr->getTotalNotShippedForSubscription($subscriptionPlan->getProfileSubscriptionId());

        /*
         * In case if there is still future shipments scheduled for this subscription - assign them a pending cancellation
         * status as we still need to process shipments for it
         * */

        $cancellationStatus = ($unshippedNum > 0) ? \Pley\Enum\SubscriptionStatusEnum::STOPPED : \Pley\Enum\SubscriptionStatusEnum::CANCELLED;
        $subscriptionPlan->setStatus($cancellationStatus);

        $profileSubscription->updateWithSubscriptionPlan($subscriptionPlan);

        $this->_profileSubsPlanDao->save($subscriptionPlan);
        $this->_profileSubsDao->save($profileSubscription);
    }

    /**
     * Closure method to skip a box within a subscription.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubscription
     */
    private function _skipABoxClosure(\Pley\Entity\Profile\ProfileSubscription $profileSubscription)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        $profileSubsPlan = $this->_profileSubsPlanDao->findLastByProfileSubscription($profileSubscription->getId());
        $subscription = $this->_subscriptionDao->find($profileSubscription->getSubscriptionId());

        $paymentManager = $this->_paymentManagerFactory->getManager($profileSubsPlan->getVPaymentSystemId());
        $paymentSubscription = $paymentManager->getSubscription($this->_user, $profileSubsPlan);

        $paymentSubscriptionChargeDate = $paymentSubscription->getPeriodDateEnd();
        $currentPaymentSubscriptionPeriodIdx = 0;

        $this->_validateSubscriptionSkip($profileSubscription);

        $subscriptionPeriodsList = $this->_subscriptionMgr->getFullPeriodDefinitionList($subscription);
        foreach ($subscriptionPeriodsList as $subscriptionPeriod){
            /**
             * Iterating over the existing subscription periods to find a period index,
             * which corresponds to active subscription charge date
             * We need to know this, because we need to shift Stripe charge date to ONE SHIPPING PERIOD
             * Which will be equal to $currentPaymentSubscriptionPeriodIdx + 1
             **/
            $periodChargeDay = \Pley\Util\Time\PeriodDefinition::toTimestamp(
                    $subscriptionPeriod->getPeriodUnit(),
                    $subscriptionPeriod->getPeriod(),
                    $subscription->getChargeDay(),
                    $subscriptionPeriod->getYear());
            if($periodChargeDay >= $paymentSubscriptionChargeDate){
                /** Found a corresponding period index**/
                $currentPaymentSubscriptionPeriodIdx = $subscriptionPeriod->getIndex();
                break;
            }
        }

        $nextPeriod = $subscriptionPeriodsList[$currentPaymentSubscriptionPeriodIdx + 1];

        $nextPeriodChargeDateTime = \Pley\Util\Time\PeriodDefinition::toTimestamp(
            $nextPeriod->getPeriodUnit(),
            $nextPeriod->getPeriod(),
            $subscription->getChargeDay(),
            $nextPeriod->getYear()
        );

        /** use $nextPeriodChargeDateTime as the subscription pause end date */
        $paymentManager->subscriptionPause($this->_user, $profileSubsPlan, $nextPeriodChargeDateTime);

        $this->_userSubscriptionMgr->skipProfileSubscriptionShipments($profileSubscription, $subscription);

        $profileSubscription->setStatus(SubscriptionStatusEnum::PAUSED);
        $this->_profileSubsDao->save($profileSubscription);
    }
    
    /**
     * Checks if the supplied Profile subscription is in a state where it can be reactivated.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubscription
     * @return boolean
     * @throws \Pley\Exception\User\Profile\InvalidSubscriptionReactivationStateException
     */
    private function _valiadteReactivationState(\Pley\Entity\Profile\ProfileSubscription $profileSubscription)
    {
        // Validation of ownership
        if ($this->_user->getId() != $profileSubscription->getUserId()) {
            throw new \Exception("Incorrect relationship of User to ProfileSubscription");
        }
        
        // If subscription is already cancelled, we can reactivate
        if ($profileSubscription->getStatus() == \Pley\Enum\SubscriptionStatusEnum::CANCELLED) {
            return true;
        }
        
        // Alternatively, if it is still active but flagged as stop auto-renew, we can also reactivate
        if ($profileSubscription->getStatus() == \Pley\Enum\SubscriptionStatusEnum::ACTIVE
                && !$profileSubscription->isAutoRenew()) {
            return true;
        }
        
        // Any other status or flag cannot be reactivated (i.e. Gifts, Past Due, Active+AutoRenew)
        throw new \Pley\Exception\User\Profile\InvalidSubscriptionReactivationStateException(
            $this->_user, $profileSubscription
        );
    }

    private function _validateSubscriptionSkip(\Pley\Entity\Profile\ProfileSubscription $profileSubscription){
        // Validation of ownership
        if ($this->_user->getId() != $profileSubscription->getUserId()) {
            throw new \Exception("Incorrect relationship of User to ProfileSubscription");
        }
        //Status validation
        if ($profileSubscription->getStatus() !== SubscriptionStatusEnum::ACTIVE) {
            throw new \Exception("Invalid subscription status. Subscription should be active.");
        }
    }
    
    /**
     * Provides with the functionality to set an Active subscription that has been flagged to not
     * auto renew, back to fully active (with auto-renew enabled)
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubscription
     */
    private function _reactivateAutoRenewStop(\Pley\Entity\Profile\ProfileSubscription $profileSubscription)
    {
        $this->_userSubscriptionMgr->reactivateAutoRenew($this->_user, $profileSubscription);
    }
    
    /**
     * Provides with the functionality to restart a Cancelled subscription.
     * This behaves like setting a subscription for the first time where we need to do an initial
     * charge and set a trial period for the following charge.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubscription
     * @param \Pley\Entity\Payment\PaymentPlan         $paymentPlan
     */
    private function _reactivateCancelled(
            \Pley\Entity\Profile\ProfileSubscription $profileSubscription,
            \Pley\Entity\Payment\PaymentPlan $paymentPlan)
    {
        $paymentMethod = $this->_userPaymentMethodDao->find($this->_user->getDefaultPaymentMethodId());
        
        $nextSequenceItem = $this->_userSubscriptionMgr->changeSubscriptionPlan(
            $this->_user, $profileSubscription, $paymentMethod, $paymentPlan
        );
    }

    private function _triggerSubscriptionReactivateEvent(
        \Pley\Entity\Subscription\Subscription $subscription)
    {
        \Event::fire(\Pley\Enum\EventEnum::SUBSCRIPTION_REACTIVATE, [
            'user' => $this->_user,
            'subscription' => $subscription,
        ]);
    }
    
}
