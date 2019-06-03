<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\User;

use Pley\Entity\Profile\ProfileSubscriptionPlan;
use Pley\Entity\Profile\ProfileSubscriptionShipment;
use \Pley\Entity\Profile\QueueItem;
use \Pley\Entity\Profile\ProfileSubscription;
use \Pley\Entity\Subscription\Subscription;
use Pley\Enum\PaymentSystemEnum;
use \Pley\Util\Time\DateTime;

/**
 * The <kbd>UserSubscriptionManager</kbd> class handles operations around Subscriptions for a user.
 * <p>Operations include but are not limited to:
 * <ul>
 *   <li>Charging for the subscription.</li>
 *   <li>Setting the initial date for the recurring payment.</li>
 *   </li>Cancelling (stop auto-renew) a subscription.</li>
 * </ul>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.User
 * @subpackage Subscription
 */
class UserSubscriptionManager
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionMgr;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_paymentPlanDao;
    /** @var \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao */ 
    protected $_vendorPaymentPlanDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionPlanDao */
    protected $_profileSubsPlanDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionTransactionDao */
    protected $_profileSubsTransacDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionShipmentDao */
    protected $_profileSubsShipDao;
    /** @var \Pley\Dao\Gift\GiftDao */
    protected $_giftDao;
    /** @var \Pley\Dao\Gift\GiftPriceDao */
    protected $_giftPriceDao;
    /** @var \Pley\Payment\PaymentManagerFactory */
    protected $_paymentManagerFactory;
    /** @var \Pley\Coupon\CouponManager */
    protected $_couponManager;
    /** @var \Pley\Referral\RewardManager */
    protected $_rewardManager;
    /** @var \Pley\Billing\PaypalManager */
    protected $_paypalManager;
    
    public function __construct(
            \Pley\Db\AbstractDatabaseManager $dbManager,
            \Pley\Subscription\SubscriptionManager $subscriptionMgr,
            \Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
            \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao,
            \Pley\Dao\Profile\ProfileSubscriptionPlanDao $profileSubsPlanDao,
            \Pley\Dao\Profile\ProfileSubscriptionTransactionDao $profileSubsTransacDao,
            \Pley\Dao\Profile\ProfileSubscriptionShipmentDao $profileSubsShipDao,
            \Pley\Dao\Gift\GiftDao $giftDao,
            \Pley\Dao\Gift\GiftPriceDao $giftPriceDao,
            \Pley\Payment\PaymentManagerFactory $paymentManagerFactory,
            \Pley\Coupon\CouponManager $couponManager,
            \Pley\Referral\RewardManager $rewardManager,
            \Pley\Billing\PaypalManager $paypalManager)
    {
        $this->_dbManager       = $dbManager;
        $this->_subscriptionMgr = $subscriptionMgr;

        $this->_paymentPlanDao        = $paymentPlanDao;
        $this->_vendorPaymentPlanDao  = $vendorPaymentPlanDao;
        $this->_profileSubsDao        = $profileSubsDao;
        $this->_profileSubsPlanDao    = $profileSubsPlanDao;
        $this->_profileSubsTransacDao = $profileSubsTransacDao;
        $this->_profileSubsShipDao    = $profileSubsShipDao;
        $this->_giftDao               = $giftDao;
        $this->_giftPriceDao          = $giftPriceDao;

        $this->_paymentManagerFactory = $paymentManagerFactory;
        $this->_couponManager         = $couponManager;
        $this->_rewardManager         = $rewardManager;
        $this->_paypalManager         = $paypalManager;
    }
    
    /**
     * Returns whether the Subscription and the Payment plan are compatible
     * @param int $subscriptionId
     * @param int $paymentPlanId
     * @return boolean <p>Returns <kbd>TRUE</kbd> if period units match and peridiocity is compatible,
     *      <kbd>FALSE</kbd> otherwise.
     */
    public function isCompatibleSubscription($subscriptionId, $paymentPlanId)
    {
        $paymentPlan  = $this->_paymentPlanDao->find($paymentPlanId);
        $subscription = $this->_subscriptionMgr->getSubscription($subscriptionId);

        // Checking that the Period Unit (Month, Week) are the same in both
        $isSamePeriodUnit        = $subscription->getPeriodUnit() == $paymentPlan->getPeriodUnit();
        
        // Checking that the Plan period is compatible with that of the subscription
        // i.e
        // * Plan 2 month - Subs 2 month = compatible (equates to 1 period)
        // * Plan 6 month - Subs 2 month = compatible (equates to 3 periods)
        // * Plan 1 month - Subs 2 month = BAD (payment is monthly but items delivered bimonthly)
        $isCompatiblePeriodicity = $paymentPlan->getPeriod() % $subscription->getPeriod() == 0;
        $isSubscriptionIdMatch = $paymentPlan->getSubscriptionId() == $subscriptionId;

        return $isSamePeriodUnit && $isCompatiblePeriodicity && $isSubscriptionIdMatch;
    }
    
    /**
     * Adds a new Recurring Subscription for the suer on the given profile and shipping address.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\User\UserProfile          $profile
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @param int                                    $subscriptionId
     * @param int                                    $paymentPlanId
     * @param \Pley\Entity\User\UserAddress          $address        (Optional)
     * @param \Pley\Entity\Coupon\Coupon             $coupon         (Optional)
     * @return \Pley\User\NewSubscriptionResult Object containing data created for subscription
     */
    public function addPaidSubscription(
            \Pley\Entity\User\User $user, 
            \Pley\Entity\User\UserProfile $profile,
            \Pley\Entity\Payment\UserPaymentMethod $paymentMethod,
            $subscriptionId, 
            $paymentPlanId,
            \Pley\Entity\User\UserAddress $address = null,
            \Pley\Entity\Coupon\Coupon $coupon = null)
    {
        // This should not happen unless somebody misconfigured the DB or is someone is trying to
        // hack the API call with incorrect data, that is why we throw a base exception instead of
        // a specialized exception.
        if (!$this->isCompatibleSubscription($subscriptionId, $paymentPlanId)) {
            throw new \Exception('Incompatible Payment Plan for Subscription');
        }
        
        $that = $this;
        
        $newSubsResult = $this->_dbManager->transaction(
            function() use ($that, $user, $profile, $paymentMethod, $subscriptionId, $paymentPlanId, $address, $coupon) {
                return $that->_addPaidSubscriptionClosure(
                    $user, $profile, $paymentMethod, $subscriptionId, $paymentPlanId, $address, $coupon
                );
            }
        );
        
        return $newSubsResult;
    }

    /**
     * Adds a new PayPal Recurring Subscription for the suer on the given profile and shipping address.
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\User\UserProfile          $profile
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @param int                                    $subscriptionId
     * @param int                                    $paymentPlanId
     * @param \Pley\Entity\User\UserAddress          $address        (Optional)
     * @param \Pley\Entity\Coupon\Coupon             $coupon         (Optional)
     * @return \Pley\User\NewSubscriptionResult Object containing data created for subscription
     */
    public function addPaypalSubscription(
        \Pley\Entity\User\User $user,
        \Pley\Entity\User\UserProfile $profile,
        \Pley\Entity\Payment\UserPaymentMethod $paymentMethod,
        $subscriptionId,
        $paymentPlanId,
        \PayPal\Api\Agreement $agreement,
        \Pley\Entity\User\UserAddress $address = null,
        \Pley\Entity\Coupon\Coupon $coupon = null)
    {
        // This should not happen unless somebody misconfigured the DB or is someone is trying to
        // hack the API call with incorrect data, that is why we throw a base exception instead of
        // a specialized exception.
        if (!$this->isCompatibleSubscription($subscriptionId, $paymentPlanId)) {
            throw new \Exception('Incompatible Payment Plan for Subscription');
        }

        $that = $this;

        $newSubsResult = $this->_dbManager->transaction(
            function() use ($that, $user, $profile, $paymentMethod, $subscriptionId, $paymentPlanId, $agreement, $address, $coupon) {
                return $that->_addPaypalSubscriptionClosure(
                    $user, $profile, $paymentMethod, $subscriptionId, $paymentPlanId, $agreement, $address, $coupon
                );
            }
        );

        return $newSubsResult;
    }
    
    /**
     * Adds a new Non-Recurring Subscription for the user on the given profile and address for the supplied gift.
     * @param \Pley\Entity\User\User        $user
     * @param \Pley\Entity\User\UserProfile $profile
     * @param \Pley\Entity\User\UserAddress $address
     * @param \Pley\Entity\Gift\Gift        $gift
     * @return \Pley\User\NewSubscriptionResult Object containing data created for subscription
     */
    public function addGiftSubscription(
            \Pley\Entity\User\User $user, 
            \Pley\Entity\User\UserProfile $profile,
            \Pley\Entity\Gift\Gift $gift,
            \Pley\Entity\User\UserAddress $address = null)
    {
        $that = $this;
        $newSubsResult = $this->_dbManager->transaction(
            function() use ($that, $user, $profile, $gift, $address) {
                return $that->_addGiftSubscriptionClosure($user, $profile, $gift, $address);
            }
        );
        
        return $newSubsResult;
    }
    
    /** 
     * Retrieves the first recurring charge data, which is the second payment of a subscription
     * as the first payment is always immediate and doesn't necessarily align with the recurring date
     * or the subscription.
     * @param \Pley\Entity\Subscription\Subscription   $subscription
     * @param \Pley\Entity\Payment\PaymentPlan         $paymentPlan
     * @param \Pley\Entity\Subscription\SequenceItem[] $itemSequence
     * @return int
     */
    public function getFirstRecurringChargeDate(
            \Pley\Entity\Subscription\Subscription $subscription, 
            \Pley\Entity\Payment\PaymentPlan $paymentPlan, 
            $itemSequence)
    {
        // The second payment is always respective to when the first item charge date is
        // (This is because it could be that we sold out of the active period item and a user subscribed
        // to start on a future item, so, the payment has to be calculated from that date and not the
        // active [aka current] period)
        $firstItemScheduledChargeDate = $itemSequence[0]->getChargeTime();

        $periodDef = new \Pley\Util\Time\PeriodDefinition(
            $subscription->getPeriodUnit(), 
            DateTime::getPeriod($subscription->getPeriodUnit(), $firstItemScheduledChargeDate),
            DateTime::getDayOfPeriod($subscription->getPeriodUnit(), $firstItemScheduledChargeDate),
            DateTime::dateParts($firstItemScheduledChargeDate)->getYear()
        );
        
        // Creating an iterator based on the payment plan length
        $periodIterator = new \Pley\Util\Time\PeriodIterator(
            $paymentPlan->getPeriodUnit(), $paymentPlan->getPeriod(), $periodDef
        );
        $periodIterator->rewind();
        $periodIterator->current();
        
        // Now moving the pointer to the next period.
        $periodIterator->next();
        
        $nextPeriodDef = $periodIterator->current();
        return $nextPeriodDef->getTimestamp();
    }
    
    /**
     * If the profile is eligible for a new shipment based on their queue and the active period
     * a Shipment will be added, otherwise, nothing happens.
     * @param \Pley\Entity\Profile\ProfileSubscription         $profileSubs
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @return array an array with the following two values if added (nodes can be null if no shipment was added)<br/>
     *      <pre>array(
     *      &nbsp;    \Pley\Entity\Profile\QueueItem,
     *      &nbsp;    \Pley\Entity\Profile\ProfileSubscriptionShipment,
     *      )</pre>
     */
    public function queueShipment(
            \Pley\Entity\Profile\ProfileSubscription $profileSubs,
            \Pley\Entity\Subscription\Subscription $subscription,
            $periodIndex = null)
    {
        // If a period index is not supplied, it defaults to the Active Period
        if ($periodIndex == null) {
            $periodIndex = $this->_subscriptionMgr->getActivePeriodIndex($subscription);
        }
        
        if (!$this->_canQueueShipment($profileSubs, $periodIndex)) {
            return [null, null];
        }
        
        // Now we know we can pull from the queue and add the shipment as the item is valid (purchased
        // and in a valid shipping period)
        $sourceType = null;
        $sourceId   = null;
        if ($profileSubs->getStatus() == \Pley\Enum\SubscriptionStatusEnum::GIFT) {
            $sourceType = \Pley\Enum\Shipping\ShipmentSourceEnum::GIFT;
            $sourceId   = $profileSubs->getGiftId();
            
        } else {
            $lastChargeTransaction = $this->_profileSubsTransacDao->findByLastCharge($profileSubs->getId());
            
            $sourceType = \Pley\Enum\Shipping\ShipmentSourceEnum::BILLING_TRANSACTION;
            $sourceId   = $lastChargeTransaction->getId();
        }
        
        $that = $this;
        list ($nextShipItem, $firstAddedShipment) = $this->_dbManager->transaction(
            function() use ($that, $profileSubs, $periodIndex, $sourceType, $sourceId) {
                return $that->_queueShipmentClosure($profileSubs, $periodIndex, $sourceType, $sourceId);
            }
        );
        
        return [$nextShipItem, $firstAddedShipment];
    }
    
    /**
     * Remove the Reserved items on the Profile Subscription queue and free the respective inventory.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     */
    public function clearReserved(\Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        $that = $this;
        $this->_dbManager->transaction(function() use ($that, $profileSubs) {
            $that->_clearReservedClosure($profileSubs);
        });
    }
    
    /**
     * Moves X number of Reserved Units of the subscription's item queue to Purchased.
     * @param \Pley\Entity\Subscription\SequenceItem $sequenceItem
     */
    public function reservedToPaid(\Pley\Entity\Profile\ProfileSubscription $profileSubs, $count)
    {
        $that = $this;
        $this->_dbManager->transaction(function() use ($that, $profileSubs, $count) {
            $that->_reservedToPaidClosure($profileSubs, $count);
        });
    }
    
    /**
     * Remove the Purchased shipments on the Profile Subscription free the respective inventory.
     * <p>Note: This action is only to be taken as a result of CustomerService performing a full cancel.</p>
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     */
    public function removeNotShipped(\Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        $that = $this;
        $this->_dbManager->transaction(function() use ($that, $profileSubs) {
            $that->_removeNotShippedClosure($profileSubs);
        });
    }
    
    /**
     * Reactivates the auto-renew option on an existing Ative subscription.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     * @throws \Pley\Exception\User\Profile\InvalidSubscriptionReactivationStateException
     */
    public function reactivateAutoRenew(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        // Validation for correct state of subscription
        if ($profileSubs->getStatus() != \Pley\Enum\SubscriptionStatusEnum::ACTIVE
                || $profileSubs->isAutoRenew()) {
            throw new \Pley\Exception\User\Profile\InvalidSubscriptionReactivationStateException(
                $user, $profileSubs
            );
        }

        $that = $this;
        $this->_dbManager->transaction(function() use ($that, $user, $profileSubs) {
            $that->_reactivateAutoRenewClosure($user, $profileSubs);
        });
    }

    /**
     * For a given cancelled subscription it allows to create a new follow up subscription with
     * the specified payment plan.
     * @param \Pley\Entity\User\User                   $user
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     * @param \Pley\Entity\Payment\UserPaymentMethod   $paymentMethod
     * @param \Pley\Entity\Payment\PaymentPlan         $paymentPlan
     * @param \Pley\Entity\User\UserAddress            $address
     * @throws \Pley\Exception\User\Profile\InvalidSubscriptionReactivationStateException
     */
    public function changeSubscriptionPlan(
            \Pley\Entity\User\User $user,
            \Pley\Entity\Profile\ProfileSubscription $profileSubs,
            \Pley\Entity\Payment\UserPaymentMethod $paymentMethod,
            \Pley\Entity\Payment\PaymentPlan $paymentPlan
            )
    {
        // Validation for correct state of subscription
        // Since all boxes are paid ahead of time, a change can only be performed on a cancelled subscription.
        if ($profileSubs->getStatus() != \Pley\Enum\SubscriptionStatusEnum::CANCELLED) {
            throw new \Pley\Exception\User\Profile\InvalidSubscriptionReactivationStateException(
                $user, $profileSubs
            );
        }

        $that = $this;
        $this->_dbManager->transaction(function() use ($that, $user, $profileSubs, $paymentMethod, $paymentPlan) {
            $that->_changeSubscriptionPlanClosure($user, $profileSubs, $paymentMethod, $paymentPlan);
        });
    }

    /**
     * Skip a box within a shipment queue based on the skip strategy defined by a subscription
     * Used for a skip-a-box feature, when pausing a subscription for a one shipping period.
     * Also used when payment is failed and profile subscription becomes UNPAID.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubscription
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @return void
     */
    public function skipProfileSubscriptionShipments(ProfileSubscription $profileSubscription, Subscription $subscription)
    {
        $shipments = $this->_profileSubsShipDao->findNotShipped($profileSubscription->getId());

        switch (\Pley\Enum\SubscriptionSkipMethodEnum::getSubscriptionSkipMethod($subscription)) {
            case \Pley\Enum\SubscriptionSkipMethodEnum::SKIP:
                $itemSeqQueue = $profileSubscription->getItemSequenceQueue();
                $skipQueueItem = $itemSeqQueue[0];

                if ($skipQueueItem->getType() == QueueItem::TYPE_RESERVED) {
                    $this->_subscriptionMgr->freeReservedItem($subscription->getId(), $skipQueueItem->getSequenceIndex());
                }elseif ($skipQueueItem->getType() == QueueItem::TYPE_PURCHASED){
                    $this->_subscriptionMgr->freePurchasedItem($subscription->getId(), $skipQueueItem->getSequenceIndex());

                    foreach ($itemSeqQueue as $queueItem){
                        if($queueItem->getType() === QueueItem::TYPE_RESERVED){
                            //find the first following reserved item in a queue and change it to purchased
                            $queueItem->setType(QueueItem::TYPE_PURCHASED);
                            $this->_subscriptionMgr->reservedToPaidItem($subscription->getId(), $queueItem->getSequenceIndex());
                            break;
                        }
                    }
                }

                foreach ($shipments as $shipment){
                    $shipment->setScheduleIndex($shipment->getScheduleIndex() + 1);
                    $shipment->setItemSequenceIndex($shipment->getItemSequenceIndex() + 1);
                    $this->_profileSubsShipDao->save($shipment);
                }
                array_shift($itemSeqQueue); //remove an item from a profile subscription queue
                $profileSubscription->setItemSequenceQueue($itemSeqQueue);
                $this->_profileSubsDao->save($profileSubscription);
                break;
            case \Pley\Enum\SubscriptionSkipMethodEnum::SHIFT:
                foreach ($shipments as $shipment) {
                    $shipment->setScheduleIndex($shipment->getScheduleIndex() + 1);
                    $this->_profileSubsShipDao->save($shipment);
                }
                break;
        }
    }

    /**
     * Sets shipment's profile subscription status back to ACTIVE
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubscriptionShipment
     * @return void
     */
    public function reactivateProfileSubscription(ProfileSubscriptionShipment $profileSubscriptionShipment)
    {
        $profileSubs = $this->_profileSubsDao->find($profileSubscriptionShipment->getProfileSubscriptionId());
        if ($profileSubs->getStatus() === \Pley\Enum\SubscriptionStatusEnum::PAUSED) {
            $profileSubs->setStatus(\Pley\Enum\SubscriptionStatusEnum::ACTIVE);
            $this->_profileSubsDao->save($profileSubs);
        }
    }

    // ---------------------------------------------------------------------------------------------
    // PRIVATE METHODS -----------------------------------------------------------------------------
    
    /**
     * Helper method to know if a queue item can be added as a shipment to the supplied period index.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     * @param int                                      $periodIndex 
     * @return boolean
     */
    private function _canQueueShipment(\Pley\Entity\Profile\ProfileSubscription $profileSubs, $periodIndex)
    {
        // If the sequence queue is empty, there is nothing to pull to enqueue a shipment
        $itemSeqQueue = $profileSubs->getItemSequenceQueue();
        if (empty($itemSeqQueue)) {
            return false;
        }
        
        $nextShipItem = $itemSeqQueue[0];
        
        // Before we even attempt to do further checks, if the next item in the queue is of reserved
        // type, we cannot pull it until it has been paid for, so we've got nothing to add.
        if ($nextShipItem->getType() == QueueItem::TYPE_RESERVED) {
            return false;
        }
        
        // Now we need to check if the user has an active shipment for the current shippable period
        $existingProfileShipment = $this->_profileSubsShipDao->findByPeriod(
            $profileSubs->getId(), $periodIndex
        );
        
        // If a shipment exists, then we don't need to pull another yet
        if (!empty($existingProfileShipment)) {
            return false;
        }
        
        // Now we know that there is no active shipment and that the next item in the queue is paid
        // for, so we just need to do one more check to see if the item is scheduled for a future shipment
        if ($nextShipItem->getSequenceIndex() > $periodIndex) {
            return false; // The next item is scheduled for a future shipment period
        }
        
        return true;
    }
    
    /**
     * Closure to queue all paid items from the Profile Subscription's queue as a transaction.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     * @param int $periodIndex
     * @param int $sourceType
     * @param int $sourceId
     * @return array an array with the following two values if added (nodes can be null if no shipment was added)<br/>
     *      <pre>array(
     *      &nbsp;    \Pley\Entity\Profile\QueueItem,
     *      &nbsp;    \Pley\Entity\Profile\ProfileSubscriptionShipment,
     *      )</pre>
     */
    private function _queueShipmentClosure(
            \Pley\Entity\Profile\ProfileSubscription $profileSubs, $periodIndex, $sourceType, $sourceId)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $nextShipItem = $firstAddedShipment = null;
        
        // Adding all the Paid Shipments
        $itemSeqQueue       = $profileSubs->getItemSequenceQueue();
        $processingShipItem = $itemSeqQueue[0];
        $scheduleIndex      = $periodIndex;
        while ($processingShipItem->getType() == QueueItem::TYPE_PURCHASED) {
            $shipment = \Pley\Entity\Profile\ProfileSubscriptionShipment::withNew(
                $profileSubs->getUserId(),
                $profileSubs->getUserProfileId(),
                $profileSubs->getId(),
                $profileSubs->getSubscriptionId(),
                $sourceType,
                $sourceId,
                $scheduleIndex,
                $processingShipItem->getSequenceIndex()
            );

            $this->_profileSubsShipDao->save($shipment);
            
            if (!isset($firstAddedShipment)) {
                $firstAddedShipment = $shipment;
                $nextShipItem       = $processingShipItem;
            }
            
            // Removing the processed item from the queue and collect the following one to process
            array_shift($itemSeqQueue);
            
            // For the Gift Cases, once all paid have been queued, there are no reserved boxes so
            // we can't try to get the next item as there is none.
            if (empty($itemSeqQueue)) {
                break;
            }
            
            $processingShipItem = $itemSeqQueue[0];
            $scheduleIndex++;
        }
        
        // Update with the remaining items in the queue.
        $profileSubs->setItemSequenceQueue($itemSeqQueue);
        $this->_profileSubsDao->save($profileSubs);
        
        return [$nextShipItem, $firstAddedShipment];
    }


    /**
     * Get count of scheduled and yet not shipped shipments for a subscription
     * @param int
     * @return int
     */
    public function getTotalNotShippedForSubscription($profileSubscriptionId){
        return count($this->_profileSubsShipDao->findNotShipped($profileSubscriptionId));
    }
    
    /**
     * Closure to add a new Recurring Subscription for the user on a given profile and address, make
     * the initial charge and set up the future recurring subscription, all as a transaction.
     * 
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\User\UserProfile          $profile
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @param int                                    $subscriptionId
     * @param int                                    $paymentPlanId
     * @param \Pley\Entity\User\UserAddress          $address        (Optional)
     * @param \Pley\Entity\Coupon\Coupon             $coupon         (Optional)
     * @return \Pley\User\NewSubscriptionResult Object containing data created for subscription
     */
    private function _addPaidSubscriptionClosure(
            \Pley\Entity\User\User $user, 
            \Pley\Entity\User\UserProfile $profile,
            \Pley\Entity\Payment\UserPaymentMethod $paymentMethod,
            $subscriptionId, 
            $paymentPlanId,
            \Pley\Entity\User\UserAddress $address = null,
            \Pley\Entity\Coupon\Coupon $coupon = null)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        // Validation to make sure that the supplied payment method is the same as the one set as Default.
        if ($user->getDefaultPaymentMethodId() != $paymentMethod->getId()) {
            throw new \Exception('Payment Method supplied is not the Default.');
        }
        
        $subscription = $this->_subscriptionMgr->getSubscription($subscriptionId);
        $paymentPlan  = $this->_paymentPlanDao->find($paymentPlanId);

        // -----------------------------------------------------------------------------------------
        // Creating the base Profile Subscription Entry
        $profileSubs = \Pley\Entity\Profile\ProfileSubscription::withNew(
            $user->getId(), $profile->getId(), $subscription->getId(), $paymentMethod->getId()
        );
        if (isset($address)) { $profileSubs->setUserAddressId($address->getId()); }
        $this->_profileSubsDao->save($profileSubs);
        
        // Now delegating the ret of the actual subscription creation that is common for new subscriptions
        // as well as for change of plans or reactivation after cancelled subscription
        $chargeDescription = '[Pleybox] First charge for ' . $subscription->getName() . ' subscription.';
        $newSubsResult = $this->_createPaidSubscription(
            $user, $profileSubs, $paymentMethod, $subscription, $paymentPlan, $chargeDescription, $coupon
        );

        return $newSubsResult;
    }

    /**
     * Closure to add a new Paypal based Recurring Subscription for the user on a given profile and address, make
     * the initial charge and set up the future recurring subscription, all as a transaction.
     *
     * @param \Pley\Entity\User\User                 $user
     * @param \Pley\Entity\User\UserProfile          $profile
     * @param \Pley\Entity\Payment\UserPaymentMethod $paymentMethod
     * @param int                                    $subscriptionId
     * @param int                                    $paymentPlanId
     * @param \Pley\Entity\User\UserAddress          $address        (Optional)
     * @param \Pley\Entity\Coupon\Coupon             $coupon         (Optional)
     * @return \Pley\User\NewSubscriptionResult Object containing data created for subscription
     */
    private function _addPaypalSubscriptionClosure(
        \Pley\Entity\User\User $user,
        \Pley\Entity\User\UserProfile $profile,
        \Pley\Entity\Payment\UserPaymentMethod $paymentMethod,
        $subscriptionId,
        $paymentPlanId,
        \PayPal\Api\Agreement $agreement,
        \Pley\Entity\User\UserAddress $address = null,
        \Pley\Entity\Coupon\Coupon $coupon = null)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        // Validation to make sure that the supplied payment method is the same as the one set as Default.

        $subscription = $this->_subscriptionMgr->getSubscription($subscriptionId);
        $paymentPlan  = $this->_paymentPlanDao->find($paymentPlanId);

        // -----------------------------------------------------------------------------------------
        // Creating the base Profile Subscription Entry
        $profileSubs = \Pley\Entity\Profile\ProfileSubscription::withNew(
            $user->getId(), $profile->getId(), $subscription->getId(), $paymentMethod->getId()
        );
        if (isset($address)) { $profileSubs->setUserAddressId($address->getId()); }
        $this->_profileSubsDao->save($profileSubs);

        // Now delegating the ret of the actual subscription creation that is common for new subscriptions
        // as well as for change of plans or reactivation after cancelled subscription
        $newSubsResult = $this->_createPaypalSubscription(
            $user, $profileSubs, $paymentMethod, $subscription, $paymentPlan, $agreement, $coupon
        );

        return $newSubsResult;
    }

    /**
     * Closure for a given cancelled subscription it allows to create a new follow up subscription with
     * the specified payment plan.
     * @param \Pley\Entity\User\User                   $user
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     * @param \Pley\Entity\Payment\UserPaymentMethod   $paymentMethod
     * @param \Pley\Entity\Payment\PaymentPlan         $paymentPlan
     * @param \Pley\Entity\User\UserAddress            $address
     * @return \Pley\User\NewSubscriptionResult Object containing data created for subscription
     * @throws \Exception If the supplied profile subscription is not in a cancelled state.
     */
    private function _changeSubscriptionPlanClosure(
            \Pley\Entity\User\User $user,
            \Pley\Entity\Profile\ProfileSubscription $profileSubs,
            \Pley\Entity\Payment\UserPaymentMethod $paymentMethod,
            \Pley\Entity\Payment\PaymentPlan $paymentPlan
            )
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        // Validation to make sure that the supplied payment method is the same as the one set as Default.
        if ($user->getDefaultPaymentMethodId() != $paymentMethod->getId()) {
            throw new \Exception('Payment Method supplied is not the Default.');
        }

        $subscription = $this->_subscriptionMgr->getSubscription($profileSubs->getSubscriptionId());

        // Now delegating the ret of the actual subscription creation that is common for new subscriptions
        // as well as for change of plans or reactivation after cancelled subscription
        $chargeDescription = '[Pleybox] charge for new Plan on ' . $subscription->getName() . ' subscription.';
        $newSubsResult = $this->_createPaidSubscription(
            $user, $profileSubs, $paymentMethod, $subscription, $paymentPlan, $chargeDescription
        );

        return $newSubsResult;
    }

    /**
     * Core functionality to created a Paid subscription.
     * @param \Pley\Entity\User\User                   $user
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     * @param \Pley\Entity\User\UserAddress            $address
     * @param \Pley\Entity\Payment\UserPaymentMethod   $paymentMethod
     * @param \Pley\Entity\Subscription\Subscription   $subscription
     * @param \Pley\Entity\Payment\PaymentPlan         $paymentPlan
     * @param string                                   $chargeDescription
     * @param \Pley\Entity\Coupon\Coupon               $coupon
     * @return \Pley\User\NewSubscriptionResult Object containing data created for subscription
     */
    private function _createPaidSubscription(\Pley\Entity\User\User $user,
            \Pley\Entity\Profile\ProfileSubscription $profileSubs,
            \Pley\Entity\Payment\UserPaymentMethod $paymentMethod,
            \Pley\Entity\Subscription\Subscription $subscription,
            \Pley\Entity\Payment\PaymentPlan $paymentPlan,
            $chargeDescription,
            \Pley\Entity\Coupon\Coupon $coupon = null)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        // Validation to make sure that the supplied payment method is the same as the one set as Default.
        if ($user->getDefaultPaymentMethodId() != $paymentMethod->getId()) {
            throw new \Exception('Payment Method supplied is not the Default.');
        }

        /** @TODO : We need to adjust this for international shipping as with the AB Test where
         * the address may not be supplied until after payment, we can no longer rely on the address
         * to determine the shipping zone */
        $FORCED_DEFAULT_SHIPPING_ZONE = 1;
        $vendorPaymentPlan = $this->_vendorPaymentPlanDao->findByPaymentPlan(
            $paymentPlan->getId(), $FORCED_DEFAULT_SHIPPING_ZONE, $user->getVPaymentSystemId()
        );

        // Getting the Payment Manager for the user assigned vendor system.
        $paymentManager = $this->_paymentManagerFactory->getManager($user->getVPaymentSystemId());

        // -----------------------------------------------------------------------------------------
        // Creating the base Profile Subscription Entry

        // And creating the Queue with the Paid and Reserved items (as well as updating the availability)
        $itemSequence = $this->_createSubscriptionQueue($profileSubs, $subscription, $paymentPlan);
        
        // Collecting the first item to be delivered as it is not certain that it will be shipped
        // within this current active period, but we still want to inform what is the first item to
        // be shipped.
        $firstSequenceItem = $itemSequence[0];
        
        // -----------------------------------------------------------------------------------------
        // Now creating the Profile Subscription Plan which determines how the subscription will be paid
        // (Since we perform an immediate charge on the date the subscription is created and set the
        // actual subscription to start at a future date, upon creation of the plan, we don't have
        // the Vendor subscription ID yet, so it has to be updatd later below)
        $isAutoRenew     = true;
        $profileSubsPlan = \Pley\Entity\Profile\ProfileSubscriptionPlan::withNew(
            $user->getId(), $profileSubs->getUserProfileId(), $profileSubs->getId(), $paymentPlan->getId(),
            \Pley\Enum\SubscriptionStatusEnum::ACTIVE, $isAutoRenew
        );
        $this->_profileSubsPlanDao->save($profileSubsPlan);
        
        // Syncing the Subscription Root to the new Plan
        $profileSubs->updateWithSubscriptionPlan($profileSubsPlan);
        $this->_profileSubsDao->save($profileSubs);

        // -----------------------------------------------------------------------------------------
        // Now proceed to do the immediate first charge for the subscription, record the transaction
        // and add the respective shipments for the charge
        $baseAmount     = $vendorPaymentPlan->getTotal();

        $couponDiscount = $this->_couponManager->calculateDiscount($vendorPaymentPlan, $baseAmount, $coupon);
        $rewardDiscount = $this->_rewardManager->getLoggedInUserReferralRewardAmount();

        $discountAmount = $couponDiscount + $rewardDiscount;
        $amount         = $baseAmount - $discountAmount;

        $this->_sanitizeMinimumChargeAmount($paymentManager, $amount, $discountAmount);

        $metadata    = [
            'subscriptionId'            => $subscription->getId(),
            'subscriptionName'          => $subscription->getName(),
            'userId'                    => $user->getId(),
            'userProfileId'             => $profileSubs->getUserProfileId(),
            'profileSubscriptionId'     => $profileSubs->getId(),
            'profileSubscriptionPlanId' => $profileSubsPlan->getId(),
            'baseAmount'                => $baseAmount,
        ];
        if ($coupon) {
            $metadata['couponCode']           = $coupon->getCode();
            $metadata['couponId']             = $coupon->getId();
            $metadata['couponType']           = \Pley\Enum\CouponTypeEnum::asString($coupon->getType());
            $metadata['couponDiscountAmount'] = $coupon->getDiscountAmount();
        }
        $transaction = $paymentManager->charge($user, $paymentMethod, $amount, $chargeDescription, $metadata);

        $profileSubsTransac = \Pley\Entity\Profile\ProfileSubscriptionTransaction::withNew(
            $user->getId(), $profileSubs->getUserProfileId(), $profileSubs->getId(), $profileSubsPlan->getId(),
            $paymentMethod->getId(), \Pley\Enum\TransactionEnum::CHARGE, $amount,
            $user->getVPaymentSystemId(), $paymentMethod->getVPaymentMethodId(), $transaction->getVendorId(),
            $transaction->getTransactionAt(), $baseAmount, $discountAmount,
            $this->_couponManager->getDiscountType($coupon), $this->_couponManager->getDiscountSourceId($coupon)
        );
        $this->_profileSubsTransacDao->save($profileSubsTransac);

        if ($coupon) {
            $this->_couponManager->logRedemption($coupon, $user, $profileSubsTransac);
        }
        // Calculate the first recurring charge date before we add shipments, since there is a chance
        // that we take the last of the most recent available item, causing to set up the recurring
        // date even futher in the future.
        $firstRecurringPaymentDate = $this->getFirstRecurringChargeDate($subscription, $paymentPlan, $itemSequence);
        
        // If the first item is schedule to be delivered on a future period (due to out of inventory
        // for the current period), then no shipment will be added nor an Item be pulled from the queue.
        // Since we only need to notify when the first item will be shipped, there is no need to know
        // whether the shipment or the queue item got pulled
        $this->queueShipment($profileSubs, $subscription, $firstSequenceItem->getPeriodIndex());
        
        // -----------------------------------------------------------------------------------------
        // Finally we can go ahead and create the vendor subscription with trial period until the 
        // second payment would be due (this is because the first payment is immediate, see above)
        // This process is done so that all subscription recurring payments align
        $paymentSubs = $paymentManager->addSubscription($user, $vendorPaymentPlan, $firstRecurringPaymentDate, $metadata);
        
        $profileSubsPlan->setVPaymentPlan($user->getVPaymentSystemId(), $vendorPaymentPlan->getVPaymentPlanId());
        $profileSubsPlan->setVPaymentSubscription($user->getVPaymentSystemId(), $paymentSubs->getVendorId());
        $this->_profileSubsPlanDao->save($profileSubsPlan);
        
        $newSubsResult = new NewSubscriptionResult();
        $newSubsResult->itemSequence              = $itemSequence;
        $newSubsResult->coupon                    = $coupon;
        $newSubsResult->profileSubscription       = $profileSubs;
        $newSubsResult->profileSubsPlan           = $profileSubsPlan;
        $newSubsResult->profileSubsTransac        = $profileSubsTransac;
        $newSubsResult->vendorPaymentPlan         = $vendorPaymentPlan;
        $newSubsResult->firstRecurringPaymentDate = $firstRecurringPaymentDate;

        return $newSubsResult;
    }

    /**
     * Core functionality to created a Paid Paypal subscription.
     * @param \Pley\Entity\User\User                   $user
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     * @param \Pley\Entity\User\UserAddress            $address
     * @param \Pley\Entity\Payment\UserPaymentMethod   $paymentMethod
     * @param \Pley\Entity\Subscription\Subscription   $subscription
     * @param \Pley\Entity\Payment\PaymentPlan         $paymentPlan
     * @param \Pley\Entity\Coupon\Coupon               $coupon
     * @return \Pley\User\NewSubscriptionResult Object containing data created for subscription
     */
    private function _createPaypalSubscription(\Pley\Entity\User\User $user,
                                             \Pley\Entity\Profile\ProfileSubscription $profileSubs,
                                             \Pley\Entity\Payment\UserPaymentMethod $paymentMethod,
                                             \Pley\Entity\Subscription\Subscription $subscription,
                                             \Pley\Entity\Payment\PaymentPlan $paymentPlan,
                                             \PayPal\Api\Agreement $agreement,
                                             \Pley\Entity\Coupon\Coupon $coupon = null)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        $FORCED_DEFAULT_SHIPPING_ZONE = 1;
        $paymentSystemId = PaymentSystemEnum::PAYPAL;

        $vendorPaymentPlan = $this->_vendorPaymentPlanDao->findByPaymentPlan(
            $paymentPlan->getId(), $FORCED_DEFAULT_SHIPPING_ZONE, $paymentSystemId
        );

        // -----------------------------------------------------------------------------------------
        // Creating the base Profile Subscription Entry

        // And creating the Queue with the Paid and Reserved items (as well as updating the availability)
        $itemSequence = $this->_createSubscriptionQueue($profileSubs, $subscription, $paymentPlan);

        // Collecting the first item to be delivered as it is not certain that it will be shipped
        // within this current active period, but we still want to inform what is the first item to
        // be shipped.
        $firstSequenceItem = $itemSequence[0];

        // -----------------------------------------------------------------------------------------
        // Now creating the Profile Subscription Plan which determines how the subscription will be paid
        // (Since we perform an immediate charge on the date the subscription is created and set the
        // actual subscription to start at a future date, upon creation of the plan, we don't have
        // the Vendor subscription ID yet, so it has to be updatd later below)
        $isAutoRenew     = true;
        $profileSubsPlan = \Pley\Entity\Profile\ProfileSubscriptionPlan::withNew(
            $user->getId(), $profileSubs->getUserProfileId(), $profileSubs->getId(), $paymentPlan->getId(),
            \Pley\Enum\SubscriptionStatusEnum::ACTIVE, $isAutoRenew
        );
        $this->_profileSubsPlanDao->save($profileSubsPlan);

        // Syncing the Subscription Root to the new Plan
        $profileSubs->updateWithSubscriptionPlan($profileSubsPlan);
        $this->_profileSubsDao->save($profileSubs);

        // -----------------------------------------------------------------------------------------
        // Now proceed to do the immediate first charge for the subscription, record the transaction
        // and add the respective shipments for the charge
        $baseAmount     = $vendorPaymentPlan->getTotal();

        $couponDiscount = $this->_couponManager->calculateDiscount($vendorPaymentPlan, $baseAmount, $coupon);
        $rewardDiscount = $this->_rewardManager->getLoggedInUserReferralRewardAmount();

        $discountAmount = $couponDiscount + $rewardDiscount;
        $amount         = $baseAmount - $discountAmount;

        $profileSubsTransac = \Pley\Entity\Profile\ProfileSubscriptionTransaction::withNew(
            $user->getId(), $profileSubs->getUserProfileId(), $profileSubs->getId(), $profileSubsPlan->getId(),
            $paymentMethod->getId(), \Pley\Enum\TransactionEnum::CHARGE, $amount,
            $paymentSystemId, $paymentMethod->getVPaymentMethodId(), $agreement->getId(),
            time(), $baseAmount, $discountAmount,
            $this->_couponManager->getDiscountType($coupon), $this->_couponManager->getDiscountSourceId($coupon)
        );
        $this->_profileSubsTransacDao->save($profileSubsTransac);

        if ($coupon) {
            $this->_couponManager->logRedemption($coupon, $user, $profileSubsTransac);
        }
        // Calculate the first recurring charge date before we add shipments, since there is a chance
        // that we take the last of the most recent available item, causing to set up the recurring
        // date even futher in the future.
        $firstRecurringPaymentDate = $this->getFirstRecurringChargeDate($subscription, $paymentPlan, $itemSequence);

        // If the first item is schedule to be delivered on a future period (due to out of inventory
        // for the current period), then no shipment will be added nor an Item be pulled from the queue.
        // Since we only need to notify when the first item will be shipped, there is no need to know
        // whether the shipment or the queue item got pulled
        $this->queueShipment($profileSubs, $subscription, $firstSequenceItem->getPeriodIndex());

        // -----------------------------------------------------------------------------------------
        // Finally we can go ahead and create the vendor subscription with trial period until the
        // second payment would be due (this is because the first payment is immediate, see above)
        // This process is done so that all subscription recurring payments align

        $profileSubsPlan->setVPaymentPlan($paymentSystemId, $vendorPaymentPlan->getVPaymentPlanId());
        $profileSubsPlan->setVPaymentSubscription($paymentSystemId, $agreement->getId());
        $this->_profileSubsPlanDao->save($profileSubsPlan);

        $newSubsResult = new NewSubscriptionResult();
        $newSubsResult->itemSequence              = $itemSequence;
        $newSubsResult->coupon                    = $coupon;
        $newSubsResult->profileSubscription       = $profileSubs;
        $newSubsResult->profileSubsPlan           = $profileSubsPlan;
        $newSubsResult->profileSubsTransac        = $profileSubsTransac;
        $newSubsResult->vendorPaymentPlan         = $vendorPaymentPlan;
        $newSubsResult->firstRecurringPaymentDate = $firstRecurringPaymentDate;

        return $newSubsResult;
    }
    
    /**
     * Closure to add a new Non-Recurring Subscription for the user on the given profile and addres
     * for the supplied gift, all as a transaction.
     * 
     * @param \Pley\Entity\User\User        $user
     * @param \Pley\Entity\User\UserProfile $profile
     * @param \Pley\Entity\User\UserAddress $address
     * @param \Pley\Entity\Gift\Gift        $gift
     * @return \Pley\User\NewSubscriptionResult Object containing data created for subscription
     */
    private function _addGiftSubscriptionClosure(
            \Pley\Entity\User\User $user, 
            \Pley\Entity\User\UserProfile $profile,
            \Pley\Entity\Gift\Gift $gift,
            \Pley\Entity\User\UserAddress $address = null)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $subscription = $this->_subscriptionMgr->getSubscription($gift->getSubscriptionId());
        
        // Retrieving the equivalent Payment Plan so we can determine how many boxes are given
        $giftPrice   = $this->_giftPriceDao->find($gift->getGiftPriceId());
        $paymentPlan = $this->_paymentPlanDao->find($giftPrice->getEquivalentPaymentPlanId());
        
        // Marking the gift as redeemed and associating it to the user that redeemed it.
        $gift->setRedeemed($user->getId());
        $this->_giftDao->save($gift);
        
        // Creating the base Profile Subscription Entry
        $profileSubs = \Pley\Entity\Profile\ProfileSubscription::withNewGift(
            $user->getId(), $profile->getId(), $gift->getSubscriptionId(), $gift->getId()
        );
        if (isset($address)) { $profileSubs->setUserAddressId($address->getId()); }
        $this->_profileSubsDao->save($profileSubs);
        
        // And creating the Queue with the Paid and Reserved items (as well as updating the availability)
        $itemSequence = $this->_createSubscriptionQueue($profileSubs, $subscription, $paymentPlan);
        
        // Collecting the first item to be delivered as it is not certain that it will be shipped
        // within this current active period, but we still want to inform what is the first item to
        // be shipped.
        $firstSequenceItem = $itemSequence[0];
        
        // If the first item is schedule to be delivered on a future period (due to out of inventory
        // for the current period), then no shipment will be added nor an Item be pulled from the queue.
        // Since we only need to notify when the first item will be shipped, there is no need to know
        // whether the shipment or the queue item got pulled
        $this->queueShipment($profileSubs, $subscription);
        
        $newSubsResult = new NewSubscriptionResult();
        $newSubsResult->itemSequence        = $itemSequence;
        $newSubsResult->profileSubscription = $profileSubs;
        $newSubsResult->gift                = $gift;

        return $newSubsResult;
    }
    
    /**
     * Creates the Item Sequence Queue for the Profile Subscription and updates the respective sequence
     * items with the purchased and reserved items.
     * <p>The profile subscription is updated with the Queue and the period where the first item will
     * be shipped.</p>
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubscription
     * @param \Pley\Entity\Subscription\Subscription $subscription
     * @param \Pley\Entity\Payment\PaymentPlan $paymentPlan
     * @return \Pley\Entity\Subscription\SequenceItem[] The sequence of items the user has subscribed to.
     */
    private function _createSubscriptionQueue(
            \Pley\Entity\Profile\ProfileSubscription $profileSubscription,
            \Pley\Entity\Subscription\Subscription $subscription, 
            \Pley\Entity\Payment\PaymentPlan $paymentPlan)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $boxCount     = $this->_subscriptionMgr->getSubscriptionBoxCount($subscription->getId(), $paymentPlan->getId());
        $itemSequence = $this->_subscriptionMgr->getItemSequenceForProfileSubscription(
            $subscription, $profileSubscription
        );
        $itemCount    = count($itemSequence);

        // Now create the user queue and update the item sale as well
        $itemSequenceQueue = [];
        $addedItemSequence = [];
        for ($i = 0; $i < $itemCount; $i++) {
            $seqItem = $itemSequence[$i];
            
            $itemType = QueueItem::TYPE_RESERVED;
            if ($i < $boxCount) {
                $itemType = QueueItem::TYPE_PURCHASED;
            
            // A gift subscription does not get reserved items in their queue.
            } else if ($profileSubscription->getStatus() == \Pley\Enum\SubscriptionStatusEnum::GIFT) {
                break;
            }
            
            $queueItem = new QueueItem($seqItem->getSequenceIndex(), $itemType);
            
            $this->_subscriptionMgr->increaseItemSale($seqItem, $queueItem);
            
            $itemSequenceQueue[] = $queueItem;
            $addedItemSequence[] = $seqItem;
        }
        
        $profileSubscription->setItemSequenceQueue($itemSequenceQueue);
        $this->_profileSubsDao->save($profileSubscription);
        
        return $addedItemSequence;
    }
    
    /**
     * Since our Payment management Vendors have a minimum charge, we need to make sure that we abide
     * by it and adjust the amount to be this minimum (Business rule decided by CEO).
     * <p>If the amount were to go below this minimum, then we need to adjust the amount and also any
     * potential discount.</p>
     * <p>There are only two cases where this could basically happen.
     * <ul>
     *   <li>A 100% coupon was created to share with strategic partners and it is only used for a single box subscription</li>
     *   <li>A coupon is misconfigured which would yield a free first payment.</li>
     * </ul></p>
     * @param float $amount
     * @param float $discount
     */
    private function _sanitizeMinimumChargeAmount(\Pley\Payment\PaymentManagerInterface $paymentManager, &$amount, &$discount)
    {
        $minimumChargeAmount = $paymentManager->getMinimumCharge();
        
        // If the amount is greater than the minimum charge, there is no sanitization to do.
        if ($amount >= $minimumChargeAmount) {
            return;
        }
        
        // Now we know we need to sanitize and we know that amount is less than minimum charge
        
        // If there is an actual discount, then we have to adjust it
        if ($discount > 0) {
            $difference = $minimumChargeAmount - $amount;
            $discount   -= $difference;

            // If the adjustment yields a negative discount, just set the discount to 0
            if ($discount < 0) {
                $discount = 0;
            }
        }

        // Now just fix the amount to the minimum
        $amount = $minimumChargeAmount;
    }
    
    /**
     * Closure to remove the Reserved items on the Profile Subscription queue and free the respective
     * inventory.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     */
    private function _clearReservedClosure(\Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $itemSequenceQueue = $profileSubs->getItemSequenceQueue();
        
        // If the queue is already empty, there is nothing to be done
        if (empty($itemSequenceQueue)) {
            return;
        }
        
        list ($purchasedQueue, $reservedQueue) = $this->_splitItemSequenceQueue($itemSequenceQueue);
        
        // Updating the queue to leave only the purchased items
        $profileSubs->setItemSequenceQueue($purchasedQueue);
        $this->_profileSubsDao->save($profileSubs);
        
        /* @var $queueItem \Pley\Entity\Profile\QueueItem */
        foreach ($reservedQueue as $queueItem) {
            $this->_subscriptionMgr->freeReservedItem(
                $profileSubs->getSubscriptionId(), $queueItem->getSequenceIndex()
            );
        }
    }
    
    /**
     * Closure to move X number of Reserved Units of the subscription's item queue to Purchased.
     * inventory.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     */
    private function _reservedToPaidClosure(\Pley\Entity\Profile\ProfileSubscription $profileSubs, $count)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $itemSequenceQueue = $profileSubs->getItemSequenceQueue();
       
        /* @var $reservedQueue \Pley\Entity\Profile\QueueItem[] */
        list ($purchasedQueue, $reservedQueue) = $this->_splitItemSequenceQueue($itemSequenceQueue);
        
        if (count($reservedQueue) < $count) {
            throw new \Exception("Invalid count, cannot convert {$count} items as there are less reserved");
        }
        
        for ($i = 0; $i < $count; $i++) {
            // Removing an item from the reserved to make it purchased and add it to the respective queue
            $queueItem = array_shift($reservedQueue);
            $queueItem->setType(QueueItem::TYPE_PURCHASED);
            $purchasedQueue[] = $queueItem;
            
            //move from reserved to purchased
            $this->_subscriptionMgr->reservedToPaidItem(
                $profileSubs->getSubscriptionId(), $queueItem->getSequenceIndex()
            );
        }
        
        $updatedItemSequenceQueue = array_merge($purchasedQueue, $reservedQueue);
        
        // Updating the queue to leave only the purchased items
        $profileSubs->setItemSequenceQueue($updatedItemSequenceQueue);
        $this->_profileSubsDao->save($profileSubs);
    }
    
    /**
     * Closure to remove the Purchased shipments on the Profile Subscription free the respective inventory.
     * <p>Note: This action is only to be taken as a result of CustomerService performing a full cancel.</p>
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     */
    private function _removeNotShippedClosure(\Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $profileSubsShipList = $this->_profileSubsShipDao->findNotShipped($profileSubs->getId());
        
        foreach ($profileSubsShipList as $profileSubsShipment) {
            $this->_subscriptionMgr->freePurchasedItem(
                $profileSubs->getSubscriptionId(), $profileSubsShipment->getItemSequenceIndex()
            );
            
            $this->_profileSubsShipDao->delete($profileSubsShipment);
        }
    }
    
    /**
     * Takes the supplied Item Sequence Queue and splits it into two, the Purchased and Reserved.
     * @param \Pley\Entity\Profile\QueueItem[] $itemSequenceQueue
     * @return array A two element array with the following structure<br/>
     *      <pre>array(
     *      &nbsp;   \Pley\Entity\Profile\QueueItem[] $purchasedQueue,
     *      &nbsp;   \Pley\Entity\Profile\QueueItem[] $reservedQueue
     *      );</pre>
     */
    private function _splitItemSequenceQueue(array $itemSequenceQueue)
    {
        $purchasedQueue = [];
        $reservedQueue  = [];
        
        while (!empty($itemSequenceQueue)) {
            $queueItem = array_shift($itemSequenceQueue);
            
            if ($queueItem->getType() == QueueItem::TYPE_PURCHASED) {
                $purchasedQueue[] = $queueItem;
                                
            } else { // $queueItem->getType() == QueueItem::TYPE_RESERVED
                $reservedQueue[] = $queueItem;
            }
        }
        
        return [$purchasedQueue, $reservedQueue];
    }
    
    /**
     * Closure that reactivates the auto-renew option on an existing Ative subscription.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     */
    private function _reactivateAutoRenewClosure(
            \Pley\Entity\User\User $user, \Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);

        $profileSubsPlan = $this->_profileSubsPlanDao->findLastByProfileSubscription($profileSubs->getId());

        // Reset Subscription and its latest plan (We do this to leave the actual billing vendor
        // operation at the end, so in case there is a problem with that operation, this will just be
        // reverted instead of a DB issue and we have to rollback a Billing operation.
        $profileSubsPlan->resetAutoRenewal();
        $profileSubs->updateWithSubscriptionPlan($profileSubsPlan);

        $this->_profileSubsDao->save($profileSubs);
        $this->_profileSubsPlanDao->save($profileSubsPlan);

        // Getting the Payment Manager for the user assigned vendor system.
        $paymentManager = $this->_paymentManagerFactory->getManager($user->getVPaymentSystemId());

        $paymentManager->reactivateAutoRenew($user, $profileSubsPlan);
    }

}

/**
 * The <kbd>NewSubscriptionResult</kbd> class provides a way to store the multiple objects created
 * when creating a new subscription.
 * @author Alejandro Salazar (alejandros@pley.com)
 */
class NewSubscriptionResult
{
    /** @var \Pley\Entity\Subscription\SequenceItem[] */
    public $itemSequence;
    /** @var \Pley\Entity\Coupon\Coupon */
    public $coupon;
    /** @var \Pley\Entity\Profile\ProfileSubscription */
    public $profileSubscription;
    /** @var \Pley\Entity\Profile\ProfileSubscriptionPlan */
    public $profileSubsPlan;
    /** @var \Pley\Entity\Profile\ProfileSubscriptionTransaction */
    public $profileSubsTransac;
    /** @var \Pley\Entity\Payment\VendorPaymentPlan */
    public $vendorPaymentPlan;
    /** @var int */
    public $firstRecurringPaymentDate;
    /** @var \Pley\Entity\Gift\Gift */
    public $gift;
}