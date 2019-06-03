<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace operations\v1\CustomerService\User\Profile;

use \Pley\Db\AbstractDatabaseManager as DatabaseManager;

/**
 * The <kbd>ProfileSubscriptionController</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 */
class ProfileSubscriptionController extends \operations\v1\BaseAuthController
{
    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;
    /** @var \Pley\User\UserSubscriptionManager */
    protected $_userSubsManager;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionDao */
    protected $_profileSubsDao;
    /** @var \Pley\Dao\Profile\ProfileSubscriptionPlanDao */
    protected $_profileSubsPlanDao;
    /** @var \Pley\Repository\User\UserRepository */
    protected $_userRepo;
    /** @var \Pley\Payment\PaymentManagerFactory */
    protected $_paymentManagerFactory;
    
    public function __construct(DatabaseManager $dbManager,
            \Pley\User\UserSubscriptionManager $userSubscriptionManager,
            \Pley\Dao\Profile\ProfileSubscriptionDao $profileSubscriptionDao,
            \Pley\Dao\Profile\ProfileSubscriptionPlanDao $profileSubscriptionPlanDao,
            \Pley\Repository\User\UserRepository $userRepo,
            \Pley\Payment\PaymentManagerFactory $paymentManagerFactory)
    {
        parent::__construct();
        
        $this->_dbManager             = $dbManager;
        $this->_userSubsManager       = $userSubscriptionManager;
        $this->_profileSubsDao        = $profileSubscriptionDao;
        $this->_profileSubsPlanDao    = $profileSubscriptionPlanDao;
        $this->_userRepo              = $userRepo;
        $this->_paymentManagerFactory = $paymentManagerFactory;
    }
    
    // DELETE /cs/profile/subscription/{intId}/full-cancel
    public function fullCancel($profileSubscriptionId)
    {
        \RequestHelper::checkDeleteRequest();
        
        $profileSubs = $this->_profileSubsDao->find($profileSubscriptionId);
        \ValidationHelper::entityExist($profileSubs, \Pley\Entity\Profile\ProfileSubscription::class);
        
        $this->_validateForCancel($profileSubs);
        
        $that = $this;
        $this->_dbManager->transaction(function () use ($that, $profileSubs) {
            $that->_fullCancelClosure($profileSubs);
        });
        
        return \Response::json(['success' => true]);
    }
    
    private function _validateForCancel(\Pley\Entity\Profile\ProfileSubscription $profileSubscription)
    {
        // Validation of cancelability
        if (\Pley\Enum\SubscriptionStatusEnum::CANCELLED == $profileSubscription->getStatus()) {
            $user = $this->_userRepo->find($profileSubscription->getUserId());
            throw new \Pley\Exception\User\Profile\NonCancelableSubscriptionException($user, $profileSubscription);
        }
    }
    
    /**
     * Closure method to force cancel a profile subscription and remove all shipments (paid and reserved)
     * as a transaction.
     * @param \Pley\Entity\Profile\ProfileSubscription $profileSubscription
     */
    private function _fullCancelClosure(\Pley\Entity\Profile\ProfileSubscription $profileSubscription)
    {
        $this->_dbManager->checkActiveTransaction(__METHOD__);
        
        $this->_userSubsManager->clearReserved($profileSubscription);
        $this->_userSubsManager->removeNotShipped($profileSubscription);
        
        $user = $this->_userRepo->find($profileSubscription->getUserId());
        
        // Special handling for Gift (as there is no Payment Plan)
        if ($profileSubscription->getStatus() == \Pley\Enum\SubscriptionStatusEnum::GIFT) {
            $profileSubscription->setStatus(\Pley\Enum\SubscriptionStatusEnum::CANCELLED);
            
        // Otherwise handle a Purchased (whether is active or past due)
        } else {
            $subscriptionPlan = $this->_profileSubsPlanDao->findLastByProfileSubscription($profileSubscription->getId());

            // Stops the AutoRenew on the subscription and cancels it
            $paymentManager = $this->_paymentManagerFactory->getManager($subscriptionPlan->getVPaymentSystemId());
            $paymentManager->subscriptionCancel(
                $user, $subscriptionPlan, \Pley\Enum\SubscriptionCancelSourceEnum::CUSTOMER_SERVICE, $this->_opsUserId
            );

            $profileSubscription->updateWithSubscriptionPlan($subscriptionPlan);
            $this->_profileSubsPlanDao->save($subscriptionPlan);
        }
        
        $this->_profileSubsDao->save($profileSubscription);
    }
}
