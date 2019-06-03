<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console\VendorIntegration\Hatchbuck;

/**
 * The <kbd>CancelledMemberHandlingTrait</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @method line(string $text) Write a string as standard output.
 * @method _getHatchbuckContact(string $email) Retrieve a Contact object from Hatchbuck given its email
 * @property \Pley\Db\AbstractDatabaseManager $_dbManager Database Access
 */
trait CancelledMemberHandlingTrait
{
    protected function _CancelledMember_processor()
    {
        $startTime = microtime(true);
        
        $this->line('Processing Cancelled Members');
        $userList = $this->_CancelledMember_getDbList();
        
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
            
            $this->_CancelledMember_updateUser($hbContact);
            $statsUpdated++;
        }
        $progressPrinter->finish();
        
        $endTime     = microtime(true);
        $elapsedTime = $endTime - $startTime;
        
        $this->line('[Cancelled] Stats:');
        $this->line("[Cancelled] Updated {$statsUpdated} of {$statsTotalDb} read from DB.");
        $this->line(sprintf('[Cancelled] Elapsed Time: %.3f secs', $elapsedTime));
    }
    
    /**
     * Returns a list of array Maps containing information about the users who have no active subscription.
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
    protected function _CancelledMember_getDbList()
    {
        $sql = 'SELECT `user`.`id`, `user`.`first_name`, `user`.`last_name`, `user`.`email` '
             . 'FROM `user` '
             . 'JOIN `profile_subscription` ON `user`.`id` = `profile_subscription`.`user_id` '
             . 'GROUP BY `user`.`id` '
             . 'HAVING GROUP_CONCAT(`profile_subscription`.`status`) = SUBSTRING(REPEAT(CONCAT(",",?), COUNT(*)), 2) '
             .   'AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(MAX(`profile_subscription`.`updated_at`))) < ?'
             ;
        
        $prepStmt = $this->_dbManager->prepare($sql);
        $bindings = [
            \Pley\Enum\SubscriptionStatusEnum::CANCELLED,
            static::$CHECK_SPAN_TIME
        ];
        
        $prepStmt->execute($bindings);
        
        $userList = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return $userList;
    }
    
    /**
     * Updates the supplied contact on the Hatchbuck CRM
     * @param \Hatchbuck\Entity\Contact $contact
     */
    protected function _CancelledMember_updateUser(\Hatchbuck\Entity\Contact $contact)
    {
        $isUpdated = false;
        if ($contact->getStatus()->getId() != static::$HATCHBUCK_CONTACT_STATUS_MAP['cancelled']) {
            $status = new \Hatchbuck\Entity\Status();
            $status->setId(static::$HATCHBUCK_CONTACT_STATUS_MAP['cancelled']);

            $contact->setStatus($status);
            $isUpdated = true;
        }
        
        if ($isUpdated) {
            $this->_hatchbuck->updateContact($contact);
        }

        // Now checking the tags
        $tagList = $contact->getTagList();
        
        // Find if the user has the `member` tag, if so, remove it, and also check if the user
        // is already a cancelled (meaning this was triggered because a subscription updated)
        $isTaggedAlready = false;
        $removeTagList = [
            static::$HATCHBUCK_TAG_MAP['member'],
            static::$HATCHBUCK_TAG_MAP['pastDue'],
        ];
        foreach ($tagList as $tag) {
            if (in_array($tag->getId(), $removeTagList)) {
                $this->_hatchbuck->removeTag($contact, $tag);
                continue;
            }
            
            if ($tag->getId() == static::$HATCHBUCK_TAG_MAP['cancelled']) {
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
        $newTag->setId(static::$HATCHBUCK_TAG_MAP['cancelled']);
        
        $this->_hatchbuck->addTag($contact, [$newTag]);
    }
}
