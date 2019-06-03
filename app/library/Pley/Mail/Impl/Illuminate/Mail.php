<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Mail\Impl\Illuminate;

use \Illuminate\Mail\Mailer;
use \Illuminate\Mail\Message;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Dao\Mail\EmailLogDao;
use \Pley\Mail\AbstractMail;
use \Pley\Mail\MailInfo;

/**
 * The <kbd>Mail</kbd> class is the implementation of <kbd>AbstractMail</kbd> using the Laravel's
 * libraries.
 * 
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Mail.Impl.Illuminate
 * @subpackage Mail
 */
class Mail extends AbstractMail
{
    /** @var \Illuminate\Mail\Mailer */
    protected $_mailer;
    
    public function __construct(Config $config, EmailLogDao $emailLogDao, Mailer $mailer)
    {
        parent::__construct($config, $emailLogDao);
        
        $this->_mailer = $mailer;
    }
    
    /**
     * Method that takes care of using the specific implementation to send the template email 
     * supplied with the respective replacement value map.
     * @param $templateName       Name of the email template
     * @param $dataMap            Array map with values to replace by key name
     * @param \Pley\Mail\MailInfo Object containing information to send the email to a user
     */
    protected function _sendMail($templateName, $dataMap, MailInfo $mailInfo)
    {
        $this->_mailer->send(
            $templateName,
            $dataMap, 
            function(Message $message) use ($mailInfo) {
                $fromMailUser = $mailInfo->getFromMailUser();
                $toMailUser   = $mailInfo->getToMailUser();

                $message->from($fromMailUser->getEmail(), $fromMailUser->getDisplayName());
                $message->to($toMailUser->getEmail(), $toMailUser->getDisplayName());
                $message->subject($mailInfo->getSubject());
            }
        );
    }
}
