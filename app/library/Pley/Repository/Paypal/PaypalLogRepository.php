<?php
/** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Repository\Paypal;

use Pley\DataMap\Dao;
use Pley\DataMap\Repository;
use Pley\Entity\Paypal\PaypalLog;

/**
 * Repository class for PayPal log entries related CRUD operations
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @version 1.0
 */
class PaypalLogRepository extends Repository
{
    public function __construct(Dao $dao)
    {
        parent::__construct($dao);
        $this->_dao->setEntityClass(PaypalLog::class);
    }

    /**
     * Find log entry by Id
     *
     * @param int $id
     * @return \Pley\Entity\Paypal\PaypalLog
     */
    public function find($id)
    {
        return $this->_dao->find($id);
    }

    /**
     * Get all entries
     *
     * @return \Pley\Entity\Paypal\PaypalLog[]
     */
    public function all()
    {
        return $this->_dao->all();
    }

    /**
     * Saves the supplied <kbd>PaypalLog</kbd> Entity.
     *
     * @param \Pley\Entity\Paypal\PaypalLog $paypalLog
     * @return \Pley\Entity\Paypal\PaypalLog
     */
    public function save(PaypalLog $paypalLog)
    {
        return $this->_dao->save($paypalLog);
    }
}