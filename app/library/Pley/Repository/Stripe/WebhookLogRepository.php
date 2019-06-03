<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Stripe;

use Pley\DataMap\Dao;
use Pley\DataMap\Repository;
use Pley\Entity\Stripe\WebhookLog;

/**
 * Repository class for Stripe webhook log entries related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class WebhookLogRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(WebhookLog::class);
    }

    /**
     * Find log entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Stripe\WebhookLog
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Find log entry by event id
     *
     * @param string $eventId
     * @return \Pley\Entity\Stripe\WebhookLog
     */
    public function findByEventId($eventId)
    {
        $result = $this->_dao->where('event_id = ?', [$eventId]);
        return count($result) ? $result[0] : null;
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\Stripe\WebhookLog[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>WebhookLog</kbd> Entity.
     *
     * @param \Pley\Entity\Stripe\WebhookLog $webhookLog
     * @return \Pley\Entity\Stripe\WebhookLog
     */
    public function save(WebhookLog $webhookLog)
    {
        return $this->_dao->save($webhookLog);
    }
}