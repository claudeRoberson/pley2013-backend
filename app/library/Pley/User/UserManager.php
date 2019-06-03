<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace Pley\User;
use Pley\Dao\Subscription\SubscriptionDao;

/**
 * The <kbd>UserManager</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package 
 * @subpackage
 */
class UserManager
{
    /** @var \Pley\Dao\User\UserAddressDao */
    protected $_userAddressDao;
    /** @var \Pley\Dao\Payment\UserPaymentMethodDao */
    protected $_userPymtMethodDao;
    /** @var \Pley\Dao\User\UserProfileDao */
    protected $_userProfileDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionPlanDao */
    protected $_profileSubsPlanDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionTransactionDao */
    protected $_profileSubsTransacDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionShipmentDao */
    protected $_profileSubsShipmentDao;
    /** @var \Pley\Dao\Subscription\SubscriptionDao */
    protected $_subscriptionDao;
    /** @var \Pley\Repository\User\UserWaitlistRepository */
    protected $_userWaitlistRepo;
    
    public function __construct(
            \Pley\Dao\User\UserAddressDao $userAddressDao,
            \Pley\Dao\User\UserProfileDao $userProfileDao,
            \Pley\Dao\Payment\UserPaymentMethodDao $userPymtMethodDao,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao,
            \Pley\Dao\Profile\ProfileSubscriptionPlanDao $profileSubsPlanDao,
            \Pley\Dao\Profile\ProfileSubscriptionTransactionDao $profileSubsTransacDao,
            \Pley\Dao\Profile\ProfileSubscriptionShipmentDao $profileSubsShipmentDao,
            \Pley\Dao\Subscription\SubscriptionDao $subscriptionDao,
            \Pley\Repository\User\UserWaitlistRepository $userWaitlistRepo)
    {
        $this->_userAddressDao         = $userAddressDao;
        $this->_userProfileDao         = $userProfileDao;
        $this->_userPymtMethodDao      = $userPymtMethodDao;
        $this->_profileSubsDao         = $profileSubsDao;
        $this->_profileSubsPlanDao     = $profileSubsPlanDao;
        $this->_profileSubsTransacDao  = $profileSubsTransacDao;
        $this->_profileSubsShipmentDao = $profileSubsShipmentDao;
        $this->_subscriptionDao        = $subscriptionDao;
        $this->_userWaitlistRepo       = $userWaitlistRepo;
    }
    
    /**
     * Returns a map with the User's address entities.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\User\UserAddress[]
     */
    public function getAddressMap(\Pley\Entity\User\User $user)
    {
        $addressList = $this->_userAddressDao->findByUser($user->getId());
        $addressMap  = $this->_listToMap($addressList, 'getId');
        
        return $addressMap;
    }
    
    /**
     * Returns a map with the Profile's entities.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\User\UserProfile[]
     */
    public function getProfileMap(\Pley\Entity\User\User $user)
    {
        $profileList = $this->_userProfileDao->findByUser($user->getId());
        $profileMap  = $this->_listToMap($profileList, 'getId');
        
        return $profileMap;
    }
    
    /**
     * Returns the supplied User's default payment method.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\Payment\UserPaymentMethod
     */
    public function getDefaultPaymentMethod(\Pley\Entity\User\User $user)
    {
        return $this->_userPymtMethodDao->find($user->getDefaultPaymentMethodId());
    }
    
    /**
     * Retrieves a map of the visible Payent Methods for the supplied user.
     * <p>The optional parameter can be supplied to return all the payment methods stored.</p>
     * @param \Pley\Entity\User\User $user
     * @param boolean                $includeHidden (Optional)<br/>Default <kbd>FALSE</kbd>. By 
     *      default it only returns the visible cards, but if all are needed (either for Customer 
     *      Service or to re-enable a hidden one), set it to <kbd>TRUE</kbd>.
     * @return \Pley\Entity\Payment\UserPaymentMethod[]
     */
    public function getPaymentMethodMap(\Pley\Entity\User\User $user, $includeHidden = false)
    {
        $stripePaymentMethodList = $this->_userPymtMethodDao->findByUser($user->getId(), $includeHidden, \Pley\Enum\PaymentSystemEnum::STRIPE);
        $paypalPaymentMethodList = $this->_userPymtMethodDao->findByUser($user->getId(), $includeHidden, \Pley\Enum\PaymentSystemEnum::PAYPAL);
        $paymentMethodList = array_merge($stripePaymentMethodList, $paypalPaymentMethodList);
        $paymentMethodMap  = $this->_listToMap($paymentMethodList, 'getId');
        
        return $paymentMethodMap;
    }
    
