<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console\VendorIntegration\Hatchbuck;

/**
 * The <kbd>PastDueMemberHandlingTrait</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @method line(string $text) Write a string as standard output.
 * @method _getHatchbuckContact(string $email) Retrieve a Contact object from Hatchbuck given its email
 * @property \Pley\Db\AbstractDatabaseManager $_dbManager Database Access
 */
trait PastDueMemberHandlingTrait
{
    protected function _PastDueMember_processor()
    {
        $startTime = microtime(true);
        
        $this->line('Processing Past Due Members');
        $userList = $this->_PastDueMember_getDbList();
        
        $progressPrinter = new \Pley\Console\Util\ProgressPrinter();
        
        $statsTotalDb = count($userList);
        $statsUpdated   = 0;
        
        foreach ($userList as $userArray) {
            $progressPrinter->step();
            
            $hbContact = $this->_getHatchbuckContact($userArray['email']);
            
            // If the contact doesn't exist, something is wrong with this user and should be
            // inspected, for now we just skip it to avoid errors
            if (empty($hbContact)) {
                continue;
            }
            
            $this->_PastDueMember_updateUser($hbContact);
            $statsUpdated++;
        }
        $progressPrinter->finish();
        
        $endTime     = microtime(true);
        $elapsedTime = $endTime - $startTime;
        
        $this->line('[PastDue] Stats:');
        $this->line("[PastDue] Updated {$statsUpdated} of {$statsTotalDb} read from DB.");
        $this->line(sprintf('[PastDue] Elapsed Time: %.3f secs', $elapsedTime));
    }
    
    /**
     * Returns a list of array Maps containing information about the users who have a past due subscription.
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
    protected function _PastDueMember_getDbList()
    {
        $sql = 'SELECT `user`.`id`, `user`.`first_name`, `user`.`last_name`, `user`.`email` '
             . 'FROM `user` '
             . 'LEFT OUTER JOIN `profile_subscription` ON `user`.`id` = `profile_subscription`.`user_id` '
             . 'WHERE `profile_subscription`.`status` = ? '
             .   'AND ( '
             .      '(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`profile_subscription`.`created_at`)) < ? '
             .      'OR '
             .      '(UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`profile_subscription`.`updated_at`)) < ? '
             .   ') '
             . 'GROUP BY `user`.`id` '
             . 'ORDER BY `user`.`id`';
        
        $prepStmt = $this->_dbManager->prepare($sql);
        $bindings = [
            \Pley\Enum\SubscriptionStatusEnum::PAST_DUE,
            static::$CHECK_SPAN_TIME,
            static::$CHECK_SPAN_TIME,
        ];
        
        $prepStmt->execute($bindings);
        
        $userList = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $userList;
    }
    
    /**
     * Updates the supplied contact on the Hatchbuck CRM
     * @param \Hatchbuck\Entity\Contact $contact
     */
    protected function _PastDueMember_updateUser(\Hatchbuck\Entity\Contact $contact)
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
        $isTaggedAlready = false;
        $removeTagList = [static::$HATCHBUCK_TAG_MAP['cancelled']];
        foreach ($tagList as $tag) {
            if (in_array($tag->getId(), $removeTagList)) {
                $this->_hatchbuck->removeTag($contact, $tag);
                continue;
            }
            
            if ($tag->getId() == static::$HATCHBUCK_TAG_MAP['pastDue']) {
                $isTaggedAlready = true;
                continue;
            }
        }
        
        // If the user is tagged already, we don't need to add the tag, so just finish here
        if ($isTaggedAlready) {
            return;
        }
        
        // Otherwise we need to add the tag
        $newTag = new \Hatchbuck\Entity\Tag();
        $newTag->setId(static::$HATCHBUCK_TAG_MAP['pastDue']);
        
        $this->_hatchbuck->addTag($contact, [$newTag]);
    }
}
