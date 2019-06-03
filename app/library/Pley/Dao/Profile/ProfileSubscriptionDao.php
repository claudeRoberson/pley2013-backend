<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace Pley\Dao\Profile;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;
use Pley\Enum\SubscriptionStatusEnum;

/**
 * The <kbd>SubscriptionDao</kbd> class provides implementation to interact with the Subscription
 * table in the DB and Cache.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.Subscription
 * @subpackage Dao
 */
class ProfileSubscriptionDao extends AbstractDbDao implements DbDaoInterface
{
    /** @var string */
    protected $_tableName = 'profile_subscription';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;

    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'user_id', 'user_profile_id', 'subscription_id', 'user_address_id',
            'user_payment_method_id', 'gift_id', 'status', 'is_auto_renew',
            'item_sequence_queue_json', 'created_at', 'updated_at'
        ]);

        $this->_columnNames = implode(',', $escapedColumnNames);
    }

    /**
     * Return the <kbd>ProfileSubscription</kbd> entity for the supplied id or null if not found.
     * @param int $id
     * @return \Pley\Entity\Profile\ProfileSubscription
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
        $entity = $this->_toEntity($dbRecord);

        return $entity;
    }

    /**
     * Get a list of Profile Subscriptions for the given User ID
     * @param int $userId
     * @return \Pley\Entity\Profile\ProfileSubscription[]
     */
    public function findByUser($userId)
    {
        $profileSubsList = $this->_findByField('user_id', $userId);
        return $profileSubsList;
    }

    /**
     * Get a list of UNPAID Profile Subscriptions
     * @return \Pley\Entity\Profile\ProfileSubscription[]
     */
    public function findUnpaid()
    {
        $profileSubsList = $this->_findByField('status', SubscriptionStatusEnum::UNPAID);
        return $profileSubsList;
    }

    /**
     * Get a list of Profile Subscriptions for the given User Profile ID
     * @param int $profileId
     * @return \Pley\Entity\Profile\ProfileSubscription[]
     */
    public function findByProfile($profileId)
    {
        $profileSubsList = $this->_findByField('user_profile_id', $profileId);
        return $profileSubsList;
    }

    /**
     * Get a list of Profile Subscriptions for the given subscription ID
     * @param int $subscriptionId
     * @return \Pley\Entity\Profile\ProfileSubscription[]
     */
    public function findBySubscription($subscriptionId)
    {
        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . "WHERE `subscription_id` = ? AND `status` IN (?, ?)";
        $pstmt = $this->_prepare($prepSql);
        $bindings = [
            $subscriptionId,
            SubscriptionStatusEnum::ACTIVE,
            SubscriptionStatusEnum::GIFT
        ];
        $pstmt->execute($bindings);
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount = $pstmt->rowCount();
        $pstmt->closeCursor();

        // In place replacement of array representation to object representation
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }
        return $resultSet;
    }

    /**
     * Get a list of Profile Subscriptions given the search field
     * @param string $fieldName
     * @param mixed $fieldValue
     * @return @return \Pley\Entity\Profile\ProfileSubscription[]
     */
    private function _findByField($fieldName, $fieldValue)
    {
        $prepSql = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
            . "WHERE `{$fieldName}` = ?";
        $pstmt = $this->_prepare($prepSql);
        $bindings = [$fieldValue];

        $pstmt->execute($bindings);
        $resultSet = $pstmt->fetchAll(\PDO::FETCH_ASSOC);
        $rowCount = $pstmt->rowCount();
        $pstmt->closeCursor();

        // In place replacement of array representation to object representation
        for ($i = 0; $i < $rowCount; $i++) {
            $resultSet[$i] = $this->_toEntity($resultSet[$i]);
        }

        return $resultSet;
    }

    public function save(\Pley\Entity\Profile\ProfileSubscription $profileSubscription)
    {
        // Depending on the Entity ID value, either update or insert.
        if (empty($profileSubscription->getId())) {
            $this->_insert($profileSubscription);
        } else {
            $this->_update($profileSubscription);
            \Event::fire(\Pley\Enum\EventEnum::PROFILE_SUBSCRIPTION_UPDATED, [
                'profileSubscription' => $profileSubscription,
            ]);
        }
    }

    private function _insert(\Pley\Entity\Profile\ProfileSubscription $profileSubscription)
    {
        $prepSql = "INSERT INTO `{$this->_tableName}` ("
            . '`user_id`, '
            . '`user_profile_id`, '
            . '`subscription_id`, '
            . '`user_address_id`, '
            . '`user_payment_method_id`, '
            . '`gift_id`, '
            . '`status`, '
            . '`is_auto_renew`, '
            . '`created_at` '
            . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())';
        $pstmt = $this->_prepare($prepSql);

        // converting the Object to a JSON String
        $bindings = [
            $profileSubscription->getUserId(),
            $profileSubscription->getUserProfileId(),
            $profileSubscription->getSubscriptionId(),
            $profileSubscription->getUserAddressId(),
            $profileSubscription->getUserPaymentMethodId(),
            $profileSubscription->getGiftId(),
            $profileSubscription->getStatus(),
            $profileSubscription->isAutoRenew() ? 1 : 0,
        ];

        $pstmt->execute($bindings);

        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $profileSubscription->setId($id);
    }

    private function _update(\Pley\Entity\Profile\ProfileSubscription $profileSubscription)
    {
        $prepSql = "UPDATE `{$this->_tableName}` "
            . 'SET `user_address_id` = ?, '
            . '`user_payment_method_id` = ?, '
            . '`status` = ?, '
            . '`is_auto_renew` = ?, '
            . '`item_sequence_queue_json` = ? '
            . 'WHERE `id` = ?';
        $pstmt = $this->_prepare($prepSql);

        $itemSequenceQueue = $profileSubscription->getItemSequenceQueue();
        $itemSequenceArray = [];
        foreach ($itemSequenceQueue as $queueItem) {
            $itemSequenceArray[] = $queueItem->toArray();
        }

        $bindings = [
            $profileSubscription->getUserAddressId(),
            $profileSubscription->getUserPaymentMethodId(),
            $profileSubscription->getStatus(),
            $profileSubscription->isAutoRenew() ? 1 : 0,
            json_encode($itemSequenceArray),

            // WHERE bindings
            $profileSubscription->getId(),
        ];

        $pstmt->execute($bindings);
        $pstmt->closeCursor();
    }

    /**
     * Map an associative array DB record into a <kbd>Subscription</kbd> Entity.
     *
     * @param array $dbRecord
     * @return \Pley\Entity\Subscription\Subscription
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }

        // Parsing the sequence JSON into an array of objects, if there is a queue
        $itemSequenceJson = $dbRecord['item_sequence_queue_json'];
        $itemSequenceQueue = null;
        if (!empty($itemSequenceJson)) {
            $itemSequenceQueue = [];
            $itemSequenceArray = json_decode($itemSequenceJson, true);

            for ($i = 0; $i < count($itemSequenceArray); $i++) {
                $queueItem = $itemSequenceArray[$i];
                $itemSequenceQueue[] = \Pley\Entity\Profile\QueueItem::fromArray($queueItem);
            }
        }

        return new \Pley\Entity\Profile\ProfileSubscription(
            $dbRecord['id'],
            $dbRecord['user_id'],
            $dbRecord['user_profile_id'],
            $dbRecord['subscription_id'],
            $dbRecord['user_address_id'],
            $dbRecord['user_payment_method_id'],
            $dbRecord['gift_id'],
            $dbRecord['status'],
            $dbRecord['is_auto_renew'] == 1,
            $itemSequenceQueue,
            \Pley\Util\DateTime::strToTime($dbRecord['created_at']),
            \Pley\Util\DateTime::strToTime($dbRecord['updated_at'])
        );
    }
}
