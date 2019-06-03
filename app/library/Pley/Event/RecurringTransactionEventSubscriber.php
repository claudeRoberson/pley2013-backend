<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Event;

use \Pley\Config\ConfigInterface as Config;

/**
 * Event handler for recurring transactions events.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class RecurringTransactionEventSubscriber extends AbstractEventSubscriber
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionManager;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubscriptionDao;


    public function __construct(Config $config,
                                \Pley\Subscription\SubscriptionManager $subscriptionManager,
                                \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubscriptionDao
    )
    {
        $this->_config = $config;
        $this->_subscriptionManager = $subscriptionManager;
        $this->_profileSubscriptionDao = $profileSubscriptionDao;
    }

    /**
     * Handles a recurring transaction
     * @param \Pley\Entity\Profile\ProfileSubscriptionTransaction $profileSubscriptionTransaction
     */
    public function handleRecurringTransaction(\Pley\Entity\Profile\ProfileSubscriptionTransaction $profileSubscriptionTransaction)
    {
        $profileSubscription = $this->_profileSubscriptionDao->find($profileSubscriptionTransaction->getProfileSubscriptionId());
        $subscription = $this->_subscriptionManager->getSubscription($profileSubscription->getSubscriptionId());
        $this->_createShareASaleTransaction($profileSubscriptionTransaction);
    }

    protected function _createShareASaleTransaction(\Pley\Entity\Profile\ProfileSubscriptionTransaction $profileSubscriptionTransaction)
    {
        //TODO: implement ShareASale transaction trigger
    }

    /** {@inheritDoc} */
    protected function _getEventToMethodData()
    {
        return [
            [\Pley\Enum\EventEnum::RECURRING_TRANSACTION_CREATED, 'handleRecurringTransaction']
        ];
    }
}