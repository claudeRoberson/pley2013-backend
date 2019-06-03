<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace webhook\v1;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Db\AbstractDatabaseManager as DatabaseManager;
use \Pley\Mail\AbstractMail as Mail;

/**
 * The <kbd>EasyPostListenerController</kbd> Listener to EasyPost shipment events.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class EasyPostListenerController extends \BaseController
{
    const DEPENDENCY_CONFIG                   = 'config';
    const DEPENDENCY_DB                       = 'dbManager';
    const DEPENDENCY_MAIL                     = 'mailer';
    const DEPENDENCY_USER_DAO                 = 'userDao';
    const DEPENDENCY_PROFILE_DAO              = 'userProfileDao';
    const DEPENDENCY_PROFILE_SUBSCRIPTION_DAO = 'profileSubscriptionDao';
    const DEPENDENCY_PROFILE_SHIPMENT_DAO     = 'profileSubsShipmentDao';
    const DEPENDENCY_SUBSCRIPTION_MGR         = 'subscriptionManager';
    const DEPENDENCY_NPS_MGR                  = 'npsManager';

    const EVENT_TYPE_TRACKER   = 'tracker';
    const EVENT_TYPE_BATCH     = 'batch';
    const EVENT_TYPE_SCAN_FORM = 'scan_form';
    const EVENT_TYPE_INSURANCE = 'insurance';
    const EVENT_TYPE_REFUND    = 'refund';
    const EVENT_TYPE_PAYMENT   = 'payment';
    
    private static $eventTypeMap = [
        'tracker.created'     => self::EVENT_TYPE_TRACKER,
        'tracker.updated'     => self::EVENT_TYPE_TRACKER,
        'batch.created'       => self::EVENT_TYPE_BATCH,
        'batch.updated'       => self::EVENT_TYPE_BATCH,
        'scan_form.created'   => self::EVENT_TYPE_SCAN_FORM,
        'scan_form.updated'   => self::EVENT_TYPE_SCAN_FORM,
        'insurance.purchased' => self::EVENT_TYPE_INSURANCE,
        'insurance.cancelled' => self::EVENT_TYPE_INSURANCE,
        'refund.successful'   => self::EVENT_TYPE_REFUND,
        'payment.created'     => self::EVENT_TYPE_PAYMENT,
        'payment.completed'   => self::EVENT_TYPE_PAYMENT,
        'payment.failed'      => self::EVENT_TYPE_PAYMENT,
    ];
    
    protected $_dependencyMap = [];
    
    public function __construct(Config $config, DatabaseManager $dbManager, Mail $mailer,
            \Pley\Dao\User\UserDao $userDao,
            \Pley\Dao\User\UserProfileDao $userProfileDao,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao,
            \Pley\Dao\Profile\ProfileSubscriptionShipmentDao $profileSubsShipmentDao,
            \Pley\Subscription\SubscriptionManager $subscriptionMgr,
            \Pley\Nps\NpsManagerInterface $npsManager)
    {
        $this->_dependencyMap[self::DEPENDENCY_CONFIG]                   = $config;
        $this->_dependencyMap[self::DEPENDENCY_DB]                       = $dbManager;
        $this->_dependencyMap[self::DEPENDENCY_MAIL]                     = $mailer;
        $this->_dependencyMap[self::DEPENDENCY_USER_DAO]                 = $userDao;
        $this->_dependencyMap[self::DEPENDENCY_PROFILE_DAO]              = $userProfileDao;
        $this->_dependencyMap[self::DEPENDENCY_PROFILE_SUBSCRIPTION_DAO] = $profileSubsDao;
        $this->_dependencyMap[self::DEPENDENCY_PROFILE_SHIPMENT_DAO]     = $profileSubsShipmentDao;
        $this->_dependencyMap[self::DEPENDENCY_SUBSCRIPTION_MGR]         = $subscriptionMgr;
        $this->_dependencyMap[self::DEPENDENCY_NPS_MGR]                  = $npsManager;

        // Adding a separate log so we can keep cleaner and more dedicated track of webhook calls
        // to this controller
        \LogHelper::popAllHandlers();
        \Log::useDailyFiles(storage_path(). '/logs/webhook-EasyPostListener.log');
        \LogHelper::ignoreHandlersEmptyContextAndExtra();
    }
    
    // POST /webhook/v1/easy-post/event
    public function listen()
    {
        \RequestHelper::checkPostRequest();
        $event = \EasyPost\Event::receive(\Input::getContent());
        
        $eventType = static::$eventTypeMap[$event->description];
        switch($eventType) {
            case self::EVENT_TYPE_TRACKER:
                $this->_trackerHandler($event);
                break;
            case self::EVENT_TYPE_BATCH:
            case self::EVENT_TYPE_SCAN_FORM:
            case self::EVENT_TYPE_INSURANCE:
            case self::EVENT_TYPE_REFUND:
            case self::EVENT_TYPE_PAYMENT:
            default:
                break;
        }
        
        return \Response::json(['success' => true]);
    }
    
    private function _trackerHandler(\EasyPost\Event $event)
    {
        $handler = new __EPL_TrackerHandler($event, $this->_dependencyMap);
        $response = $handler->process();
        
        // If the response is `FALSE`, either the event has already been processed for it's status
        // or it is an event for one of our other systems and thus not to be handled by this one.
        if ($response === false) {
            return;
        }
        
        // Otherwise, it was processed, so let's log the event
        $logElementMap = [
            'evtId'                         => $event->id,
            'shpId'                         => $event->result->shipment_id,
            'trackerId'                     => $event->result->id,
            'trackCode'                     => $event->result->tracking_code,
            'status'                        => $event->result->status,
            'profileSubscriptionShipmentId' => $response->getId()
        ];
        $logMessage = "EasyPost (webhook): " . json_encode($logElementMap);
        \Log::info($logMessage);
    }
}

// -------------------------------------------------------------------------------------------------
// Support handling classes ------------------------------------------------------------------------
// (EPL = EasyPostListener) ------------------------------------------------------------------------

/** @author Alejandro Salazar (alejandros@pley.com) */
abstract class __EPL_AbstractHandler
{
    /** @var \EasyPost\Event */
    protected $_event;
    /** @var array */
    protected $_dependencyMap;
    
