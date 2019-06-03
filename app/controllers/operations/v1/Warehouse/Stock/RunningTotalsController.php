<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace operations\v1\Warehouse\Stock;

use Pley\Entity\Subscription\Item;
use Pley\Enum\ShirtSizeEnum;
use Pley\Enum\SubscriptionItemPullEnum;
use Pley\Repository\Operations\ReportsRepository;

/**
 * The <kbd>RunningTotalsController</kbd> responsible on making stock running totals statistics
 * and feed it to frontend
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class RunningTotalsController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Operations\StockManager */
    protected $stockManager;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionShipmentDao */
    protected $_profileSubsShipDao;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionMgr;
    /** @var \Pley\Dao\Subscription\ItemDao */
    protected $_subscriptionItemDao;
    /** @var \Pley\Dao\User\UserProfileDao * */
    protected $_userProfileDao;
    /** @var \Pley\Repository\Operations\ReportsRepository * */
    protected $_reportsRepository;


    public function __construct(
        \Pley\Operations\StockManager $stockManager,
        \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubsDao,
        \Pley\Dao\Profile\ProfileSubscriptionShipmentDao $profileSubsShipDao,
        \Pley\Subscription\SubscriptionManager $subscriptionMgr,
        \Pley\Dao\User\UserProfileDao $userProfileDao,
        \Pley\Dao\Subscription\ItemDao $subscriptionItemDao,
        \Pley\Repository\Operations\ReportsRepository $reportsRepository
    )
    {
        parent::__construct();
        $this->stockManager = $stockManager;
        $this->_profileSubsDao = $profileSubsDao;
        $this->_profileSubsShipDao = $profileSubsShipDao;
        $this->_subscriptionMgr = $subscriptionMgr;
        $this->_subscriptionItemDao = $subscriptionItemDao;
        $this->_userProfileDao = $userProfileDao;
        $this->_reportsRepository = $reportsRepository;
    }

    // GET /warehouse/subscription/in-order
    public function getOrderedSubscriptions()
    {
        $response = [];
        $subscriptionsList = $this->stockManager->getSubscriptionsList();
        foreach ($subscriptionsList as $subscription) {
                $response[] = $subscription->toArray();
        }
        return \Response::json($response);
    }

    // GET /warehouse/subscription/{subscriptionId}/running-totals
    public function getRunningTotals($subscriptionId)
    {
        $subscription = $this->_subscriptionMgr->getSubscription($subscriptionId);
        $activePeriodIndex = $this->_subscriptionMgr->getActivePeriodIndex($subscription);
        $fullItemSequence = $this->_subscriptionMgr->getItemSequence($subscription);

        $response = [
            'scheduleIndex' => []
        ];

        foreach ($fullItemSequence as $itemSequence) {
            $scheduleIndex = $itemSequence->getPeriodIndex();
            $response['scheduleIndex'][$scheduleIndex] =
                [
                    'deliveryStartDate' => \Pley\Util\DateTime::date($itemSequence->getDeliveryStartTime()),
                    'paidItems' => [],
                    'reservedItems' => [],
                ];
            foreach ($fullItemSequence as $itemSeq) {
                if(!$itemSeq->getItemId()){
                    continue;
                }
                $item = $this->_subscriptionItemDao->find($itemSeq->getItemId());

                if($subscription->getItemPullType() === SubscriptionItemPullEnum::BY_SCHEDULE){
                    if($itemSequence->getItemId() !== $item->getId()){
                        continue;
                    }
                }
                $sizes = $this->_reportsRepository->getSizesScheduledForPeriod(
                    $subscription->getId(),
                    $scheduleIndex,
                    $itemSeq->getSequenceIndex()
                );

                $itemCounts = [
                    'name' => $item->getName(),
                    'count' => 0,
                    'shirtSizes' => [
                        ShirtSizeEnum::XXS => 0,
                        ShirtSizeEnum::XS => 0,
                        ShirtSizeEnum::S => 0,
                        ShirtSizeEnum::M => 0,
                        ShirtSizeEnum::L => 0,
                        ShirtSizeEnum::XL => 0
                    ]
                ];
                foreach ($sizes as $size){
                    $itemCounts['shirtSizes'][$size['id']] = $size['count'];
                    $itemCounts['count']+= $size['count'];
                }
                $response['scheduleIndex'][$scheduleIndex]['paidItems'][$item->getId()] = $itemCounts;
            }
        }
        return \Response::json($response);
    }
}