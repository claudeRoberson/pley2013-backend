<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console\VendorIntegration\Hatchbuck;

/**
 * The <kbd>SubscriptionTagHandlingTrait</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @method line(string $text) Write a string as standard output.
 * @method _getHatchbuckContact(string $email) Retrieve a Contact object from Hatchbuck given its email
 * @property \Pley\Db\AbstractDatabaseManager $_dbManager Database Access
 */
trait SubscriptionTagHandlingTrait
{
    protected function _SubscriptionTag_processor()
    {
        $startTime = microtime(true);
        
        $this->line('Processing Subscription Tags');
        $userList = $this->_SubscriptionTag_getDbList();
        
        $progressPrinter = new \Pley\Console\Util\ProgressPrinter();
        
        $statsTotalDb = count($userList);
        $statsAdded   = 0;
        
        foreach ($userList as $userArray) {
            $progressPrinter->step();
            
            $hbContact = $this->_getHatchbuckContact($userArray['email']);
            
            // This should not really happen unless something was run out of order and a future
            // run would pick up the created contact
            // (i.e. A user is created between the Add User/Member portion of the code but before
            // this Subscription handling)
            if (!isset($hbContact)) {
                continue;
            }
            
            $this->_SubscriptionTag_addTag($hbContact, $userArray['subscription_id']);
            $statsAdded++;
        }
        $progressPrinter->finish();
        
        $endTime     = microtime(true);
        $elapsedTime = $endTime - $startTime;
        
        $this->line('[Sub Tags] Stats:');
        $this->line("[Sub Tags] Added {$statsAdded} of {$statsTotalDb} read from DB.");
        $this->line(sprintf('[Sub Tags] Elapsed Time: %.3f secs', $elapsedTime));
    }
    
    /**
     * Returns a list of array Maps containing information about the users who added a new subscription.
     * @return array The list has the following structure<br/>
     * <pre>array(
     * &nbsp;  array(
     * &nbsp;     'id'              => int,
     * &nbsp;     'first_name'      => string,
     * &nbsp;     'last_name'       => string,
     * &nbsp;     'email'           => string,
     * &nbsp;     'subscription_id' => int,
     * &nbsp;  ),
     * &nbsp;  ...
     * )</pre>
     */
    protected function _SubscriptionTag_getDbList()
    {
        $sql = 'SELECT `user`.`id`, `user`.`first_name`, `user`.`last_name`, `user`.`email`, `profile_subscription`.`subscription_id` '
             . 'FROM `user` '
             . 'JOIN `profile_subscription` ON `user`.`id` = `profile_subscription`.`user_id` '
             . 'WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`profile_subscription`.`created_at`)) < ?';
             ;
        
        $prepStmt = $this->_dbManager->prepare($sql);
        $bindings = [
            static::$CHECK_SPAN_TIME
        ];
        
        $prepStmt->execute($bindings);
        
        $userList = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $userList;
    }
    
    /**
     * Updates the supplied contact on the Hatchbuck CRM
     * @param \Hatchbuck\Entity\Contact $contact
     * @param int                       $subscriptionId
     */
    protected function _SubscriptionTag_addTag(\Hatchbuck\Entity\Contact $contact, $subscriptionId)
    {
        $tagList = $contact->getTagList();
        $isTaggedAlready = false;
        foreach ($tagList as $tag) {
            if ($tag->getId() == static::$HATCHBUCK_SUBSCRIPTION_TAG_MAP[$subscriptionId]) {
                $isTaggedAlready = true;
                break;
            }
        }
        
        // If the user is tagged already, we don't need to add the tag, so just finish here
        if ($isTaggedAlready) {
            return;
        }
        
        // Otherwise we need to add the tag
        $tagSubscription = new \Hatchbuck\Entity\Tag();
        $tagSubscription->setId(static::$HATCHBUCK_SUBSCRIPTION_TAG_MAP[$subscriptionId]);
        
        $this->_hatchbuck->addTag($contact, [$tagSubscription]);
    }
}