    public function __construct(\EasyPost\Event $event, $dependencyMap = null)
    {
        $this->_event         = $event;
        $this->_dependencyMap = $dependencyMap;
    }
    
    /**
     * Processes the event related to this instance and return a response object or <kbd>false</kbd>
     * if the function couldn't process (perhaps because the event is for a different system, considering
     * we have one EasyPost service, but separate systems creating labels, so some labels will not
     * be related to this system)
     * @return object|false 
     */
    public function process()
    {
        $this->_init();
        return $this->_processDelegate();
    }
    
    /** Help initialize variables or references or execute some code before the actual processing occurs */
    protected function _init() {}
    
    /** Main method that will process the event */
    protected abstract function _processDelegate();
}

/** @author Alejandro Salazar (alejandros@pley.com) */
class __EPL_TrackerHandler extends __EPL_AbstractHandler
{
    /**
     * A shipping label has been created but the package with label has not yet been scanned and/or 
     * picked up by the carrier.
     * @var string 
     */
    const STATUS_UNKNOWN              = 'unknown';
    /**
     * Carrier has received information about the package but it has not yet been scanned and picked up.
     * @var string 
     */
    const STATUS_PRE_TRANSIT          = 'pre_transit';
    /**
     * A package is traveling to its destination. You may receive multiple updates of this type as a
     * package travels to its destination.
     * @var string 
     */
    const STATUS_IN_TRANSIT           = 'in_transit';
    /**
     * A package has reached the local area and is in the process of being delivered.
     * @var string 
     */
    const STATUS_OUT_FOR_DELIVERY     = 'out_for_delivery';
    /**
     * The package has been delivered.
     * @var string 
     */
    const STATUS_DELIVERED            = 'delivered';
    /**
     * The package has been delivered to post office but client has to pick it up.
     * @var string 
     */
    const STATUS_AVAILABLE_FOR_PICKUP = 'available_for_pickup';
    /**
     * The package encountered some error during transit. The carrier may still be able to reroute
     * the package successfully.
     * @var string 
     */
    const STATUS_RETURN_TO_SENDER     = 'return_to_sender';
    /**
     * The package is being returned to the sender.
     * @var string 
     */
    const STATUS_FAILURE              = 'failure';
    
