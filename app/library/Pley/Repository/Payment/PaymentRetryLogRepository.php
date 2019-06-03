<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Payment;

use Pley\DataMap\Dao;
use Pley\DataMap\Repository;

use Pley\Entity\Payment\PaymentRetryLog;

/**
 * Repository class for payment retries entity
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class PaymentRetryLogRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(PaymentRetryLog::class);
    }

    /**
     * Find log entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Payment\PaymentRetryLog
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\Payment\PaymentRetryLog[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Get all entries
     *
     * @param int $profileSubscriptionId
     * @return \Pley\Entity\Payment\PaymentRetryLog[]
     */
    public function getAllSubscriptionRetries($profileSubscriptionId)
    {
        return $this->_dao->where('profile_subscription_id = ?', [$profileSubscriptionId]);
    }

    /**
     * Saves the supplied <kbd>PaymentRetryLog</kbd> Entity.
     *
     * @param \Pley\Entity\Payment\PaymentRetryLog $paymentRetryLog
     * @return \Pley\Entity\Payment\PaymentRetryLog
     */
    public function save(PaymentRetryLog $paymentRetryLog)
    {
        return $this->_dao->save($paymentRetryLog);
    }
}