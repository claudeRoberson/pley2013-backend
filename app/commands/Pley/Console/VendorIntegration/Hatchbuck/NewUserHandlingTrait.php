<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console\VendorIntegration\Hatchbuck;

/**
 * The <kbd>NewUserHandlingTrait</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @method line(string $text) Write a string as standard output.
 * @method _getHatchbuckContact(string $email) Retrieve a Contact object from Hatchbuck given its email
 * @property \Pley\Db\AbstractDatabaseManager $_dbManager Database Access
 */
trait NewUserHandlingTrait
{
    protected function _NewUser_processor()
    {
        $startTime = microtime(true);
        
        $config = [
            'newLeadBySubscriptionMap' => [
                1 => static::$HATCHBUCK_TAG_MAP['tbNewLeadPrincess'],
                2 => static::$HATCHBUCK_TAG_MAP['tbNewLeadNatGeo'],
                3 => static::$HATCHBUCK_TAG_MAP['tbNewLeadHotWheels'],
            ],
        ];
        
        $this->line('Processing New Users');
        $userList = $this->_NewUser_getDbList();
        
        $progressPrinter = new \Pley\Console\Util\ProgressPrinter();
        
        $statsTotalDb = count($userList);
        $statsAdded   = 0;
        
        foreach ($userList as $userArray) {
            $progressPrinter->step();
            
            $hbContact = $this->_getHatchbuckContact($userArray['email']);
            
            // If the contact already exists (don't try adding a new one, it either was read from
            // the DB due to how the execution times are set or the user was added on HB through
            // other means, so just add the Pleybox tag if not set already)
            if (isset($hbContact)) {
                $this->_NewUser_addTag($hbContact, $userArray, $config);
                
            } else {
                $this->_NewUser_addUser($userArray, $config);
            }
            
            $statsAdded++;
        }
        $progressPrinter->finish();
        
        $endTime     = microtime(true);
        $elapsedTime = $endTime - $startTime;
        
        $this->line('[New Users] Stats:');
        $this->line("[New Users] Added/Updated {$statsAdded} of {$statsTotalDb} read from DB.");
        $this->line(sprintf('[New Users] Elapsed Time: %.3f secs', $elapsedTime));
    }
    
    /**
     * Returns a list of array Maps containing information about the users who hasn't finished registration.
     * @return array The list has the following structure<br/>
     * <pre>array(
     * &nbsp;  array(
     * &nbsp;     'id'         => int,
     * &nbsp;     'first_name' => string,
     * &nbsp;     'last_name'  => string,
     * &nbsp;     'email'      => string,
     * &nbsp;  ),
     * &nbsp;  ...
     * )</pre>
     */
    protected function _NewUser_getDbList()
    {
        $sql = 'SELECT `user`.`id`, `user`.`first_name`, `user`.`last_name`, `user`.`email`, `user_incomplete_registration`.`subscription_id` '
             . 'FROM `user` '
             . 'JOIN `user_incomplete_registration` ON `user`.`id` = `user_incomplete_registration`.`user_id` '
             . 'WHERE UNIX_TIMESTAMP(`user`.`created_at`) < (UNIX_TIMESTAMP(NOW()) - ?) '
             .   'AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`user`.`created_at`)) < ? ';
             ;
        
        $prepStmt = $this->_dbManager->prepare($sql);
        $bindings = [
            // This gives some time for user to finish registration if they are on it when this ran
            static::$NEW_USERS_THRESHOLD_TIME, 
            static::$CHECK_SPAN_TIME
        ];
        
        $prepStmt->execute($bindings);
        
        $userList = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $userList;
    }
    
    /**
     * Adds a user to the Hatchbuck CRM
     * @param array $userArray See <kbd>_NewUser_getDbList()</kbd> for structure information.
     * @param array $config    An additional map that contains extra information to be used.
     */
    protected function _NewUser_addUser($userArray, $config)
    {
        $newLeadBySubscriptionMap = $config['newLeadBySubscriptionMap'];
        
        $email = new \Hatchbuck\Entity\Email();
        $email->setAddress($userArray['email']);
        
        $status = new \Hatchbuck\Entity\Status();
        $status->setId(static::$HATCHBUCK_CONTACT_STATUS_MAP['user']);
        
        $contact = new \Hatchbuck\Entity\Contact();
        $contact->setFirstName($userArray['first_name']);
        $contact->setLastName($userArray['last_name']);
        $contact->setStatus($status);
        $contact->setEmailList([$email]);
        
        $newContact = $this->_hatchbuck->addContact($contact);

        // Now adding tags to the just added contact
        $tagUser = new \Hatchbuck\Entity\Tag();
        $tagUser->setId(static::$HATCHBUCK_TAG_MAP['user']);
        
        $tagPleybox = new \Hatchbuck\Entity\Tag();
        $tagPleybox->setId(static::$HATCHBUCK_TAG_MAP['pleybox']);
        
        $tagSubscriptionLead = new \Hatchbuck\Entity\Tag();
        $tagSubscriptionLead->setId($newLeadBySubscriptionMap[$userArray['subscription_id']]);
        
        $this->_hatchbuck->addTag($newContact, [$tagUser, $tagPleybox, $tagSubscriptionLead]);
    }
    
    /**
     * Updates the supplied contact on the Hatchbuck CRM
     * @param \Hatchbuck\Entity\Contact $contact
     * @param array                     $userArray See <kbd>_NewUser_getDbList()</kbd> for structure information.
     * @param array                     $config    An additional map that contains extra information to be used.
     */
    protected function _NewUser_addTag(\Hatchbuck\Entity\Contact $contact, $userArray, $config)
    {
        $newLeadBySubscriptionMap = $config['newLeadBySubscriptionMap'];
        
        // Now checking the tags
        $tagList = $contact->getTagList();
        
        // Find if the user has the `cancelled` tag, if so, remove it, and also check if the user
        // is already a member (meaning this was triggered because a new subscription got added/updated)
        $isTaggedAlready = false;
        foreach ($tagList as $tag) {
            if ($tag->getId() == static::$HATCHBUCK_TAG_MAP['pleybox']) {
                $isTaggedAlready = true;
                break;
            }
        }
        
        // If the user is tagged already, we don't need to add the tag, so just finish here
        if ($isTaggedAlready) {
            return;
        }
        
        // Otherwise we need to add the tag
        $tagPleybox = new \Hatchbuck\Entity\Tag();
        $tagPleybox->setId(static::$HATCHBUCK_TAG_MAP['pleybox']);
        
        $tagSubscriptionLead = new \Hatchbuck\Entity\Tag();
        $tagSubscriptionLead->setId($newLeadBySubscriptionMap[$userArray['subscription_id']]);
        
        $this->_hatchbuck->addTag($contact, [$tagPleybox, $tagSubscriptionLead]);
    }
    
}
