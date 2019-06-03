<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Mail;

use \Pley\Entity\User\User;

/**
 * The <kbd>MailUser</kbd> Represents an email address and it's display name
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Mail
 * @subpackage Mail
 */
class MailUser
{
    /** @var int */
    protected $_userId;
    /** @var string */
    protected $_email;
    /** @var string */
    protected $_displayName;
    
    /**
     * Creates a new <kbd>MailUser</kbd> object from the <kbd>User</kbd> entity.
     * @param \Pley\Entity\User\User $user
     * @return \Pley\Mail\MailUser
     */
    public static function withUser(User $user)
    {
        $mailUser = new self($user->getEmail(), $user->getFirstName() . ' ' . $user->getLastName());
        
        // Since the user exists, we can collect the user id
        $mailUser->_userId = $user->getId();
        
        return $mailUser;
    }
    
    public static function withPolaroidUser($userEmail) {
        $mailUser = new self($userEmail, $userEmail);
        
        return $mailUser;        
    }
    
    /**
     * Creates a new <kbd>MailUser</kbd> object with the supplied email and display name.
     * @param string $email
     * @param string $displayName
     */
    public function __construct($email, $displayName)
    {
        $this->_email       = $email;
        $this->_displayName = $displayName;
    }

    /**
     * Returns the user id if the user exist in our system.
     * <p>If this mail user was not generated from a user in our system (i.e. A gift), then
     * this method will return <kbd>null</kbd>.
     * </p>
     * @return int Intenger if user exists in our system, <kbd>null</kbd> otherwise.
     */
    public function getUserId()
    {
        return $this->_userId;
    }
    
    /**
     * The email address of the user.
     * @return string
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * The display name to use when sending an email.
     * @return string
     */
    public function getDisplayName()
    {
        return $this->_displayName;
    }


}
