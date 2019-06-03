<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Notification;

use Pley\DataMap\Dao;
use Pley\DataMap\Repository;
use Pley\Entity\Notification\NotificationSubscriber;

/**
 * Repository class for invites related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class NotificationSubscriberRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(NotificationSubscriber::class);
    }

    /**
     * Find invite by Id
     *
     * @param int $id
     * @return \Pley\Entity\Notification\NotificationSubscriber
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Get invites by a given user Id
     *
     * @param int $userId
     * @return \Pley\Entity\Notification\NotificationSubscriber[]
     */
    public function findByUserId($userId)
    {
        return $this->_dao->where('user_id = ?', [$userId]);
    }

    /**
     * Get invite by a given token Id
     *
     * @param int $tokenId
     * @return \Pley\Entity\Notification\NotificationSubscriber
     */
    public function findByTokenId($tokenId)
    {
        $result = $this->_dao->where('referral_token_id = ?', [$tokenId]);
        return count($result) ? $result[0] : null;
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\Notification\NotificationSubscriber[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>Invite</kbd> Entity.
     *
     * @param \Pley\Entity\Notification\NotificationSubscriber $notificationSubscriber
     * @return \Pley\Entity\Notification\NotificationSubscriber
     */
    public function save(\Pley\Entity\Notification\NotificationSubscriber $notificationSubscriber)
    {
        return $this->_dao->save($notificationSubscriber);
    }
}