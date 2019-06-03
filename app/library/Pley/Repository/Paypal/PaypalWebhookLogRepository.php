<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Paypal;

use Pley\DataMap\Dao;
use Pley\DataMap\Repository;
use Pley\Entity\Paypal\PaypalWebhookLog;

/**
 * Repository class for Paypal webhook log entries related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class PaypalWebhookLogRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(PaypalWebhookLog::class);
    }

    /**
     * Find log entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Paypal\PaypalWebhookLog
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Find log entry by event id
     *
     * @param string $eventId
     * @return \Pley\Entity\Paypal\PaypalWebhookLog
     */
    public function findByEventId($eventId)
    {
        $result = $this->_dao->where('event_id = ?', [$eventId]);
        return count($result) ? $result[0] : null;
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\Paypal\PaypalWebhookLog[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>PaypalWebhookLog</kbd> Entity.
     *
     * @param \Pley\Entity\Paypal\PaypalWebhookLog $webhookLog
     * @return \Pley\Entity\Paypal\PaypalWebhookLog
     */
    public function save(PaypalWebhookLog $webhookLog)
    {
        return $this->_dao->save($webhookLog);
    }
}