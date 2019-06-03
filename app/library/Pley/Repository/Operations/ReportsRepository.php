<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Operations;

use Pley\Db\AbstractDatabaseManager;

/**
 * Repository class for reports related operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class ReportsRepository
{
    /**
     * Instance of the Database Manager
     * @var \Pley\Db\DatabaseManagerInterface
     */
    protected $_dbManager;

    public function __construct(AbstractDatabaseManager $databaseManager)
    {
        $this->_dbManager = $databaseManager;
    }

    /**
     * Returns coupons which has been redeemed by a given user.
     * @param \Pley\Entity\User\User $user
     * @return array
     */
    public function getSizesScheduledForPeriod($subscriptionId, $scheduleIndex, $itemSequenceIndex)
    {
        $prepSql = "SELECT `tss`.`id`, COUNT(*) AS `count`
FROM `profile_subscription_shipment` AS `pss`
  JOIN `user_profile` AS `up` ON `pss`.`user_profile_id` = `up`.`id`
  JOIN `type_shirt_size` AS `tss` ON tss.id = up.type_shirt_size_id
WHERE `pss`.`subscription_id` = ?
    AND `pss`.`schedule_index` = ?
    AND pss.status IN (1,2)
    AND pss.item_sequence_index = ?
GROUP BY up.`type_shirt_size_id`;";

        $pstmt = $this->_dbManager->prepare($prepSql);
        $bindings = [
            $subscriptionId,
            $scheduleIndex,
            $itemSequenceIndex];

        $pstmt->execute($bindings);

        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);

        return $resultSet;
    }
}

