<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Repository\Subscription;

/**
 * The <kbd>SubscriptionRepository</kbd> class concentrates functionality to interact with different
 * subscription related objects.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Repository.Subscription
 * @subpackage Repository
 */
class SubscriptionRepository extends \Pley\DataMap\Repository
{
    /** @var \Pley\Dao\Subscription\SubscriptionDao */
    protected $_subscriptionDao;
    /** @var \Pley\Dao\Subscription\SequenceItemDao */
    protected $_subsSequenceItemDao;
    
    public function __construct(
            \Pley\Dao\Subscription\SequenceItemDao $seqItemDao,
            \Pley\Dao\Subscription\SubscriptionDao $subscriptionDao)
    {
        parent::__construct($seqItemDao);
        
        $this->_subsSequenceItemDao = $seqItemDao;
        $this->_subscriptionDao     = $subscriptionDao;
    }
    
    /**
     * Return the <kbd>Subscription</kbd> object for the supplied subscription ID.
     * @param int $subscriptionId
     * @return \Pley\Entity\Subscription\Subscription
     */
    public function getSubscription($subscriptionId)
    {
        return $this->_subscriptionDao->find($subscriptionId);
    }
    
    /**
     * Return a list <kbd>Subscription</kbd> objects for all existing subscriptions.
     * @return \Pley\Entity\Subscription\Subscription[]
     */
    public function getAllSubscriptions()
    {
        return $this->_subscriptionDao->all();
    }
    
    /**
     * Retrieve the Item Sequence (List of <kbd>SequenceItem</kbd> objects) for the supplied subscription ID
     * @param int $subscriptionId
     * @return \Pley\Entity\Subscription\SequenceItem[]
     */
    public function getItemSequence($subscriptionId)
    {
        return $this->_subsSequenceItemDao->where('`subscription_id` = ?', [$subscriptionId]);
    }
    
    /**
     * Returns the Sequence Item for the specified sequence index
     * @param int $subscriptionId
     * @param int $sequenceIndex
     * @return \Pley\Entity\Subscription\SequenceItem
     */
    public function getSequenceItemByIndex($subscriptionId, $sequenceIndex)
    {
        $sequenceItem = null;
        
        $itemSequence = $this->_subsSequenceItemDao->where(
            '`subscription_id` = ? AND `sequence_index` = ?', 
            [$subscriptionId, $sequenceIndex]
        );
        if (!empty($itemSequence)) {
            $sequenceItem = $itemSequence[0];
        }
        
        return $sequenceItem;
    }
    
    /**
     * Increases the count of a purchased or reserved item in the storage.
     * <p>It also updates the reference on the <kbd>$sequenceItem</kbd> object to the new value.</p>
     * @param \Pley\Entity\Subscription\SequenceItem $sequenceItem
     * @param \Pley\Entity\Profile\QueueItem         $queueItem
     */
    public function increaseItemSale(
            \Pley\Entity\Subscription\SequenceItem $sequenceItem, \Pley\Entity\Profile\QueueItem $queueItem)
    {
        $this->_subsSequenceItemDao->increaseItemSale($sequenceItem, $queueItem);
    }
    
    /**
     * Frees up a Reserved item from the sequence.
     * @param \Pley\Entity\Subscription\SequenceItem $sequenceItem
     */
    public function freeReservedItem(\Pley\Entity\Subscription\SequenceItem $sequenceItem)
    {
        if($sequenceItem->getStoreUnitsReserved() !== 0){
            $this->_subsSequenceItemDao->freeReservedItem($sequenceItem);
        }
    }
    
    /**
     * Frees up a Purchased item from the sequence.
     * <p>Note: This action is only to be taken as a result of CustomerService performing a full cancel.</p>
     * @param \Pley\Entity\Subscription\SequenceItem $sequenceItem
     */
    public function freePurchasedItem(\Pley\Entity\Subscription\SequenceItem $sequenceItem)
    {
        $this->_subsSequenceItemDao->freePurchasedItem($sequenceItem);
    }
    
    /**
     * Moves a unit from the Reserved stock into the Purchased stock.
     * @param \Pley\Entity\Subscription\SequenceItem $sequenceItem
     */
    public function reservedToPaidItem(\Pley\Entity\Subscription\SequenceItem $sequenceItem)
    {
        $this->_subsSequenceItemDao->reservedToPaidItem($sequenceItem);
    }
}
