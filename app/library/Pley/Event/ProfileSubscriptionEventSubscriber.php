<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Event;

use \Pley\Config\ConfigInterface as Config;
use Pley\Entity\Profile\ProfileSubscriptionStatusLog;

/**
 * Event handler for Profile Subscription events.
 *
 * @author Vsevolod Yatsuk (vsevolod.yatsuk@agileengine.com)
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class ProfileSubscriptionEventSubscriber extends AbstractEventSubscriber
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Subscription\SubscriptionManager */
    protected $_subscriptionManager;

    protected $_profileSubscriptionStatusLogRepo;


    // Because the framework's MailServiceProvider is `deferred` we cannot add it as part of the
    // dependencies of this subscriber which is loaded as part of our Non-Deferred EventServiceProvider
    public function __construct(Config $config,
                                \Pley\Subscription\SubscriptionManager $subscriptionManager,
                                \Pley\Repository\Profile\ProfileSubscriptionStatusLogRepository $profileSubscriptionStatusLogRepository
    )
    {
        $this->_config = $config;
        $this->_subscriptionManager = $subscriptionManager;
        $this->_profileSubscriptionStatusLogRepo = $profileSubscriptionStatusLogRepository;
    }

    /**
     * Handes a diff values on the profile subscription update
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubscription
     */
    public function handleProfileSubscriptionUpdate(\Pley\Entity\Profile\ProfileSubscription $profileSubscription)
    {
        foreach ($profileSubscription->getDataDiff() as $updatedProperty => $values) {
            if($updatedProperty === '_status'){
                $log = new ProfileSubscriptionStatusLog();
                $log->setProfileSubscriptionId($profileSubscription->getId())
                    ->setOldStatus(\Pley\Enum\SubscriptionStatusEnum::asString($values['old']))
                    ->setNewStatus(\Pley\Enum\SubscriptionStatusEnum::asString($values['new']));
                $this->_profileSubscriptionStatusLogRepo->save($log);
            }
        }
    }


    /** {@inheritDoc} */
    protected function _getEventToMethodData()
    {
        return [
            [\Pley\Enum\EventEnum::PROFILE_SUBSCRIPTION_UPDATED, 'handleProfileSubscriptionUpdate']
        ];
    }
}