    /** @var \Pley\Config\ConfigInterface  */
    protected $_config;
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Mail\AbstractMail  */
    protected $_mailer;
    /** @var \Pley\Dao\User\UserDao  */
    protected $_userDao;
    /** @var \Pley\Dao\User\UserProfileDao  */
    protected $_userProfileDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao  */
    protected $_profileSubsDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionShipmentDao  */
    protected $_profileSubsShipmentDao;
    /** @var \Pley\Subscription\SubscriptionManager  */
    protected $_subscriptionMgr;
    /** @var \Pley\Nps\NpsManagerInterface  */
    protected $_npsManager;
    /* @var \EasyPost\Tracker */
    protected $_tracker;

    protected function _init()
    {
        $this->_config                 = $this->_dependencyMap[EasyPostListenerController::DEPENDENCY_CONFIG];
        $this->_dbManager              = $this->_dependencyMap[EasyPostListenerController::DEPENDENCY_DB];
        $this->_mailer                 = $this->_dependencyMap[EasyPostListenerController::DEPENDENCY_MAIL];
        $this->_userDao                = $this->_dependencyMap[EasyPostListenerController::DEPENDENCY_USER_DAO];
        $this->_userProfileDao         = $this->_dependencyMap[EasyPostListenerController::DEPENDENCY_PROFILE_DAO];
        $this->_profileSubsDao         = $this->_dependencyMap[EasyPostListenerController::DEPENDENCY_PROFILE_SUBSCRIPTION_DAO];
        $this->_profileSubsShipmentDao = $this->_dependencyMap[EasyPostListenerController::DEPENDENCY_PROFILE_SHIPMENT_DAO];
        $this->_subscriptionMgr        = $this->_dependencyMap[EasyPostListenerController::DEPENDENCY_SUBSCRIPTION_MGR];
        $this->_npsManager             = $this->_dependencyMap[EasyPostListenerController::DEPENDENCY_NPS_MGR];

        $this->_tracker = $this->_event->result;
    }
    
    protected function _processDelegate()
    {
        // Checking if the Tracker status is one we are not currently handling
        $unhandledStatusList = [
            self::STATUS_UNKNOWN,
            self::STATUS_PRE_TRANSIT,
            self::STATUS_RETURN_TO_SENDER,
            self::STATUS_FAILURE,
        ];
        if (in_array($this->_tracker->status, $unhandledStatusList)) {
            return false;
        }
        
        // We know it is a status we are handling so let's retrieve the Shipment for the vendor ID
        $profileSubsShipment = $this->_profileSubsShipmentDao->findByVendorShipId($this->_tracker->shipment_id);
        if (empty($profileSubsShipment)) {
            return false;
        }
        
        // Updating the tracker ID if not set yet.
        if (empty($profileSubsShipment->getVendorShipTrackerId())) {
            $profileSubsShipment->setVendorShipTrackerId($this->_tracker->id);
            $this->_profileSubsShipmentDao->save($profileSubsShipment);
        }
        
        // Variable to track whether the event was processed onto the shipment.
        $status = false;
        
        switch ($this->_tracker->status) {
            case self::STATUS_IN_TRANSIT:
            case self::STATUS_OUT_FOR_DELIVERY:
                $status = $this->_handleInTransit($profileSubsShipment);
                break;
            case self::STATUS_DELIVERED:
            case self::STATUS_AVAILABLE_FOR_PICKUP:
                $status = $this->_handleDelivered($profileSubsShipment);
                break;
            default:
                $status = false;
        }
        
        // We want to make sure that any events that depend on shipment progress are notified
        // E.g.: when in-transit, grant nat-geo digital experience mission
        $this->_triggerShipmentProgressEvent($profileSubsShipment);
        
        // If the processing status is TRUE, then return the object reference, otherwise just return false
        if ($status) {
            return $profileSubsShipment;
        }
        
        return false;
    }
    
