<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Mail;

/**
 * The <kbd>MailInfo</kbd> is a plain object to group information about sending an email.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Mail
 * @subpackage Mail
 */
class MailInfo
{
    /** @var string */
    protected $_subject;
    /** @var \Pley\Mail\MailUser */
    protected $_fromMailUser;
    /** @var \Pley\Mail\MailUser */
    protected $_toMailUser;
    
    public function __construct($subject, MailUser $fromMailUser, MailUser $toMailUser)
    {
        $this->_subject      = $subject;
        $this->_fromMailUser = $fromMailUser;
        $this->_toMailUser   = $toMailUser;
    }

    /**
     * Return the email subject
     * @return string
     */
    public function getSubject()
    {
        return $this->_subject;
    }

    /**
     * Returns the <kbd>MailUser</kbd> of the user who is sending the email
     * @return \Pley\Mail\MailUser
     */
    public function getFromMailUser()
    {
        return $this->_fromMailUser;
    }

    /**
     * Returns the <kbd>MailUser</kbd> of the user who is receiving the email
     * @return \Pley\Mail\MailUser
     */
    public function getToMailUser()
    {
        return $this->_toMailUser;
    }

}
