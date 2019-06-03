<?php /** @copyright Pley (c) 2016, All Rights Reserved */

namespace Pley\Entity\Profile;

/**
 * The <kbd>Subscription</kbd> entity.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Entity.Subscription
 * @subpackage Entity
 */
class ProfileSubscription
{
    /** @var int */
    protected $_id;
    /** @var int */
    protected $_userId;
    /** @var int */
    protected $_userProfileId;
    /** @var int */
    protected $_subscriptionId;
    /** @var int */
    protected $_userAddressId;
    /** @var int */
    protected $_userPaymentMethodId;
    /** @var int */
    protected $_giftId;
    /** @var int */
    protected $_status;
    /** @var boolean */
    protected $_isAutoRenew;
    /** @var \Pley\Entity\Profile\QueueItem[] */
    protected $_itemSequenceQueue;
    /** @var int */
    protected $_createdAt;
    /** @var int */
    protected $_updatedAt;
    /** @var array */
    protected $_origData;

    public function __construct($id, $userId, $userProfileId, $subscriptionId, $userAddressId,
                                $userPaymentMethodId, $giftId, $status, $isAutoRenew, $itemSequenceQueue, $createdAt, $updatedAt)
    {
        $this->_id = $id;
        $this->_userId = $userId;
        $this->_userProfileId = $userProfileId;
        $this->_subscriptionId = $subscriptionId;
        $this->_userAddressId = $userAddressId;
        $this->_userPaymentMethodId = $userPaymentMethodId;
        $this->_giftId = $giftId;
        $this->_status = $status;
        $this->_isAutoRenew = $isAutoRenew;
        $this->_itemSequenceQueue = $itemSequenceQueue;
        $this->_createdAt = $createdAt;
        $this->_updatedAt = $updatedAt;

        $this->_setOriginalData();
    }

    public static function withNew($userId, $userProfileId, $subscriptionId, $userPaymentMethodId)
    {
        $userAddressId = null;
        $giftId = null;
        $status = \Pley\Enum\SubscriptionStatusEnum::ACTIVE;
        $isAutoRenew = true;
        $itemSequenceQueue = null;
        $createdAt = null;
        $updatedAt = null;

        return new static(
            null, $userId, $userProfileId, $subscriptionId, $userAddressId, $userPaymentMethodId,
            $giftId, $status, $isAutoRenew, $itemSequenceQueue, $createdAt, $updatedAt
        );
    }

    public static function withNewGift($userId, $userProfileId, $subscriptionId, $giftId)
    {
        $userAddressId = null;
        $userPaymentMethodId = null;
        $status = \Pley\Enum\SubscriptionStatusEnum::GIFT;
        $isAutoRenew = false;
        $itemSequenceQueue = null;
        $createdAt = null;
        $updatedAt = null;

        return new static(
            null, $userId, $userProfileId, $subscriptionId, $userAddressId, $userPaymentMethodId,
            $giftId, $status, $isAutoRenew, $itemSequenceQueue, $createdAt, $updatedAt
        );
    }

    /** @param int id */
    public function setId($id)
    {
        if (isset($this->_id)) {
            throw new ImmutableAttributeException(static::class, '_id');
        }
        $this->_id = $id;
    }

    /** @return int */
    public function getId()
    {
        return $this->_id;
    }

    /** @return int */
    public function getUserId()
    {
        return $this->_userId;
    }

    /** @return int */
    public function getUserProfileId()
    {
        return $this->_userProfileId;
    }

    /** @return int */
    public function getSubscriptionId()
    {
        return $this->_subscriptionId;
    }

    /** @return int */
    public function getUserAddressId()
    {
        return $this->_userAddressId;
    }

    /** @param $status int A value from <kbd>\Pley\Enum\SubscriptionStatusEnum</kbd> */
    public function setStatus($status)
    {
        \Pley\Enum\SubscriptionStatusEnum::validate($status);

        $this->_status = $status;
    }

    /**
     * Set the Address ID to use for this subscription, or reset it only if the status is Cancelled
     * @param int|null $userAddressId
     */
    public function setUserAddressId($userAddressId)
    {
        if (empty($userAddressId) && $this->_status != \Pley\Enum\SubscriptionStatusEnum::CANCELLED) {
            throw new \Exception('Cannot reset the user address on an active subscription.');
        }

        $this->_userAddressId = $userAddressId;
    }

    /** @return int */
    public function getUserPaymentMethodId()
    {
        return $this->_userPaymentMethodId;
    }

    public function setUserPaymentMethodId($userPaymentMethodId)
    {
        $this->_userPaymentMethodId = $userPaymentMethodId;
    }

    /** @return int */
    public function getGiftId()
    {
        return $this->_giftId;
    }

    /** @return int */
    public function getStatus()
    {
        return $this->_status;
    }

    /** @return boolean */
    public function isAutoRenew()
    {
        return $this->_isAutoRenew;
    }

    public function setIsAutoRenew($isAutoRenew)
    {
        $this->_isAutoRenew = $isAutoRenew;
    }

    /** @return \Pley\Entity\Profile\QueueItem[] */
    public function getItemSequenceQueue()
    {
        return $this->_itemSequenceQueue;
    }

    /** @param \Pley\Entity\Profile\QueueItem[] $itemSequenceQueue */
    public function setItemSequenceQueue(array $itemSequenceQueue)
    {
        $this->_itemSequenceQueue = $itemSequenceQueue;
    }

    /** @return int */
    public function getCreatedAt()
    {
        return $this->_createdAt;
    }

    /** @return int */
    public function getUpdatedAt()
    {
        return $this->_updatedAt;
    }

    /** @param \Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan */
    public function updateWithSubscriptionPlan(\Pley\Entity\Profile\ProfileSubscriptionPlan $subscriptionPlan)
    {
        $this->setStatus($subscriptionPlan->getStatus());
        $this->setIsAutoRenew($subscriptionPlan->isAutoRenew());
    }

    protected function _setOriginalData()
    {
        $this->_origData = $this->_serializeProperties();
    }

    public function getDataDiff()
    {
        $diff = [];
        $currentValues = $this->_serializeProperties();
        foreach ($currentValues as $key => $value) {
            if($key === '_origData') continue;
            if ($this->_origData[$key] !== $value) {
                $diff[$key] = [
                    'old' => $this->_origData[$key],
                    'new' => $value
                ];
            }
        }
        return $diff;
    }

    private function _serializeProperties(){
        $propsData = get_object_vars($this);
        unset($propsData['_itemSequenceQueue']);
        unset($propsData['_origData']);
        return $propsData;
    }
}