    private function _handleInTransit(\Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubsShipment)
    {
        // If an in-transit event has been handled already, then no need to do further processing.
        if (!empty($profileSubsShipment->getShippedAt())) {
            return false;
        }
        
        // Retrieving the earliest in transit datetime
        $statusList = [self::STATUS_IN_TRANSIT, self::STATUS_OUT_FOR_DELIVERY];
        $firstInTransitDateTime = $this->_getEarliestDateTime($this->_tracker->tracking_details, $statusList);
        
        $profileSubsShipment->setShippedAt($firstInTransitDateTime);
        $this->_profileSubsShipmentDao->save($profileSubsShipment);
        
        // Now sending collecting info to send the notification email
        $profileSubs  = $this->_profileSubsDao->find($profileSubsShipment->getProfileSubscriptionId());
        $subscription = $this->_subscriptionMgr->getSubscription($profileSubs->getSubscriptionId());
        $user         = $this->_userDao->find($profileSubs->getUserId());
        $userProfile  = $this->_userProfileDao->find($profileSubs->getUserProfileId());

        $mailTagCollection = new \Pley\Mail\MailTagCollection($this->_config);
        $mailTagCollection->addEntity($userProfile);
        $mailTagCollection->addEntity($subscription);

        // setting the tracking url for the email template
        $trackingUrlMap = $this->_config->get('shipping.trackingUrl');
        $carrierUrlTemplate = $trackingUrlMap[$profileSubsShipment->getCarrierId()];
        $carrierTrackingUrl = sprintf($carrierUrlTemplate, $profileSubsShipment->getTrackingNo());
        $mailTagCollection->setCustom('trackingUrl', $carrierTrackingUrl);

        if($profileSubsShipment->getState() === 'HI'){
            $mailTagCollection->setCustom('specialNote', 'When shipping outside of the contiguous 48 states, please allow for an additional 2-3 weeks for shipping.');
        }

        $mailUserTo = \Pley\Mail\MailUser::withUser($user);

        $this->_mailer->send(\Pley\Enum\Mail\MailTemplateEnum::SHIPPING_IN_TRANSIT, $mailTagCollection, $mailUserTo);
        
        return true;
    }
    
    private function _handleDelivered(\Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubsShipment)
    {
        // If an in-transit event has been handled already, then no need to do further processing.
        if (!empty($profileSubsShipment->getDeliveredAt())) {
            return false;
        }
        
        // Retrieving the earliest delivery datetime (it should technically be one, but just in case
        // the carriers send more than one event)
        $statusList = [self::STATUS_DELIVERED, self::STATUS_AVAILABLE_FOR_PICKUP];
        $firstDeliveredDateTime = $this->_getEarliestDateTime($this->_tracker->tracking_details, $statusList);

        $user = $this->_userDao->find($profileSubsShipment->getUserId());
        $subscription = $this->_subscriptionMgr->getSubscription($profileSubsShipment->getSubscriptionId());
        $npsSendDelay = 7 * 24 * 3600; //7 days
        $this->_npsManager->addUserToSchedule($user, true, $npsSendDelay, ['subscription' => $subscription->getName()]);
        
        $profileSubsShipment->setDeliveredAt($firstDeliveredDateTime);
        $this->_profileSubsShipmentDao->save($profileSubsShipment);
        
        return true;
    }
    
    /**
     * Helper method to retrieve the frist TrackingDetail object/array that matches the supplied status
     * @param array    $trackingDetailList
     * @param string[] $statusList
     * @return int
     */
    private function _getEarliestDateTime($trackingDetailList, $statusList)
    {
        $earliestDateTime = 0;
        foreach ($trackingDetailList as $trackingDetail) {
            if (!in_array($trackingDetail->status, $statusList)) {
                continue;
            }
            
            $dateTime = \Pley\Util\Time\DateTime::strToTime($trackingDetail->datetime);
            if ($earliestDateTime == 0 || $dateTime < $earliestDateTime) {
                $earliestDateTime = $dateTime;
            }
        }
        
        return $earliestDateTime;
    }
    
    /**
     * Helper method to trigger events related to shipment progress
     * @param \Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubsShipment
     */
    private function _triggerShipmentProgressEvent(\Pley\Entity\Profile\ProfileSubscriptionShipment $profileSubsShipment)
    {
        \Event::fire(\Pley\Enum\EventEnum::SHIPMENT_PROGRESS, [
            'profileSubscriptionShipment' => $profileSubsShipment
        ]);
    }
}