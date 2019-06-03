<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console\VendorIntegration\Hatchbuck;

/**
 * The <kbd>MemberHandlingTrait</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @method line(string $text) Write a string as standard output.
 * @method _getHatchbuckContact(string $email) Retrieve a Contact object from Hatchbuck given its email
 * @property \Pley\Db\AbstractDatabaseManager $_dbManager Database Access
 */
trait MemberHandlingTrait
{
    protected function _Member_processor()
    {
        $startTime = microtime(true);
        
        $this->line('Processing Converted Members');
        $userList = $this->_Member_getDbList();
        
        $progressPrinter = new \Pley\Console\Util\ProgressPrinter();
        
        $statsTotalDb = count($userList);
        $statsAdded   = 0;
        
        foreach ($userList as $userArray) {
            $progressPrinter->step();
            
            $hbContact = $this->_getHatchbuckContact($userArray['email']);
            
            // If the contact exists, it could mean that it is a new subscription added or an existing
            // one got updated
            if (isset($hbContact)) {
                $this->_Member_updateUser($hbContact);
                
            // Otherwise, the user needs to be added as member
            } else {
                $this->_Member_addUser($userArray);
            }
            $statsAdded++;
        }
        $progressPrinter->finish();
        
        $endTime     = microtime(true);
        $elapsedTime = $endTime - $startTime;
        
        $this->line('[Members] Stats:');
        $this->line("[Members] Added/Updated {$statsAdded} of {$statsTotalDb} read from DB.");
        $this->line(sprintf('[Members] Elapsed Time: %.3f secs', $elapsedTime));
    }
    
    /**
     * Returns a list of array Maps containing information about the users who have an active subscription.
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
    protected function _Member_getDbList()
    {
        $sql = 'SELECT `user`.`id`, `user`.`first_name`, `user`.`last_name`, `user`.`email` '
             . 'FROM `user` '
             . 'JOIN `profile_subscription` ON `user`.`id` = `profile_subscription`.`user_id` '
             . 'WHERE `profile_subscription`.`status` IN (?, ?) '
             .   'AND ( '
             .      '(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`profile_subscription`.`created_at`)) < ? '
             .      'OR '
             .      '(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`profile_subscription`.`updated_at`)) < ? '
             .   ') '
             . 'GROUP BY `user`.`id` '
             . 'ORDER BY `user`.`id`';
        
        $prepStmt = $this->_dbManager->prepare($sql);
        $bindings = [
            \Pley\Enum\SubscriptionStatusEnum::ACTIVE, \Pley\Enum\SubscriptionStatusEnum::GIFT,
            static::$CHECK_SPAN_TIME,
            static::$CHECK_SPAN_TIME,
        ];
        
        $prepStmt->execute($bindings);
        
        $userList = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $userList;
    }
    
    /**
     * Adds a user to the Hatchbuck CRM
     * @param array $userArray See <kbd>_Member_getDbList()</kbd> for structure information.
     */
    protected function _Member_addUser($userArray)
    {
        $email = new \Hatchbuck\Entity\Email();
        $email->setAddress($userArray['email']);
        
        $status = new \Hatchbuck\Entity\Status();
        $status->setId(static::$HATCHBUCK_CONTACT_STATUS_MAP['member']);
        
        $contact = new \Hatchbuck\Entity\Contact();
        $contact->setFirstName($userArray['first_name']);
        $contact->setLastName($userArray['last_name']);
        $contact->setStatus($status);
        $contact->setEmailList([$email]);
        
        $newContact = $this->_hatchbuck->addContact($contact);

        // Now adding tags to the just added contact
        $tagMember = new \Hatchbuck\Entity\Tag();
        $tagMember->setId(static::$HATCHBUCK_TAG_MAP['member']);
        
        $tagPleybox = new \Hatchbuck\Entity\Tag();
        $tagPleybox->setId(static::$HATCHBUCK_TAG_MAP['pleybox']);
        
        $this->_hatchbuck->addTag($newContact, [$tagMember, $tagPleybox]);
    }
    
    /**
     * Updates the supplied contact on the Hatchbuck CRM
     * @param \Hatchbuck\Entity\Contact $contact
     */
    protected function _Member_updateUser(\Hatchbuck\Entity\Contact $contact)
    {
        $isUpdated = false;
        if ($contact->getStatus()->getId() != static::$HATCHBUCK_CONTACT_STATUS_MAP['member']) {
            $status = new \Hatchbuck\Entity\Status();
            $status->setId(static::$HATCHBUCK_CONTACT_STATUS_MAP['member']);

            $contact->setStatus($status);
            $isUpdated = true;
        }
        
        if ($isUpdated) {
            $this->_hatchbuck->updateContact($contact);
        }

        // Now checking the tags
        $tagList = $contact->getTagList();
        
        // Find if the user has the `cancelled` tag, if so, remove it, and also check if the user
        // is already a member (meaning this was triggered because a new subscription got added/updated)
        $isTaggedAlready        = false;
        $isPleyboxTaggedAlready = false;
        $removeTagList = [
            static::$HATCHBUCK_TAG_MAP['user'],
            static::$HATCHBUCK_TAG_MAP['cancelled'],
            static::$HATCHBUCK_TAG_MAP['invite'],
            
            // Also removing any New Lead tags
            static::$HATCHBUCK_TAG_MAP['tbNewLeadPrincess'],
            static::$HATCHBUCK_TAG_MAP['tbNewLeadNatGeo'],
        ];
        foreach ($tagList as $tag) {
            if (in_array($tag->getId(), $removeTagList)) {
                $this->_hatchbuck->removeTag($contact, $tag);
                continue;
            }
            
            if ($tag->getId() == static::$HATCHBUCK_TAG_MAP['member']) {
                $isTaggedAlready = true;
                continue;
            }
            
            if ($tag->getId() == static::$HATCHBUCK_TAG_MAP['pleybox']) {
                $isPleyboxTaggedAlready = true;
                break;
            }
        }
        
        if (!$isPleyboxTaggedAlready) {
            $tagMember = new \Hatchbuck\Entity\Tag();
            $tagMember->setId(static::$HATCHBUCK_TAG_MAP['pleybox']);

            $this->_hatchbuck->addTag($contact, [$tagMember]);
        }
        
        // If the user is tagged already, we don't need to add the tag, so just finish here
        if ($isTaggedAlready) {
            return;
        }
        
        // Otherwise we need to add the tag
        $tagMember = new \Hatchbuck\Entity\Tag();
        $tagMember->setId(static::$HATCHBUCK_TAG_MAP['member']);
        
        $this->_hatchbuck->addTag($contact, [$tagMember]);
    }
}
