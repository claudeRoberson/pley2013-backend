<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Console\VendorIntegration\Hatchbuck;

/**
 * The <kbd>InvitedFriendHandlingTrait</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @method line(string $text) Write a string as standard output.
 * @method _getHatchbuckContact(string $email) Retrieve a Contact object from Hatchbuck given its email
 * @property \Pley\Db\AbstractDatabaseManager $_dbManager Database Access
 */
trait InvitedFriendHandlingTrait
{
    protected function _Invite_processor()
    {
        $startTime = microtime(true);
        
        $this->line('Processing Invites');
        $userList = $this->_Invite_getDbList();
        
        $progressPrinter = new \Pley\Console\Util\ProgressPrinter();
        
        $statsTotalDb = count($userList);
        $statsAdded   = 0;
        
        foreach ($userList as $userArray) {
            $progressPrinter->step();
            
            $hbContact = $this->_getHatchbuckContact($userArray['invite_email']);
            
            // If the contact already exists (don't try adding a new one, it either was read from
            // the DB due to how the execution times are set or the user was added on HB through
            // other means)
            if (isset($hbContact)) {
                continue;
            }
            
            $this->_Invite_addUser($userArray);
            $statsAdded++;
        }
        $progressPrinter->finish();
        
        $endTime     = microtime(true);
        $elapsedTime = $endTime - $startTime;
        
        $this->line('[Invites] Stats:');
        $this->line("[Invites] Added {$statsAdded} of {$statsTotalDb} read from DB.");
        $this->line(sprintf('[Invites] Elapsed Time: %.3f secs', $elapsedTime));
    }
    
    /**
     * Returns a list of array Maps containing information about the users who hasn't finished registration.
     * @return array The list has the following structure<br/>
     * <pre>array(
     * &nbsp;  array(
     * &nbsp;     'id'           => int,
     * &nbsp;     'invite_email' => string,
     * &nbsp;  ),
     * &nbsp;  ...
     * )</pre>
     */
    protected function _Invite_getDbList()
    {
        $sql = 'SELECT `id`, `invite_email` '
             . 'FROM `user_invite` '
             . 'WHERE `invite_user_id` IS NULL '
             .   'AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`created_at`)) < ? '
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
     * Adds a user to the Hatchbuck CRM
     * @param array $userArray See <kbd>_Invite_getDbList()</kbd> for structure information.
     */
    protected function _Invite_addUser($userArray)
    {
        $email = new \Hatchbuck\Entity\Email();
        $email->setAddress($userArray['invite_email']);
        
        $status = new \Hatchbuck\Entity\Status();
        $status->setId(static::$HATCHBUCK_CONTACT_STATUS_MAP['referral']);
        
        $contact = new \Hatchbuck\Entity\Contact();
        $contact->setStatus($status);
        $contact->setEmailList([$email]);
        
        $newContact = $this->_hatchbuck->addContact($contact);

        // Now adding tags to the just added contact
        $tagInvite = new \Hatchbuck\Entity\Tag();
        $tagInvite->setId(static::$HATCHBUCK_TAG_MAP['invite']);
        
        $tagPleybox = new \Hatchbuck\Entity\Tag();
        $tagPleybox->setId(static::$HATCHBUCK_TAG_MAP['pleybox']);
        
        $this->_hatchbuck->addTag($newContact, [$tagInvite, $tagPleybox]);
    }
}
