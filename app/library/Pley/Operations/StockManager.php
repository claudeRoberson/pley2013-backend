<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Operations;

use Pley\Dao\Subscription\ItemDao;
use Pley\Dao\Subscription\ItemPartDao;
use Pley\Dao\Subscription\ItemPartStockDao;
use Pley\Dao\Subscription\SubscriptionDao;
use Pley\Entity\Stock\InductionLog;
use Pley\Repository\Stock\InductionLogRepository;

/**
 * The <kbd>StockManager</kbd> class
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class StockManager
{
    /**
     * @var $_subscriptionDao SubscriptionDao
     */
    protected $_subscriptionDao;

    /**
     * @var $_itemDao ItemDao
     */
    protected $_itemDao;

    /**
     * @var $_itemPartDao ItemPartDao
     */
    protected $_itemPartDao;

    /**
     * @var $_itemPartStockDao ItemPartStockDao
     */
    protected $_itemPartStockDao;

    /**
     * @var $_inductionLogRepository InductionLogRepository
     */
    protected $_inductionLogRepository;

    public function __construct(
        SubscriptionDao $subscriptionDao,
        ItemDao $itemDao,
        ItemPartDao $itemPartDao,
        ItemPartStockDao $itemPartStockDao,
        InductionLogRepository $inductionLogRepository)
    {
        $this->_subscriptionDao = $subscriptionDao;
        $this->_itemDao = $itemDao;
        $this->_itemPartDao = $itemPartDao;
        $this->_itemPartStockDao = $itemPartStockDao;
        $this->_inductionLogRepository = $inductionLogRepository;
    }

    /**
     * Get list of subscriptions including their child boxes
     * @return \Pley\Entity\Subscription\Subscription[]
     */
    public function getSubscriptionsList()
    {
        $subscriptions = $this->_subscriptionDao->all();
        foreach ($subscriptions as $subscription) {
            $subscription->setBoxes($this->_itemDao->findBySubscriptionId($subscription->getId()));
        }
        return $subscriptions;
    }

    /**
     * Get list of boxes including their child stock items
     * @param $boxId
     * @return \Pley\Entity\Subscription\ItemPart[]
     */
    public function getBoxPartsList($boxId)
    {
        $item = $this->_itemDao->find($boxId);
        $parts = $this->_itemPartDao->all($item);

        foreach ($parts as $part) {
            $part->setStockItems($this->_itemPartStockDao->findByItemPart($part->getId()));
        }
        return $parts;
    }

    /**
     * Get list of box induction log entries
     * @param $boxId
     * @return \Pley\Entity\Stock\InductionLog[]
     */
    public function getBoxInductionsList($boxId)
    {

        $item = $this->_itemDao->find($boxId);
        $inductions = $this->_inductionLogRepository->findByItem($item->getId());
        return $inductions;
    }

    /**
     * Creates an induction log entry
     * @param $inductionData
     * @return InductionLog
     */
    public function createStockInduction($inductionData)
    {
        $itemPartStockId = $inductionData['itemPartStockId'];
        $amount = $inductionData['amount'];
        if ($amount < 1 || !is_int($amount)) {
            throw new \InvalidArgumentException('Amount cannot be lower than 1.');
        }
        $this->_itemPartStockDao->increaseInductedStock($itemPartStockId, $amount);
        $logEntry = new InductionLog();
        $logEntry->fill($inductionData);
        return $this->_inductionLogRepository->save($logEntry);
    }

    public function getSubscriptionRunningTotals($subscriptionId){
        //TODO: total items to ship calculations here
    }
}