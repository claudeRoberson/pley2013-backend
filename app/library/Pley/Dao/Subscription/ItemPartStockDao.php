<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Dao\Subscription;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;

/**
 * The <kbd>ItemPartStockDao</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package 
 * @subpackage
 */
class ItemPartStockDao extends AbstractDbDao implements DbDaoInterface, DaoInterface
{
    /** @var string */
    protected $_tableName = 'item_part_stock';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
    
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'item_id', 'item_part_id', 'type_item_part_id', 'type_item_part_source_id', 
            'inducted_stock', 'stock',  
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /**
     * Return the <kbd>ItemPartStock</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\Subscription\ItemPartStock
     */
    public function find($id)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . "WHERE `id` = ?";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$id];

        $pstmt->execute($bindings);
        
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $entity = $this->_toEntity($dbRecord);
        
        return $entity;
    }
    
    /**
     * Return a list of <kbd>ItemPartStock</kbd> entity for the supplied Item id
     * @param int $itemId
     * @return \Pley\Entity\Subscription\ItemPartStock
     */
    public function findByItem($itemId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . "WHERE `item_id` = ?";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$itemId];

        $pstmt->execute($bindings);
        
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }
        
        return $resultSet;
    }
    
    /**
     * Return a list of <kbd>ItemPartStock</kbd> entity for the supplied Item id
     * @param int $itemPartId
     * @return \Pley\Entity\Subscription\ItemPartStock[]
     */
    public function findByItemPart($itemPartId)
    {
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . "WHERE `item_part_id` = ?";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$itemPartId];

        $pstmt->execute($bindings);
        
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount  = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }
        
        return $resultSet;
    }

    /**
     * increases the Inducted and Available Stock of the supplied ItemPartStock entry by the give amount.
     * @param int $id
     * @param int $amount
     */
    public function increaseInductedStock($id, $amount)
    {
        if ($amount < 1 || !is_int($amount)) {
            throw new \InvalidArgumentException('Amount cannot be lower than 1 or a float number.');
        }
        
        $prepSql  = "UPDATE `{$this->_tableName}` "
                  . "SET `inducted_stock` = `inducted_stock` + ?, "
                  . "    `stock` = `stock` + ? "
                  . "WHERE `id` = ?";
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$amount, $amount, $id];

        $pstmt->execute($bindings);
    }
    
    /**
     * Decreases the Stock of the supplied ItemPartStock entry
     * @param \Pley\Entity\Subscription\ItemPart $part
     * @param int                                $partTypeSourceId (Optional)<br/>Required if 
     *      <kbd>$part->getType()</kbd> is different from the `Generic` type.
     */
    public function decreaseStock(\Pley\Entity\Subscription\ItemPart $part, $partTypeSourceId = null)
    {
        // Generic type does not use a Type Source, however, because NULLs are not allowed on the
        // DB for the sake of keeping the Unique Key intact, we need to set it to 0
        if ($part->getType() == \Pley\Enum\ItemPartEnum::GENERIC) {
            $partTypeSourceId = 0;
        }
        
        $prepSql  = "UPDATE `{$this->_tableName}` "
                  . 'SET `stock` = `stock` - 1 '
                  . 'WHERE `item_id` = ? '
                  .   'AND `item_part_id` = ? '
                  .   'AND `type_item_part_id` = ? '
                  .   'AND `type_item_part_source_id` = ? ';
        $bindings = [
            $part->getItemId(), 
            $part->getId(), 
            $part->getType(),
            $partTypeSourceId,
        ];
        
        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute($bindings);
        $rowCount = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        // If there was NO record updated, means there was never a stock inducted for this part,
        // so, to keep track of this decrease, we need to add the data entry with no stock and then
        // retry the deduction to log.
        if ($rowCount == 0) {
            $emptyStockAmount = 0;
            $this->addStock($part, $emptyStockAmount, $partTypeSourceId);
            $this->decreaseStock($part, $partTypeSourceId);
        }
    }
    
    public function addStock(\Pley\Entity\Subscription\ItemPart $part, $amount, $partTypeSourceId = null)
    {
        // Generic type does not use a Type Source, however, because NULLs are not allowed on the
        // DB for the sake of keeping the Unique Key intact, we need to set it to 0
        if ($part->getType() == \Pley\Enum\ItemPartEnum::GENERIC) {
            $partTypeSourceId = 0;
        }
        
        $prepSql  = "UPDATE `{$this->_tableName}` "
                  . 'SET `inducted_stock` = `inducted_stock` + ?, '
                  . '    `stock` = `stock` + ? '
                  . 'WHERE `item_id` = ? '
                  .   'AND `item_part_id` = ? '
                  .   'AND `type_item_part_id` = ? '
                  .   'AND `type_item_part_source_id` = ?  ';
        $bindings = [
            $amount,
            $amount,
            $part->getItemId(), 
            $part->getId(), 
            $part->getType(),
            $partTypeSourceId,
        ];
        
        
        $pstmt = $this->_prepare($prepSql);
        $pstmt->execute($bindings);
        $rowCount = $pstmt->rowCount();
        $pstmt->closeCursor();
        
        // If there was a record updated, we are good to return
        if ($rowCount > 0) {
            return;
        }
        
        // Otherwise, it means that such part did not have an inducted stock, so to be able to keep
        // track of this increase, we need to add an entry into the stock table
        $insertSql = "INSERT INTO `{$this->_tableName}` ("
                   .    '`item_id`, '
                   .    '`item_part_id`, '
                   .    '`type_item_part_id`, '
                   .    '`type_item_part_source_id`, '
                   .    '`inducted_stock`, '
                   .    '`stock`, '
                   .    '`created_at`) '
                   . 'VALUES (?, ?, ?, ?, ?, ?, NOW())';
        $insertBindings = [
            $part->getItemId(), 
            $part->getId(), 
            $part->getType(),
            $partTypeSourceId,
            $amount,
            $amount,
        ];
        $insertPstmt = $this->_prepare($insertSql);
        $insertPstmt->execute($insertBindings);
        $pstmt->closeCursor();
    }
    
    /**
     * Map an associative array DB record into a <kbd>ItemPart</kbd> Entity.
     * 
     * @param array $dbRecord
     * @return \Pley\Entity\Subscription\ItemPart
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        return new \Pley\Entity\Subscription\ItemPartStock(
            $dbRecord['id'],
            $dbRecord['item_id'],
            $dbRecord['item_part_id'],
            $dbRecord['type_item_part_id'],
            $dbRecord['type_item_part_source_id'] == 0 ? null : $dbRecord['type_item_part_source_id'],
            $dbRecord['inducted_stock'],
            $dbRecord['stock']
        );
    }
}
