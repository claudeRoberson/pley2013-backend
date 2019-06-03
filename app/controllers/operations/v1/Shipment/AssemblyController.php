<?php /** @copyright Pley (c) 2016, All Rights Reserved */
namespace operations\v1\Shipment;

use \Pley\Db\AbstractDatabaseManager as DatabaseManager;

/** ♰
 * The <kbd>AssemblyController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class AssemblyController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\Dao\User\UserProfileDao */
    protected $_userProfileDao;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionMgr;
    /** @var \Pley\User\ProfileShipmentManager */
    protected $_profileShipmentMgr;
    
    public function __construct(
            DatabaseManager $dbManager,
            \Pley\Dao\User\UserProfileDao $userProfileDao,
            \Pley\Subscription\SubscriptionManager $subscriptionMgr,
            \Pley\User\ProfileShipmentManager $profileShipmentMgr)
    {
        parent::__construct();
        
        $this->_dbManager = $dbManager;
        
        $this->_userProfileDao = $userProfileDao;
        
        $this->_subscriptionMgr    = $subscriptionMgr;
        $this->_profileShipmentMgr = $profileShipmentMgr;
    }
    
    // GET /assemby/subscription
    public function getSubscriptionList()
    {
        \RequestHelper::checkGetRequest();
        
        $subscriptionList = $this->_subscriptionMgr->getAllSubscriptions();
        
        $subscriptionMap = [];
        foreach ($subscriptionList as $subscription) {
            $subscriptionMap[$subscription->getId()] = [
                'name' => $subscription->getName()
            ];
        }
        
        return \Response::json(['subscriptionMap' => $subscriptionMap]);
    }
    
    // GET /assemby/subscription/{intId}/active-schedule
    public function activeSchedule($subscriptionId)
    {
        \RequestHelper::checkGetRequest();
        
        $subscription        = $this->_subscriptionMgr->getSubscription($subscriptionId);
        $activeShipPeriodIdx = $this->_subscriptionMgr->getActiveShippablePeriodIndex($subscription);
        $itemList            = $this->_subscriptionMgr->getShippableItemList($subscription);
        
        $itemMap = [];
        foreach ($itemList as $item) {
            $itemMap[$item->getId()] = ['name' => $item->getName()];
        }
        
        $periodDefGrp = $this->_subscriptionMgr->getSubscriptionDates($subscription, $activeShipPeriodIdx);
        $deadlineTime      = $periodDefGrp->getDeadlinePeriodDef()->getTimestamp();
        $deliveryStartTime = $periodDefGrp->getDeliveryStartPeriodDef()->getTimestamp();
        $deliveryEndTime   = $periodDefGrp->getDeliveryEndPeriodDef()->getTimestamp();
        
        $responseStructure = [
            'subscriptionMap' => [
                'id'   => $subscription->getId(),
                'name' => $subscription->getName(),
            ],
            'deliveryDateMap' => [
                'currentDeadlineTime'      => $deadlineTime,
                'currentDeliveryStartTime' => $deliveryStartTime,
                'currentDeliveryEndTime'   => $deliveryEndTime,
            ],
            'itemMap' => $itemMap,
        ];
        
        return \Response::json($responseStructure);
    }
    
    // GET /assemby/subscription/{intId}/active-schedule/item/{intId}
    public function activeScheduleForItem($subscriptionId, $itemId)
    {   
        $subscription        = $this->_subscriptionMgr->getSubscription($subscriptionId);
        $item                = $this->_subscriptionMgr->getItem($itemId, true);
        $activeShipPeriodIdx = $this->_subscriptionMgr->getActiveShippablePeriodIndex($subscription);
        
        $periodDefGrp = $this->_subscriptionMgr->getSubscriptionDates($subscription, $activeShipPeriodIdx);
        $deadlineTime      = $periodDefGrp->getDeadlinePeriodDef()->getTimestamp();
        $deliveryStartTime = $periodDefGrp->getDeliveryStartPeriodDef()->getTimestamp();
        $deliveryEndTime   = $periodDefGrp->getDeliveryEndPeriodDef()->getTimestamp();

        $shipmentByShirtSizeList = $this->_subscriptionMgr->getShipmentCountByShirtSize(
            $subscription, $item, $activeShipPeriodIdx
        );
        $shipmentByStatusList    = $this->_subscriptionMgr->getShipmentCountByStatus(
            $subscription, $item, $activeShipPeriodIdx
        );
        
        $totalNotProcessedCount = 0;
        foreach ($shipmentByShirtSizeList as $countMap) {
            $totalNotProcessedCount += $countMap['count'];
        }
        
        $partList = [];
        foreach ($item->getPartList() as $part) {
            $partList[] = [
                'name'     => $part->getName(),
                'image'    => $part->getImage(),
                'type'     => $part->getType(),
                'stock'    => $part->getStock(),
                'stockDef' => $part->getType() != 1? $part->getStockDef() : null,
            ];
        }
        
        $responseStructure = [
            'subscription' => [
                'id'          => $subscription->getId(),
                'name'        => $subscription->getName(),
                'description' => $subscription->getDescription(),
            ],
            'scheduleItem' => [
                'currentDeadlineTime'      => $deadlineTime,
                'currentDeliveryStartTime' => $deliveryStartTime,
                'currentDeliveryEndTime'   => $deliveryEndTime,
                'totalNotProcessedCount'   => $totalNotProcessedCount,
            ],
            'item' => [
                'id'                     => $item->getId(),
                'name'                   => $item->getName(),
                'description'            => $item->getDescription(),
                'partList'               => $partList,
            ],
            'shipmentByShirtSizeList' => $shipmentByShirtSizeList,
            'shipmentByStatusList'    => $shipmentByStatusList,
        ];
        
        return \Response::json($responseStructure);
    }
    
    // PUT /assembly/subscription/{intId}/item/{intId2}/process
    public function processItemAndNext($subscriptionId, $itemId)
    {
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();
        
        // Getting the JSON input as an assoc array
        $json = \Input::json()->all();
        
        $rules = [
            'shirtSizeId'    => 'required|integer|in:' . implode(',', array_values(\Pley\Enum\ShirtSizeEnum::constantsMap())),
            'shipmentId'     => 'integer',
        ];
        \ValidationHelper::validate($json, $rules);
        
        $responseMap = [
            'processedStatus'     => false,
            'nextProfileShipment' => null
        ];
        
        if (!empty($json['shipmentId'])) {
            $this->_flagShipmentAsProcessed($json['shipmentId']);
            $responseMap['processedStatus'] = true;
        }
        
        $subscription        = $this->_subscriptionMgr->getSubscription($subscriptionId);
        $activeShipPeriodIdx = $this->_subscriptionMgr->getActiveShippablePeriodIndex($subscription);
        
        $nextProfileShipment = $this->_profileShipmentMgr->getNextShipmentToProcess(
            $subscriptionId, $activeShipPeriodIdx, $itemId, $json['shirtSizeId']
        );
        
        if (isset($nextProfileShipment)) {
            $profile = $this->_userProfileDao->find($nextProfileShipment->getProfileId());
            
            $responseMap['nextProfileShipment'] = [
                'id'          => $nextProfileShipment->getId(),
                'usps_zone'   => $nextProfileShipment->getUspsShippingZoneId(),
                'carrierId'   => $nextProfileShipment->getCarrierId(),
                'profileMap'  => [
                    'firstName' => $profile->getFirstName(),
                    'lastName'  => $profile->getLastName(),
                ],
                'addressMap'  => [
                    'street1' => $nextProfileShipment->getStreet1(),
                    'street2' => $nextProfileShipment->getStreet2(),
                    'city'    => $nextProfileShipment->getCity(),
                    'state'   => $nextProfileShipment->getState(),
                    'zip'     => $nextProfileShipment->getZip(),
                    'country' => $nextProfileShipment->getCountry(),
                ],
                'zplLabel'    => $this->_getZplContents($nextProfileShipment->getLabelUrl()),
            ];
        }
        
        return \Response::json($responseMap);
    }



    // PUT /assembly/subscription/{intId}/item/{intId2}/batch
    public function batchProcess($subscriptionId, $itemId)
    {
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();

        // Getting the JSON input as an assoc array
        $json = \Input::json()->all();

        $rules = [
            'shirtSizeId'    => 'required|integer|in:' . implode(',', array_values(\Pley\Enum\ShirtSizeEnum::constantsMap())),
        ];
        \ValidationHelper::validate($json, $rules);

        $responseMap = [
            'processedBatch' => []
        ];

        $subscription        = $this->_subscriptionMgr->getSubscription($subscriptionId);
        $activeShipPeriodIdx = $this->_subscriptionMgr->getActiveShippablePeriodIndex($subscription);

        $nextShipmentsBatch = $this->_profileShipmentMgr->getNextShipmentBatchToProcess(
            $subscriptionId, $activeShipPeriodIdx, $itemId, $json['shirtSizeId']
        );
        if (isset($nextShipmentsBatch)) {
            foreach ($nextShipmentsBatch as $nextProfileShipment){
                $this->_flagShipmentAsProcessed($nextProfileShipment->getId());
                $responseMap['processedBatch'][] = [
                    'id'          => $nextProfileShipment->getId(),
                    'zplLabel'    => $this->_getZplContents($nextProfileShipment->getLabelUrl()),
                ];
            }
        }

        return \Response::json($responseMap);
    }

    // PUT /assembly/subscription/{intId}/item/{intId2}/batch/new
    public function batchProcessNewOnly($subscriptionId, $itemId)
    {
        \RequestHelper::checkPutRequest();
        \RequestHelper::checkJsonRequest();

        // Getting the JSON input as an assoc array
        $json = \Input::json()->all();

        $rules = [
            'shirtSizeId'    => 'required|integer|in:' . implode(',', array_values(\Pley\Enum\ShirtSizeEnum::constantsMap())),
        ];
        \ValidationHelper::validate($json, $rules);

        $responseMap = [
            'processedBatch' => []
        ];

        $subscription        = $this->_subscriptionMgr->getSubscription($subscriptionId);
        $activeShipPeriodIdx = $this->_subscriptionMgr->getActiveShippablePeriodIndex($subscription);

        $nextShipmentsBatch = $this->_profileShipmentMgr->getNextShipmentBatchToProcess(
            $subscriptionId, $activeShipPeriodIdx, $itemId, $json['shirtSizeId'], true
        );
        if (isset($nextShipmentsBatch)) {
            foreach ($nextShipmentsBatch as $nextProfileShipment){
                $this->_flagShipmentAsProcessed($nextProfileShipment->getId());
                $responseMap['processedBatch'][] = [
                    'id'          => $nextProfileShipment->getId(),
                    'zplLabel'    => $this->_getZplContents($nextProfileShipment->getLabelUrl()),
                ];
            }
        }

        return \Response::json($responseMap);
    }
    
    // GET /assembly/label-img/{intId}
    public function getPngLabel($profileShipmentId)
    {
        $profileShipment = $this->_profileShipmentMgr->getShipment($profileShipmentId);
        $b64PngLabel = \Pley\Util\Shipping\ShippingLabel::convert($profileShipment->getLabelUrl());
        
        return \Response::json(['b64PngLabel' => $b64PngLabel]);
    }
    
    private function _flagShipmentAsProcessed($profileShipmentId)
    {
        $that = $this;
        
        $this->_dbManager->transaction(function() use ($that, $profileShipmentId) {
            $profileShipment = $that->_profileShipmentMgr->getShipment($profileShipmentId);
            $profileShipment->setStatus(\Pley\Enum\Shipping\ShipmentStatusEnum::PROCESSED);
            $that->_profileShipmentMgr->updateShipment($profileShipment);

            $that->_subscriptionMgr->decreaseStock($profileShipment);
        });
    }
    
    private function _getZplContents($labelUrl)
    {
        $client      = new \GuzzleHttp\Client();
        $zplResponse = $client->get($labelUrl);
        $body        = $zplResponse->getBody()->getContents();

        return $body;
    }
}
