<?php /** @copyright Pley (c) 2015, All Rights Reserved */

namespace Pley\Dao\Payment;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\CacheDaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Traits\CacheDaoTrait;

/**
 * The <kbd>PaymentPlanXVendorPaymentPlanDao</kbd> class provides implementation to interact with the User table in the DB and Cache.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.User
 * @subpackage Dao
 */
class PaymentPlanXVendorPaymentPlanDao extends AbstractDbDao implements DbDaoInterface, CacheDaoInterface
{
    use CacheDaoTrait; // Provides implementation for CacheDaoInterface

    /** @var string */
    protected $_tableName = 'payment_plan_x_vendor_payment_plan';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;

    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id',
            'payment_plan_id',
            'shipping_zone_id',
            'base_price',
            'unit_price',
            'shipping_price',
            'total',
            'v_payment_system_id',
            'v_payment_plan_id'
        ]);
        
        $this->_columnNames = implode(',', $escapedColumnNames);
    }

    /**
     * Return the <kbd>VendorPaymentPlan</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\Payment\VendorPaymentPlan|null
     */
    public function find($id)
    {
        // Reading from cache first
        if ($this->_cache->has($id)) {
            return $this->_cache->get($id);
        }

        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `id` = ?';
        $pstmt = $this->_prepare($prepSql);
        $bindings = [$id];

        $pstmt->execute($bindings);

        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        $entity = $this->_toEntity($dbRecord);

        // Now that we have retrieved the Entity, let's add it to the cache
        $this->_cache->set($id, $entity);

        return $entity;
    }

    /**
     * Return the <kbd>VendorPaymentPlan</kbd> entity for the supplied
     * payment plan ID and shippingZone ID on the given
     * vendor payment system.
     * @param int $paymentPlanId
     * @param int $shippingZoneId
     * @param int $vPaymentSystemId
     * @return \Pley\Entity\Payment\VendorPaymentPlan
     */
    public function findByPaymentPlan($paymentPlanId, $shippingZoneId, $vPaymentSystemId)
    {
        $cacheKey = "pp:{$paymentPlanId}::{$shippingZoneId}::{$vPaymentSystemId}";
        // Reading from cache first
        if ($this->_cache->has($cacheKey)) {
            return $this->_cache->get($cacheKey);
        }

        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `payment_plan_id` = ? '
            . 'AND `shipping_zone_id` = ? '
            . 'AND `v_payment_system_id` = ? '
            . 'AND `active` = 1';
        $pstmt = $this->_prepare($prepSql);
        $bindings = [
            $paymentPlanId,
            $shippingZoneId,
            $vPaymentSystemId,
        ];

        $pstmt->execute($bindings);

        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        $entity = $this->_toEntity($dbRecord);

        // Now that we have retrieved the Entity, let's add it to the cache
        $this->_cache->set($cacheKey, $entity);

        return $entity;
    }

    /**
     * Return the <kbd>VendorPaymentPlan</kbd> entity for the supplied
     * payment plan ID and shippingZone ID on the given
     * vendor payment system.
     * @param int $paymentPlanId
     * @return \Pley\Entity\Payment\VendorPaymentPlan[]
     */
    public function findAllByPaymentPlan($paymentPlanId)
    {
        $result = [];
        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `payment_plan_id` = ? '
            . 'AND `active` = 1';
        $pstmt = $this->_prepare($prepSql);
        $bindings = [
            $paymentPlanId
        ];

        $pstmt->execute($bindings);

        $rows = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        foreach ($rows as $row){
            $result[] = $this->_toEntity($row);
        }
        return $result;
    }

    /**
     * Return the <kbd>VendorPaymentPlan</kbd> entity for the supplied
     * vendor payment plan id
     * @param $vPaymentPlanId
     * @return \Pley\Entity\Payment\VendorPaymentPlan
     */
    public function findByVendorPaymentPlanId($vPaymentPlanId)
    {
        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . 'WHERE `v_payment_plan_id` = ? ';
        $pstmt = $this->_prepare($prepSql);
        $bindings = [
            $vPaymentPlanId,
        ];
        $pstmt->execute($bindings);

        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        $entity = $this->_toEntity($dbRecord);

        return $entity;
    }

    /**
     * Takes a <kbd>VendorPaymentPlan</kbd> entity object and saves it into the Storage.
     * <p>Saving could imply adding or updating based on the entity supplied; if the entity has a
     * set ID, it will produce an Update, otherwise it will produce an Insert and the entity will
     * be updated with the newly generated id.</p>
     * <p>The method also does an entity type check to do some error validation before run time</p>
     *
     * @param \Pley\Entity\Payment\VendorPaymentPlan $vendorPaymentPlan The Entity object to save
     */
    public function save(\Pley\Entity\Payment\VendorPaymentPlan $vendorPaymentPlan)
    {
        // Depending on the Entity ID value, either update or insert.
        if (empty($vendorPaymentPlan->getId())) {
            $this->_insert($vendorPaymentPlan);
        } else {
            $this->_update($vendorPaymentPlan);
        }
    }

    /**
     * Helper method to perform <kbd>VendorPaymentPlan</kbd> inserts
     * @param \Pley\Entity\Payment\VendorPaymentPlan $vendorPaymentPlan
     */
    private function _insert(\Pley\Entity\Payment\VendorPaymentPlan $vendorPaymentPlan)
    {
        $prepSql  = "INSERT INTO `{$this->_tableName}` ("
            .     '`payment_plan_id`, '
            .     '`shipping_zone_id`, '
            .     '`base_price`, '
            .     '`unit_price`, '
            .     '`shipping_price`, '
            .     '`total`, '
            .     '`v_payment_system_id`, '
            .     '`v_payment_plan_id` '
            . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $pstmt    = $this->_prepare($prepSql);

        // converting the Object to a JSON String
        $bindings = [
            $vendorPaymentPlan->getPaymentPlanId(),
            $vendorPaymentPlan->getShippingZoneId(),
            $vendorPaymentPlan->getBasePrice(),
            $vendorPaymentPlan->getUnitPrice(),
            $vendorPaymentPlan->getShippingPrice(),
            $vendorPaymentPlan->getTotal(),
            $vendorPaymentPlan->getVPaymentSystemId(),
            $vendorPaymentPlan->getVPaymentPlanId(),
        ];

        $pstmt->execute($bindings);

        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $vendorPaymentPlan->setId($id);
    }

    /**
     * Helper method to perform <kbd>VendorPaymentPlan</kbd> updates
     * @param \Pley\Entity\Payment\VendorPaymentPlan $vendorPaymentPlan
     */
    private function _update(\Pley\Entity\Payment\VendorPaymentPlan $vendorPaymentPlan)
    {
        $prepSql  = "UPDATE `{$this->_tableName}` "
            . 'SET `payment_plan_id` = ?, '
            .     '`shipping_zone_id` = ?, '
            .     '`base_price` = ?, '
            .     '`unit_price` = ?, '
            .     '`shipping_price` = ?, '
            .     '`total` = ?, '
            .     '`v_payment_system_id` = ?, '
            .     '`v_payment_plan_id` = ? '
            . 'WHERE `id` = ?';
        $pstmt    = $this->_prepare($prepSql);

        $bindings = [
            $vendorPaymentPlan->getPaymentPlanId(),
            $vendorPaymentPlan->getShippingZoneId(),
            $vendorPaymentPlan->getBasePrice(),
            $vendorPaymentPlan->getUnitPrice(),
            $vendorPaymentPlan->getShippingPrice(),
            $vendorPaymentPlan->getTotal(),
            $vendorPaymentPlan->getVPaymentSystemId(),
            $vendorPaymentPlan->getVPaymentPlanId(),

            // WHERE bindings
            $vendorPaymentPlan->getId(),
        ];

        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }
    
    /**
     * Map an associative array DB record into a <kbd>VendorPaymentPlan</kbd> Entity.
     *
     * @param array $dbRecord
     * @return \Pley\Entity\Payment\VendorPaymentPlan
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        return new \Pley\Entity\Payment\VendorPaymentPlan(
            $dbRecord['id'],
            $dbRecord['payment_plan_id'],
            $dbRecord['shipping_zone_id'],
            (float)$dbRecord['base_price'],
            (float)$dbRecord['unit_price'],
            (float)$dbRecord['shipping_price'],
            (float)$dbRecord['total'],
            $dbRecord['v_payment_system_id'],
            $dbRecord['v_payment_plan_id']
        );
    }
}

