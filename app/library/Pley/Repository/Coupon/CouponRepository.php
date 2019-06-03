<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Coupon;

use Pley\DataMap\Dao;
use Pley\DataMap\Repository;
use Pley\Entity\Coupon\Coupon;

/**
 * Repository class for coupon related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class CouponRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(Coupon::class);
    }

    /**
     * Find coupon by Id
     *
     * @param int $id
     * @return \Pley\Entity\Coupon\Coupon
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Find coupon by code
     *
     * @param string $code
     * @return \Pley\Entity\Coupon\Coupon | null
     */
    public function findByCode($code)
    {
        $result = $this->_dao->where('`code` = ?', [$code]);
        if (count($result)) {
            return $result[0];
        }
        return null;
    }

    /**
     * Find coupons by by search term
     *
     * @param int $term
     * @return \Pley\Entity\Coupon\Coupon[] | []
     */
    public function findByTerm($term)
    {
        return $this->_dao->where('`code` LIKE ? OR `id` = ?', ['%' . $term . '%', $term]);
    }

    /**
     * Returns coupons which has been redeemed by a given user.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Entity\Coupon\Coupon[]
     */
    public function getCouponsUsedByUser(\Pley\Entity\User\User $user)
    {
        $couponsTableName = Coupon::tableName();
        $redemptionsTableName = 'user_coupon_redemption';
        $sql = "SELECT `{$couponsTableName}`.* FROM `{$couponsTableName}` 
                      JOIN `{$redemptionsTableName}` 
                      ON `{$couponsTableName}`.`id` = `{$redemptionsTableName}`.`coupon_id` 
                      WHERE `{$redemptionsTableName}`.`user_id` = ?";

        return $this->_dao->query($sql, [$user->getId()]);
    }

    /**
     * Returns coupons which are both active and has special flag.
     * @return \Pley\Entity\Coupon\Coupon[]
     */
    public function findEnabledSpecial()
    {
        $couponsTableName = Coupon::tableName();
        $sql = "SELECT * FROM `{$couponsTableName}` 
                      WHERE `enabled` = ? 
        AND `special` = ? AND (`expires_at` >= ? OR `expires_at` IS NULL);";

        return $this->_dao->query($sql, [1, 1, \Pley\Util\DateTime::date(time())]);
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\Coupon\Coupon[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>Coupon</kbd> Entity.
     *
     * @param \Pley\Entity\Coupon\Coupon $coupon
     * @return \Pley\Entity\Coupon\Coupon
     */
    public function save(Coupon $coupon)
    {
        return $this->_dao->save($coupon);
    }
}

