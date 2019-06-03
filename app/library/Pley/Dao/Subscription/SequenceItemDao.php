<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Dao\Subscription;

use Pley\Db\AbstractDatabaseManager as DatabaseManager;

/**
 * The <kbd>SequenceItemDao</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.Subscription
 * @subpackage Dao
 */
class SequenceItemDao extends \Pley\DataMap\Dao
{
    public function __construct(DatabaseManager $databaseManager)
    {
        parent::__construct($databaseManager);
        
        $this->setEntityClass(\Pley\Entity\Subscription\SequenceItem::class);
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
        $columnName = 'subscription_units_purchased';
        if ($queueItem->getType() == \Pley\Entity\Profile\QueueItem::TYPE_RESERVED) {
            $columnName = 'subscription_units_reserved';
        }
        
        $prepSql = "UPDATE `{$this->_tableName}` "
                 . "SET `{$columnName}` = `{$columnName}` + 1 "
                 . 'WHERE `id` = ? ';
        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute([$sequenceItem->getId()]);
        $pstmt->closeCursor();
        
        $this->_updateUnits($sequenceItem);
    }
    
    /**
     * Frees up a Reserved item from the sequence.
     * @param \Pley\Entity\Subscription\SequenceItem $sequenceItem
     */
    public function freeReservedItem(\Pley\Entity\Subscription\SequenceItem $sequenceItem)
    {
        $prepSql = "UPDATE `{$this->_tableName}` " 
                 . 'SET `subscription_units_reserved` = `subscription_units_reserved` - 1 '
                 . 'WHERE `id` = ? ';
        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute([$sequenceItem->getId()]);
        $pstmt->closeCursor();
        
        $this->_updateUnits($sequenceItem);
    }
    
    /**
     * Frees up a Purchased item from the sequence.
     * <p>Note: This action is only to be taken as a result of CustomerService performing a full cancel.</p>
     * @param \Pley\Entity\Subscription\SequenceItem $sequenceItem
     */
    public function freePurchasedItem(\Pley\Entity\Subscription\SequenceItem $sequenceItem)
    {
        $prepSql = "UPDATE `{$this->_tableName}` " 
                 . 'SET `subscription_units_purchased` = `subscription_units_purchased` - 1 '
                 . 'WHERE `id` = ? ';
        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute([$sequenceItem->getId()]);
        $pstmt->closeCursor();
        
        $this->_updateUnits($sequenceItem);
    }
    
    /**
     * Moves a unit from the Reserved stock into the Purchased stock.
     * @param \Pley\Entity\Subscription\SequenceItem $sequenceItem
     */
    public function reservedToPaidItem(\Pley\Entity\Subscription\SequenceItem $sequenceItem)
    {
        $prepSql = "UPDATE `{$this->_tableName}` SET " 
                 .    '`subscription_units_reserved` = `subscription_units_reserved` - 1, '
                 .    '`subscription_units_purchased` = `subscription_units_purchased` + 1 '
                 . 'WHERE `id` = ? ';
        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute([$sequenceItem->getId()]);
        $pstmt->closeCursor();
        
        $this->_updateUnits($sequenceItem);
    }
    
    /**
     * Helper method to update the subscription units of the supplied sequence item.
     * @param \Pley\Entity\Subscription\SequenceItem $sequenceItem
     */
    private function _updateUnits(\Pley\Entity\Subscription\SequenceItem $sequenceItem)
    {
        $prepSql = 'SELECT `subscription_units_purchased`, `subscription_units_reserved` '
                 . "FROM `{$this->_tableName}` "
                 . 'WHERE `id` = ?';
        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute([$sequenceItem->getId()]);
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        
        $sequenceItem->setSubscriptionUnitsPurchased($dbRecord['subscription_units_purchased']);
        $sequenceItem->setSubscriptionUnitsReserved($dbRecord['subscription_units_reserved']);
    }
}