    /**
     * Returns a map of the Subscriptions for the supplied user or profile.
     * @param \Pley\Entity\User\User $user
     * @param \Pley\Entity\User\UserProfile $profile (Optional)<br/>If supplied used to filter
     *      the subscriptions returned.
     * @return \Pley\Entity\Profile\ProfileSubscription[]
     */
    public function getSubscriptionMap(\Pley\Entity\User\User $user, \Pley\Entity\User\UserProfile $profile = null)
    {
        if (isset($profile)) {
            $profileSubsList = $this->_profileSubsDao->findByProfile($profile->getId());
            
        } else {
            $profileSubsList = $this->_profileSubsDao->findByUser($user->getId());
        }
        
        $profileSubsMap = $this->_listToMap($profileSubsList, 'getId');
        
        return $profileSubsMap;
    }

    /**
     * Returns a map of the Subscriptions for the supplied user or profile.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\User\UserWaitlist[]
     */
    public function getWaitlistMap(\Pley\Entity\User\User $user)
    {
        $userWaitlistItems = $this->_userWaitlistRepo->findWaitlistByUser($user->getId());
        foreach ($userWaitlistItems as $waitlistItem){
            $userProfile = $this->_getUserProfileForWaitlist($waitlistItem, $user);
            $waitlistItem->setUserProfileId($userProfile->getId());
            $waitlistItem->userAddress = $this->_getUserAddressForWaitlist($waitlistItem, $user);
            $waitlistItem->paymentMethod = $this->_userPymtMethodDao->find($user->getDefaultPaymentMethodId());
            $waitlistItem->subscription = $this->_subscriptionDao->find($waitlistItem->getSubscriptionId());
        }

        $userWaitlistMap = $this->_listToMap($userWaitlistItems, 'getId');
        return $userWaitlistMap;
    }

    /**
     * Retruns the UserProfile object to use for the supplied user waitlist object
     * @param \Pley\Entity\User\UserWaitlist $userWait
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\User\UserProfile
     */
    private function _getUserProfileForWaitlist(\Pley\Entity\User\UserWaitlist $userWait, \Pley\Entity\User\User $user)
    {
        if (!empty($userWait->getUserProfileId())) {
            $userProfile = $this->_userProfileDao->find($userWait->getUserProfileId());

        } else {
            // Retrieving the first and virtually only profile stored so far (two things can happen
            // if full account is registered before billing, we'll have a profile, but if account is
            // incomplete before billing, we may not have a user profile)
            $userProfileList = $this->_userProfileDao->findByUser($user->getId());
            if (empty($userProfileList)) {
                $userProfile = \Pley\Entity\User\UserProfile::withDummy($user->getId());
                $this->_userProfileDao->save($userProfile);
            } else {
                $userProfile = $userProfileList[0];
            }
        }

        return $userProfile;
    }

    /**
     * Retruns the UserAddress object to use for the supplied user waitlist object
     * @param \Pley\Entity\User\UserWaitlist $userWait
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\User\UserAddress|null
     */
    private function _getUserAddressForWaitlist(\Pley\Entity\User\UserWaitlist $userWait, \Pley\Entity\User\User $user)
    {
        if (!empty($userWait->getUserAddressId())) {
            $userAddress = $this->_userAddressDao->find($userWait->getUserAddressId());

        } else {
            // Retrieving the first and virtually only profile stored so far (two things can happen
            // if full account is registered before billing, we'll have a profile, but if account is
            // incomplete before billing, we may not have a user profile)
            $userAddressList = $this->_userAddressDao->findByUser($user->getId());

            $userAddress = empty($userAddressList)? null : $userAddressList[0];
        }
        return $userAddress;
    }
    
