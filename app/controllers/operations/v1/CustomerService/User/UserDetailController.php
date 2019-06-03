<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace operations\v1\CustomerService\User;

/** ♰
 * The <kbd>UserDetailController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class UserDetailController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\User\UserManager */
    protected $_userManager;
    /** @var \Pley\Dao\User\UserDao */
    protected $_userDao;
    /** @var \Pley\Dao\User\UserProfileDao **/
    protected $_userProfileDao;
    /** @var \Pley\Dao\Payment\UserPaymentMethodDao **/
    protected $_userPymtMethodDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao **/
    protected $_profileSubsDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionPlanDao **/
    protected $_profileSubsPlanDao;
    /** @var \Pley\Dao\User\UserIncompleteRegistrationDao */
    protected $_userIncompleteRegDao;
    /** @var \Pley\Dao\Payment\PaymentPlanDao */
    protected $_paymentPlanDao;
    /** @var \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao */
    protected $_vendorPaymentPlanDao;
    /** @var \Pley\Dao\Gift\GiftDao */
    protected $_giftDao;
    /** @var \Pley\Dao\Gift\GiftPriceDao */
    protected $_giftPriceDao;
    /** @var \Pley\Subscription\SubscriptionManager **/
    protected $_subscriptionMgr;
    /** @var \Pley\Payment\PaymentManagerFactory */
    protected $_paymentManagerFactory;
    
    public function __construct(
            \Pley\User\UserManager $userManager,
            \Pley\Dao\User\UserDao $userDao,
            \Pley\Dao\User\UserProfileDao $userProfileDao,
            \Pley\Dao\Payment\UserPaymentMethodDao $userPymtMethodDao,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao,
            \Pley\Dao\Profile\ProfileSubscriptionPlanDao $profileSubsPlanDao,
            \Pley\Dao\Payment\PaymentPlanDao $paymentPlanDao,
            \Pley\Dao\Payment\PaymentPlanXVendorPaymentPlanDao $vendorPaymentPlanDao,
            \Pley\Dao\Gift\GiftDao $giftDao,
            \Pley\Dao\Gift\GiftPriceDao $giftPriceDao,
            \Pley\Subscription\SubscriptionManager $subscriptionMgr,
            \Pley\Payment\PaymentManagerFactory $paymentManagerFactory)
    {
        parent::__construct();
        
        $this->_userManager = $userManager;

        $this->_userDao            = $userDao;
        $this->_userProfileDao     = $userProfileDao;
        $this->_userPymtMethodDao  = $userPymtMethodDao;
        $this->_profileSubsDao     = $profileSubsDao;
        $this->_profileSubsPlanDao = $profileSubsPlanDao;
        $this->_paymentPlanDao     = $paymentPlanDao;
        $this->_vendorPaymentPlanDao = $vendorPaymentPlanDao;
        $this->_giftDao            = $giftDao;
        $this->_giftPriceDao       = $giftPriceDao;

        $this->_subscriptionMgr       = $subscriptionMgr;
        $this->_paymentManagerFactory = $paymentManagerFactory;
    }
    
    // GET /cs/user/{$userId}
    public function getUser($userId)
    {
        \RequestHelper::checkGetRequest();
        
        $user = $this->_userDao->find($userId);
        \ValidationHelper::entityExist($user, \Pley\Entity\User\User::class);
        
        $userAddressMap       = $this->_userManager->getAddressMap($user);
        $userProfileMap       = $this->_userManager->getProfileMap($user);
        $userPaymentMethodMap = $this->_userManager->getPaymentMethodMap($user);
        
        $arrayResponse = [];
        $arrayResponse['user']                 = $this->_parseUser($user);
        $arrayResponse['addressMap']       = $this->_parseMap($userAddressMap, '_parseUserAddress');
        $arrayResponse['profileMap']       = $this->_parseMap($userProfileMap, '_parseUserProfile', $user);
        $arrayResponse['paymentMethodMap'] = $this->_parseMap($userPaymentMethodMap, '_parseUserPaymentMethod', $user);

        // Collecting the subscription IDs the user is subscribed to so we can retrieve the Subscription
        // Metadata info and then adding it to the response array
        $userSubscriptionMap = $this->_userManager->getSubscriptionMap($user);
        $subscriptionMap     = [];
        foreach ($userSubscriptionMap as $userSubscription) {
            $subsId = $userSubscription->getSubscriptionId();
            if (!isset($subscriptionMap[$subsId])) {
                $subscriptionMap[$subsId] = $this->_subscriptionMgr->getSubscription($subsId);
            }
        }
        $arrayResponse['subscriptionMap'] = $this->_parseMap($subscriptionMap, '_parseSubscription');
        
        return \Response::json($arrayResponse);
    }
    
    protected function _parseUser(\Pley\Entity\User\User $user)
    {
        return [
            'id'                     => $user->getId(),
            'firstName'              => $user->getFirstName(),
            'lastName'               => $user->getLastName(),
            'email'                  => $user->getEmail(),
            'createdAt'              => $user->getCreatedAt(),
            'defaultPaymentMethodId' => $user->getDefaultPaymentMethodId(),
            'vendor'                 => [
                'billing' => empty($user->getVPaymentSystemId()) ? null : [
                    'systemId'  => $user->getVPaymentSystemId(),
                    'accountId' => $user->getVPaymentAccountId(),
                ],
            ],
        ];
    }
    
    protected function _parseUserAddress(\Pley\Entity\User\UserAddress $address)
    {
        return [
            'id'        => $address->getId(),
            'street1'   => $address->getStreet1(),
            'street2'   => $address->getStreet2(),
            'phone'     => $address->getPhone(),
            'city'      => $address->getCity(),
            'state'     => $address->getState(),
            'zipCode'   => $address->getZipCode(),
            'createdAt' => $address->getCreatedAt(),
            'updatedAt' => $address->getUpdatedAt(),
        ];
    }
    
    protected function _parseUserProfile(\Pley\Entity\User\UserProfile $profile, \Pley\Entity\User\User $user)
    {
        $profileSubsMap  = $this->_userManager->getSubscriptionMap($user, $profile);
        
        return [
            'id'              => $profile->getId(),
            'firstName'       => $profile->getFirstName(),
            'lastName'        => $profile->getLastName(),
            'gender'          => $profile->getGender(),
            'birthDate'       => $profile->getBirthDate(),
            'shirtSize'       => $profile->getTypeShirtSizeId(),
            'createdAt'       => $profile->getCreatedAt(),
            'updatedAt'       => $profile->getUpdatedAt(),
            'subscriptionMap' => $this->_parseMap($profileSubsMap, '_parseProfileSubscription', $user),
        ];
    }
    
    protected function _parseUserPaymentMethod(
            \Pley\Entity\Payment\UserPaymentMethod $paymentMethod, \Pley\Entity\User\User $user)
    {
        $paymentManager = $this->_paymentManagerFactory->getManager($paymentMethod->getVPaymentSystemId());
        $card           = $paymentManager->getCard($user, $paymentMethod);

        return [
            'id'        => $paymentMethod->getId(),
            'brand'     => $card->getBrand(),
            'last4'     => $card->getNumber(),
            'expMonth'  => $card->getExpirationMonth(),
            'expYear'   => $card->getExpirationYear(),
            'type'      => $card->getType(),
            'createdAt' => $paymentMethod->getCreatedAt(),
            'updatedAt' => $paymentMethod->getUpdatedAt(),
        ];
    }
    
    protected function _parseProfileSubscription(
            \Pley\Entity\Profile\ProfileSubscription $profileSubs, \Pley\Entity\User\User $user)
    {
        // Retrieving the subscription payment plans (they would be empty if this is a Gift subscrition)
        $subsPlanMap = $this->_userManager->getSubscriptionPlanMap($profileSubs);
        
        // Retrieving all Billing transactions and Shipments
        $transactionList    = $this->_userManager->getSubscriptionTransactionList($profileSubs);
        $shipmentCollection = $this->_userManager->getSubscriptionShipmentCollection($profileSubs);
        
        // Retrieving the gift info if there is any
        $gift = null;
        if (!empty($profileSubs->getGiftId())) {
            $gift = $this->_giftDao->find($profileSubs->getGiftId());
        }
        
        return [
            'id'                     => $profileSubs->getId(),
            'subscriptionId'         => $profileSubs->getSubscriptionId(),
            'addressId'              => $profileSubs->getUserAddressId(),
            'paymentMethodId'        => $profileSubs->getUserPaymentMethodId(),
            'status'                 => $profileSubs->getStatus(),
            'isAutoRenew'            => $profileSubs->isAutoRenew(),
            'giftId'                 => $profileSubs->getGiftId(),
            'createdAt'              => $profileSubs->getCreatedAt(),
            'giftMap'                => $this->_parseGift($gift),
            'planMap'                => $this->_parseMap($subsPlanMap, '_parseProfileSubsPlan', $user),
            'transactionList'        => $this->_parseMap($transactionList, '_parseProfileSubsTransac'),
            'shipmentMap'            => [
                'current'       => $this->_parseProfileSubsShipment($shipmentCollection->getCurrent()),
                'pendingList'   => $this->_parseMap($shipmentCollection->getPendingList(), '_parseProfileSubsShipment'),
                'deliveredList' => $this->_parseMap($shipmentCollection->getDeliveredList(), '_parseProfileSubsShipment'),
            ],
        ];
        
    }
    
    protected function _parseProfileSubsPlan(
            \Pley\Entity\Profile\ProfileSubscriptionPlan $profileSubsPlan, \Pley\Entity\User\User $user)
    {
        $paymentPlan = $this->_paymentPlanDao->find($profileSubsPlan->getPaymentPlanId());
        $vendorPaymentPlan = $this->_vendorPaymentPlanDao->findByVendorPaymentPlanId($profileSubsPlan->getVPaymentPlanId());

        $billingPeriodStart = null;
        $billingPeriodEnd   = null;
        if ($profileSubsPlan->getStatus() != \Pley\Enum\SubscriptionStatusEnum::CANCELLED) {
            $paymentMgr  = \Pley\Payment\PaymentManagerFactory::getManager($profileSubsPlan->getVPaymentSystemId());
            $paymentSubs = $paymentMgr->getSubscription($user, $profileSubsPlan);
            
            $billingPeriodStart = $paymentSubs->getPeriodDateStart();
            $billingPeriodEnd   = $paymentSubs->getPeriodDateEnd();
        }
        
        return [
            'id'              => $profileSubsPlan->getId(),
            'status'          => $profileSubsPlan->getStatus(),
            'isAutoRenew'     => $profileSubsPlan->isAutoRenew(),
            'autoRenewStopAt' => $profileSubsPlan->getAutoRenewStopAt(),
            'cancelAt'        => $profileSubsPlan->getCancelAt(),
            'createdAt'       => $profileSubsPlan->getCreatedAt(),
            'vendor'          => [
                'billing' => [
                    'systemId'       => $profileSubsPlan->getVPaymentSystemId(),
                    'subscriptionId' => $profileSubsPlan->getVPaymentSubscriptionId(),
                    'periodStartAt'  => $billingPeriodStart,
                    'periodEndAt'    => $billingPeriodEnd,
                ]
            ],
            'paymentMap' => [
                'period'      => $paymentPlan->getPeriod(),
                'periodUnit'  => $paymentPlan->getPeriodUnit(),
                'price'       => [
                    'total' => $vendorPaymentPlan->getTotal(),
                    'unit'  => $vendorPaymentPlan->getUnitPrice(),
                    'shipping' => $vendorPaymentPlan->getShippingPrice()
                ]
            ]
        ];
    }
    
    protected function _parseProfileSubsTransac(\Pley\Entity\Profile\ProfileSubscriptionTransaction $transaction)
    {
        return [
            'id'                 => $transaction->getId(),
            'subscriptionPlanId' => $transaction->getProfileSubscriptionPlanId(),
            'transactionType'    => $transaction->getTransactionType(),
            'transactionAt'      => $transaction->getTransactionAt(),
            'amount'             => $transaction->getAmount(),
            'vendor'             => [
                'billing' => [
                    'systemId'      => $transaction->getVPaymentSystemId(),
                    'transactionId' => $transaction->getVPaymentTransactionId(),
                ]
            ],
        ];
    }
    
    protected function _parseProfileSubsShipment($shipment)
    {
        if (empty($shipment)) {
            return null;
        }
        
        // We want to do type checking, but to allow the object to be NULL on the parameter would mean
        // that the first parameter is optional while the second one is required, which is an immediate
        // PHP Warning, but a feature/deficiency of how Class type check works
        // So to prevent the PHP Warning but do the type check we do the check here.
        if (!$shipment instanceof \Pley\Entity\Profile\ProfileSubscriptionShipment) {
            $message = 'Argument 1 passed to _parseProfileSubsShipment() '
                     . 'must be an instance of \Pley\Entity\Profile\ProfileSubscriptionShipment, '
                     . 'instance of ' . get_class($shipment) . ' given';
            trigger_error($message, E_USER_ERROR);
        }
        
        $sequenceItem = $this->_subscriptionMgr->getScheduledItem($shipment);
        $item         = !empty($sequenceItem->getItemId())?
                $this->_subscriptionMgr->getItem($sequenceItem->getItemId()) : null;
        
        return [
            'id'          => $shipment->getId(),
            'status'      => $shipment->getStatus(),
            'source'      => [
                'type' => $shipment->getShipmentSourceType(),
                'id'   => $shipment->getShipmentSourceId(),
            ],
            'shirtSize'   => $shipment->getShirtSize(),
            'scheduleMap' => [
                'deadlineTime'      => $sequenceItem->getDeadlineTime(),
                'deliveryStartTime' => $sequenceItem->getDeliveryStartTime(),
                'deliveryEndTime'   => $sequenceItem->getDeliveryEndTime(),
                'item'              => empty($item) ? null : $item->getName(),
            ],
            'addressMap'  => empty($shipment->getStreet1()) ? null : [
                'street1' => $shipment->getStreet1(),
                'street2' => $shipment->getStreet2(),
                'city'    => $shipment->getCity(),
                'state'   => $shipment->getState(),
                'zip'     => $shipment->getZip(),
                'country' => $shipment->getCountry(),
                    ],
            'label'       => empty($shipment->getLabelUrl()) ? null : [
                'carrierId'   => $shipment->getCarrierId(),
                'serviceId'   => $shipment->getCarrierServiceId(),
                'trackingNo'  => $shipment->getTrackingNo(),
                'b64PngLabel' => \Pley\Util\Shipping\ShippingLabel::convert($shipment->getLabelUrl()),
            ],
            'shippedAt'   => $shipment->getShippedAt(),
            'deliveredAt' => $shipment->getDeliveredAt(),
        ];
    }
    
    protected function _parseGift(\Pley\Entity\Gift\Gift $gift = null)
    {
        if (empty($gift)) {
            return null;
        }
        
        $giftPrice   = $this->_giftPriceDao->find($gift->getGiftPriceId());
        $paymentPlan = $this->_paymentPlanDao->find($giftPrice->getEquivalentPaymentPlanId());
        
        return [
            'gift' => [
                'id'            => $gift->getId(),
                'fromFirstName' => $gift->getFromFirstName(),
                'fromLastName'  => $gift->getFromLastName(),
            ],
            'giftPrice' => [
                'id'         => $giftPrice->getId(),
                'total'      => $giftPrice->getPriceTotal(),
                'period'     => $paymentPlan->getPeriod(),
                'periodUnit' => $paymentPlan->getPeriodUnit(),
            ],
        ];
    }
    
    protected function _parseSubscription(\Pley\Entity\Subscription\Subscription $subscription)
    {
        return [
            'id'         => $subscription->getId(),
            'name'       => $subscription->getName(),
            'period'     => $subscription->getPeriod(),
            'periodUnit' => $subscription->getPeriodUnit(),
        ];
    }
    
    /**
     * Helper method to parse a map of objects into their array representation.
     * <p>This is used to reduce lines of code and make the parse methods more atomic handling only
     * one entity parsing, and this method the loop around that parsing.</p>
     * @param object[]              $objectMap
     * @param string                $parseMethod
     * @param \Pley\Entity\User\User $user        (Optional)
     * @return array
     */
    private function _parseMap($objectMap, $parseMethod, \Pley\Entity\User\User $user = null)
    {
        $arrayMap = [];
        foreach ($objectMap as $key => $object) {
            if (isset($user)) {
                $arrayMap[$key] = $this->$parseMethod($object, $user);
            } else {
                $arrayMap[$key] = $this->$parseMethod($object);
            }
        }
        
        return $arrayMap;
    }
    
}
