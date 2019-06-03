<?php /** @copyright Pley (c) 2015, All Rights Reserved */

namespace Pley\Dao\Payment;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Traits\CacheDaoTrait;
use \Pley\Entity\Payment\PaymentPlan;

/**
 * The <kbd>PaymentPlanDao</kbd> class provides implementation to interact with the payment plan table
 * in the DB and Cache.
 *
 * @author Anurag Phadke (anuragp@pley.com)
 * @version 1.0
 * @package Pley.Dao.Payment
 * @subpackage Dao
 */
class PaymentPlanDao extends AbstractDbDao implements DbDaoInterface, CacheDaoInterface
{
    use CacheDaoTrait; // Provides implementation for CacheDaoInterface

    /** @var string */
    protected $_tableName = 'payment_plan';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;

    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'subscription_id', 'title', 'description', 'period', 'period_unit', 'sort_order', 'is_featured',
        ]);

        $this->_columnNames = implode(',', $escapedColumnNames);
    }

    /**
     * Return the <kbd>PaymentPlan</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\Payment\PaymentPlan
     */
    public function find($id)
    {
        if ($this->_cache->has($id)) {
            return $this->_cache->get($id);
        }

        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . "WHERE `id` = ?";
        $pstmt = $this->_prepare($prepSql);
        $bindings = [$id];

        $pstmt->execute($bindings);

        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        $paymentPlanEntity = $this->_toEntity($dbRecord);

        // Now that we have retrieved the Entity, let's add it to the cache
        $this->_cache->set($id, $paymentPlanEntity);

        return $paymentPlanEntity;
    }

    /**
     * Return all <kbd>PaymentPlan</kbd> entities.
     * @return \Pley\Entity\Payment\PaymentPlan[]
     */
    public function all()
    {
        $result = [];
        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}`";
        $pstmt = $this->_prepare($prepSql);
        $bindings = [];

        $pstmt->execute($bindings);

        $rows = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        foreach ($rows as $row) {
            $result[] = $this->_toEntity($row);
        }
        return $result;
    }

    /**
     * Map an associative array DB record into a <kbd>PaymentPlan</kbd> Entity.
     *
     * @param array $dbRecord
     * @return \Pley\Entity\Payment\PaymentPlan
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }

        return new PaymentPlan(
            $dbRecord['id'],
            $dbRecord['subscription_id'],
            $dbRecord['title'],
            $dbRecord['description'],
            $dbRecord['period'],
            \Pley\Enum\PeriodUnitEnum::fromString($dbRecord['period_unit']),
            $dbRecord['sort_order'],
            $dbRecord['is_featured']
        );
    }
}