    /**
     * Returns the most recent payment plan for the supplied profile subscription.
     * <p>A gift subscription is not going to have a payment plan.</p>
     * 
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     * @return \Pley\Entity\Profile\ProfileSubscriptionPlan
     */
    public function getRecentSubcriptionPlan(\Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        return $this->_profileSubsPlanDao->findLastByProfileSubscription($profileSubs->getId());
    }
    
    /**
     * Returns all the payment plan for the supplied profile subscription in cronological order.
     * <p>For a gift subscription, there are no payment plans.</p>
     * 
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     * @return \Pley\Entity\Profile\ProfileSubscriptionPlan[]
     */
    public function getSubscriptionPlanMap(\Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        $profileSubsPlanList = $this->_profileSubsPlanDao->findByProfileSubscription($profileSubs->getId());
        $profileSubsPlanMap  = $this->_listToMap($profileSubsPlanList, 'getId');
        
        return $profileSubsPlanMap;
    }
    
    /**
     * Returns all the transactions for the supplied profile subscription in cronological order.
     * <p>The first entry would be the oldest transaction, while the last one the most recent 
     * transaction made.</p>
     * <p>For a gift subscription, there are no transactions.</p>
     * <p>Note: The transactions are returned in Reverse Chronological order, having the most recent
     * first.</p>
     * 
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     * @return \Pley\Entity\Profile\ProfileSubscriptionTransaction[]
     */
    public function getSubscriptionTransactionList(\Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        return $this->_profileSubsTransacDao->findByProfileSubscription($profileSubs->getId());
    }
    
    /**
     * Returns all the shipments for the supplied profile subscription grouped by their status (DeliveredList, Current, PendingList)
     * <p>Note: The lists on the collection are sorted in the following fashion
     * <ul>
     *   <li>Delivered - Ordered in Reverse chronological order (most recent delivery first)</li>
     *   <li>Pending   - Ordered in Chronological order (soonest to be shipped first)</li>
     * </ul>
     * </p>
     * 
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubs
     * @return \Pley\User\ProfileShipmentCollection
     */
    public function getSubscriptionShipmentCollection(\Pley\Entity\Profile\ProfileSubscription $profileSubs)
    {
        $shipmentList = $this->_profileSubsShipmentDao->findByProfileSubscription($profileSubs->getId());
        
        $deliveredList = [];
        $current     = null;
        $pendingList = [];
        
        // Separating the Shipments by their status
        foreach ($shipmentList as $shipment) {
            if ($shipment->getStatus() == \Pley\Enum\Shipping\ShipmentStatusEnum::DELIVERED) {
                $deliveredList[] = $shipment;
                
            } else if ($shipment->getStatus() == \Pley\Enum\Shipping\ShipmentStatusEnum::PREPROCESSING
                    && empty($shipment->getLabelUrl())) {
                $pendingList[] = $shipment;
            } else if($shipment->getStatus() == \Pley\Enum\Shipping\ShipmentStatusEnum::CANCELLED){
                continue;
            }
            // Any preprocessing shipment with a label, processed or in transit status would be
            // considered the current, since per the programming, there can only be one in this status
            else {
                $current = $shipment;
            }
        }
        
        /* @var $shipmentA \Pley\Entity\Profile\ProfileSubscriptionShipment */
        /* @var $shipmentB \Pley\Entity\Profile\ProfileSubscriptionShipment */
        // Sorting the Delivered by the most recently delivered first.
        usort($deliveredList, function($shipmentA, $shipmentB) { return $shipmentB->getId() - $shipmentA->getId(); });
        // Sorting the Pending by the soones to be shipped first.
        usort($pendingList, function($shipmentA, $shipmentB) { return $shipmentA->getId() - $shipmentB->getId(); });
        
        $shipmentCollection = new \Pley\User\ProfileShipmentCollection($deliveredList, $current, $pendingList);
        return $shipmentCollection;
    }
    
    /**
     * Converts a list of objects into a map of objects where the key is the value retrieved from
     * the supplied method.
     * @param array  $objectList
     * @param string $methodNameForKey Method to invoke to use as Key for the map.
     * @return array
     */
    private function _listToMap($objectList, $methodNameForKey)
    {
        if (empty($objectList)) {
            return [];
        }
        
        $objectMap = [];
        foreach ($objectList as $object) {
            $key             = $object->$methodNameForKey();
            $objectMap[$key] = $object;
        }
        
        return $objectMap;
    }
}
