<?php /** @copyright Pley (c) 2017, All Rights Reserved */

namespace Pley\Console\VendorIntegration\Hatchbuck;

/**
 * The <kbd>RevealHandlingTrait</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @method line(string $text) Write a string as standard output.
 * @method _getHatchbuckContact(string $email) Retrieve a Contact object from Hatchbuck given its email
 * @property \Pley\Db\AbstractDatabaseManager $_dbManager Database Access
 */
trait RevealHandlingTrait
{
    protected function _Reveal_processor()
    {
        $startTime = microtime(true);

        $this->line('Processing Reveal Subscribers');
        $userList = $this->_Reveal_getDbList();

        $progressPrinter = new \Pley\Console\Util\ProgressPrinter();

        $statsTotalDb = count($userList);
        $statsAdded = 0;

        var_dump($userList);

        foreach ($userList as $userArray) {
            $progressPrinter->step();

            $hbContact = $this->_getHatchbuckContact($userArray['email']);

            if (isset($hbContact)) {
                $tagList = $hbContact->getTagList();

                $isTaggedAlready = false;
                foreach ($tagList as $tag) {
                    if ($tag->getId() == static::$HATCHBUCK_TAG_MAP['tbPrincessReveal']) {
                        $isTaggedAlready = true;
                        break;
                    }
                }
                if ($isTaggedAlready) {
                    continue;
                }

                $tagReveal = new \Hatchbuck\Entity\Tag();
                $tagReveal->setId(static::$HATCHBUCK_TAG_MAP['tbPrincessReveal']);

                $this->_hatchbuck->addTag($hbContact, [$tagReveal]);
                $statsAdded++;
                continue;
            }

            $this->_Reveal_addUser($userArray);
            $statsAdded++;
        }
        $progressPrinter->finish();

        $endTime = microtime(true);
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
    protected function _Reveal_getDbList()
    {
        $sql = 'SELECT `id`, `email` '
            . 'FROM `notification_subscriber` '
            . 'WHERE 1';

        /*        $sql = 'SELECT `id`, `email` '
                    . 'FROM `notification_subscriber` '
                    .   'WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`created_at`)) < ? '
                ;*/

        $prepStmt = $this->_dbManager->prepare($sql);
        /*        $bindings = [
                    static::$CHECK_SPAN_TIME
                ];*/

        $prepStmt->execute();

        $userList = $prepStmt->fetchAll(\PDO::FETCH_ASSOC);

        return $userList;
    }

    /**
     * Adds a user to the Hatchbuck CRM
     * @param array $userArray See <kbd>_Invite_getDbList()</kbd> for structure information.
     */
    protected function _Reveal_addUser($userArray)
    {
        $email = new \Hatchbuck\Entity\Email();
        $email->setAddress($userArray['email']);

        $status = new \Hatchbuck\Entity\Status();
        $status->setId(static::$HATCHBUCK_CONTACT_STATUS_MAP['user']);

        $contact = new \Hatchbuck\Entity\Contact();
        $contact->setStatus($status);
        $contact->setEmailList([$email]);

        $newContact = $this->_hatchbuck->addContact($contact);

        // Now adding tags to the just added contact
        $tagReveal = new \Hatchbuck\Entity\Tag();
        $tagReveal->setId(static::$HATCHBUCK_TAG_MAP['tbPrincessReveal']);

        $tagPleybox = new \Hatchbuck\Entity\Tag();
        $tagPleybox->setId(static::$HATCHBUCK_TAG_MAP['pleybox']);

        $this->_hatchbuck->addTag($newContact, [$tagReveal, $tagPleybox]);
    }
}
