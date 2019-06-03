<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Dao\User;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\DbDaoInterface;

/**
 * The <kbd>UserCouponRedemptionDao</kbd> class provides implementation to interact with the user_coupon_redemption table
 * in the DB.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 * @package Pley.Dao.User
 * @subpackage Dao
 */
class UserCouponRedemptionDao extends AbstractDbDao implements DbDaoInterface
{

    /** @var string */
    protected $_tableName = 'user_coupon_redemption';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;

    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id',
            'user_id',
            'coupon_id',
            'transaction_id',
            'profile_subscription_id',
            'redeemed_at'
        ]);

        $this->_columnNames = implode(',', $escapedColumnNames);
    }

    /**
     * Return the <kbd>UserCouponRedemption</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\User\UserCouponRedemption
     */
    public function find($id)
    {

        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . "WHERE `id` = ?";
        $pstmt = $this->_prepare($prepSql);
        $bindings = [$id];

        $pstmt->execute($bindings);

        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();
        return $this->_toEntity($dbRecord);
    }

    /**
     * Returns a list of all <kbd>User</kbd> entities.
     * @return \Pley\Entity\User\UserCouponRedemption[]
     */
    public function all()
    {
        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}`";
        $pstmt = $this->_prepare($prepSql);

        $pstmt->execute();

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $pstmt->closeCursor();

        $collection = [];
        foreach ($resultSet as $dbRecord) {
            $entity = $this->_toEntity($dbRecord);
            $collection[] = $entity;
        }
        return $collection;
    }

    public function save(\Pley\Entity\User\UserCouponRedemption $couponRedemption)
    {
        // Depending on the Entity ID value, either update or insert.
        if (empty($couponRedemption->getId())) {
            $this->_insert($couponRedemption);
        } else {
            throw new \Pley\Exception\Dao\DaoUpdateNotAllowedException(__METHOD__);
        }
    }

    /**
     * Return count of coupon redemption for a given user
     *
     * @param \Pley\Entity\Coupon\Coupon $coupon
     * @param \Pley\Entity\User\User $user
     * @return int
     */
    public function getRedemptionsPerUser(\Pley\Entity\Coupon\Coupon $coupon, \Pley\Entity\User\User $user)
    {
        $prepSql = "SELECT COUNT(*) FROM `{$this->_tableName}` WHERE `user_id` = ? AND `coupon_id` = ?;";
        $pstmt = $this->_prepare($prepSql);
        $bindings = [
            $user->getId(),
            $coupon->getId()
        ];
        $pstmt->execute($bindings);
        $count = $pstmt->fetchColumn();
        $pstmt->closeCursor();
        return $count;
    }

    /**
     * Return count of coupon redemption
     *
     * @param \Pley\Entity\Coupon\Coupon $coupon
     * @return int
     */
    public function getRedemptionsCount(\Pley\Entity\Coupon\Coupon $coupon)
    {
        $prepSql = "SELECT COUNT(*) FROM `{$this->_tableName}` WHERE `coupon_id` = ?;";
        $pstmt = $this->_prepare($prepSql);
        $bindings = [
            $coupon->getId()
        ];
        $pstmt->execute($bindings);
        $count = $pstmt->fetchColumn();
        $pstmt->closeCursor();
        return $count;
    }

    /**
     * Map an associative array DB record into a <kbd>UserCouponRedemption</kbd> Entity.
     *
     * @param array $dbRecord
     * @return \Pley\Entity\User\UserCouponRedemption
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        return new \Pley\Entity\User\UserCouponRedemption(
            $dbRecord['id'],
            $dbRecord['user_id'],
            $dbRecord['coupon_id'],
            $dbRecord['transaction_id'],
            $dbRecord['profile_subscription_id'],
            $dbRecord['redeemed_at']
        );
    }

    /**
     * @param \Pley\Entity\User\UserCouponRedemption $couponRedemption
     * @return \Pley\Entity\User\UserCouponRedemption
     */
    private function _insert(\Pley\Entity\User\UserCouponRedemption $couponRedemption)
    {
        $prepSql = "INSERT INTO `{$this->_tableName}` ("
            . '`user_id`, '
            . '`coupon_id`, '
            . '`transaction_id`, '
            . '`profile_subscription_id`, '
            . '`redeemed_at` '
            . ') VALUES (?, ?, ?, ?, ?)';

        $pstmt = $this->_prepare($prepSql);
        $bindings = [
            $couponRedemption->getUserId(),
            $couponRedemption->getCouponId(),
            $couponRedemption->getTransactionId(),
            $couponRedemption->getProfileSubscriptionId(),
            $couponRedemption->getRedeemedAt()
        ];

        $pstmt->execute($bindings);

        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $couponRedemption->setId($id);
        return $couponRedemption;
    }
}
