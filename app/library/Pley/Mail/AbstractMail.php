<?php /** @copyright Pley (c) 2014, All Rights Reserved */
namespace Pley\Mail;

use \Pley\Config\ConfigInterface as Config;
use \Pley\Dao\Mail\EmailLogDao;
use \Pley\Entity\Mail\MailLog;
use \Pley\Enum\Mail\MailTemplateEnum;
use \Pley\Exception\Mail\MissingMailTagException;
use \Pley\Exception\Mail\UnknownMailTemplateException;

/**
 * The <kbd>AbstractMail</kbd> class allows us define the base functionality to send an email and
 * allow a specific implementation (either 3rd party library or specific library set) to define
 * how to actually send the email.
 *
 * @abstract
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Mail
 * @subpackage Mail
 */
abstract class AbstractMail
{
    /** @var \Pley\Config\ConfigInterface */
    protected $_config;
    /** @var \Pley\Dao\Mail\EmailLogDao */
    protected $_emailLogDao;
    
    public function __construct(Config $config, EmailLogDao $emailLogDao)
    {
        $this->_config      = $config;
        $this->_emailLogDao = $emailLogDao;
    }
    
    /**
     * Send the specified template email to the given user.
     * 
     * @param int                          $templateId Value from <kbd>\Pley\Enum\Mail\MailTemplateEnum</kbd>
     * @param \Pley\Mail\MailTagCollection $mailTagCollection
     * @param \Pley\Mail\MailUser          $mailUserTo
     * @param \Pley\Mail\MailUser          $mailUserOnBehalfOf [Optional]
     * @see \Pley\Enum\Mail\MailTemplateEnum
     */
    public function send(
            $templateId, MailTagCollection $mailTagCollection, MailUser $mailUserTo,
            $options = array())
    {
        $this->_validateTemplateId($templateId);
        
        $templateDescription = $this->_getTemplateDef($templateId);
        $requiredTagList     = [];
        $templateName        = $templateDescription['template'];
        
        // Some email templates don't require tags, so, only add the tags if they have been explicitly
        // added to the configuration of the template.
        if (isset($templateDescription['requiredTagList'])) {
            $requiredTagList = $templateDescription['requiredTagList'];
        }

        $mailUserOnBehalfOf             = !empty($options['onBehalfOf']) ? $options['onBehalfOf'] : null;
        $templateDescription['subject'] = !empty($options['subjectName']) ? sprintf($templateDescription['subject'], $options['subjectName']) : $templateDescription['subject'];


        // Checking that the mail tag collection contains all the tags required by the requested template
        $this->_validateTags($templateId, $requiredTagList, $mailTagCollection);
        
        // Get the tag map and add the common-across-all-templates tags that are not entity specific.
        $tagMap = $mailTagCollection->getTagDataMap();
        $tagMap['config']     = $this->_config;
        $tagMap['constants']  = $this->_config->get('constants');
        $tagMap['siteUrl']    = $this->_config->get('mailTemplate.siteUrl');

        // Getting the email metadata
        $mailInfo = $this->_getMailInfo($templateDescription, $mailUserTo);
        
        // Call the concrete implementation to send the email
        $this->_sendMail($templateName, $tagMap, $mailInfo);
        
        // Add mail log record
        $refDataMap = $mailTagCollection->getRefDataMap();
        $this->_logMail($templateId, $mailInfo, $refDataMap, $mailUserOnBehalfOf);
    }
    
    /**
     * Method that takes care of using the specific implementation to send the template email 
     * supplied with the respective replacement value map.
     * @param $templateName       Name of the email template
     * @param $dataMap            Array map with values to replace by key name
     * @param \Pley\Mail\MailInfo Object containing information to send the email to a user
     */
    abstract protected function _sendMail($templateName, $dataMap, MailInfo $mailInfo);
    
    /**
     * Validates that the requested template id is a value in the <kbd>MailTemplateEnum</kbd> class.
     * @param int $templateId
     * @throws \Pley\Exception\Mail\UnknownMailTemplateException If the requested template id
     *      is not defined on the MailTemplateEnum (This is to promote using constants to make
     *      code more readable)
     */
    private function _validateTemplateId($templateId)
    {
        if (!in_array($templateId, MailTemplateEnum::constantsMap())) {
            throw new UnknownMailTemplateException($templateId);
        }
    }
    
    /**
     * Returns the Template definition or throw an exception if no such template exists.
     * @param int $templateId
     * @return array
     * @throws \Pley\Exception\Mail\UnknownMailTemplateException If the requested template id
     *      does not exist (or configured)
     */
    private function _getTemplateDef($templateId)
    {
        $templateMap = $this->_config->get('mailTemplate.map');
        
        if (!isset($templateMap[$templateId])) {
            throw new UnknownMailTemplateException($templateId);
        }
        
        return $templateMap[$templateId];
    }
    
    /**
     * Helper method to validate that all the Entities required for all the template tags to be parsed
     * are supplied.
     * 
     * @param int                               $templateId
     * @param array                             $requiredTagList
     * @param \Pley\Mail\MailTagCollection $mailTagCollection
     * @throws \Pley\Exception\Mail\MissingMailTagException if a tag is not supplied.
     */
    private function _validateTags($templateId, $requiredTagList, MailTagCollection $mailTagCollection)
    {
        $tagNameSet = $mailTagCollection->getTagNameSet();
        
        foreach($requiredTagList as $tagName) {
            if (!isset($tagNameSet[$tagName])) {
                throw new MissingMailTagException($templateId, $tagName);
            }
        }
    }
    
    /**
     * Helper method to get the "from" MailUser object to use when sending this email.
     * <p>It will chose the correct source whether the default From info or an alternate if specified
     * by the specific template description.</p>
     * 
     * @param array               $templateDescription
     * @param \Pley\Mail\MailUser $mailUserTo
     * @return \Pley\Mail\MailInfo
     */
    private function _getMailInfo($templateDescription, MailUser $mailUserTo)
    {
        // Default location to pick the address and name to use for sender
        $configFromSource = 'mail.from';
        
        if (isset($templateDescription['alternateFrom'])) {
            // e.g. AlternateFromSource = 'question'
            // value would yield 'mail.fromAlternate.question'
            $configFromSource = 'mail.fromAlternate.' . $templateDescription['alternateFrom'];
        }
        
        $fromEmail = $this->_config[$configFromSource.'.address'];
        $fromName  = $this->_config[$configFromSource.'.name'];
        
        $mailUserFrom = new MailUser($fromEmail, $fromName);
        
        return new MailInfo($templateDescription['subject'], $mailUserFrom, $mailUserTo);
    }
    
    /**
     * Helper method to create a mail log of the current email sent.
     * @param int                                           $templateId
     * @param \Pley\Mail\MailInfo                           $mailInfo
     * @param \Pley\Mail\Replacer\AbstractMailTagReplacer[] $refDataMap
     * @param \Pley\Mail\MailUser                           $mailUserOnBehalfOf
     */
    private function _logMail(
            $templateId, MailInfo $mailInfo, $refDataMap, MailUser $mailUserOnBehalfOf = null)
    {
        // Getting the email of the reference user that tirggered this email if any.
        $mailOnBehalfOf = null;
        if (isset($mailUserOnBehalfOf)) {
            $mailOnBehalfOf = $mailUserOnBehalfOf->getEmail();
        }
        
        // In case there is no data on the replacer ref data array, set it to null, we don't need
        // to store an empty array.
        if (empty($refDataMap)) {
            $refDataMap = null;
        }
        
        $mailLog = MailLog::withNew(
            $mailInfo->getToMailUser()->getUserId(),
            $templateId,
            $mailInfo->getToMailUser()->getEmail(),
            $mailInfo->getFromMailUser()->getEmail(),
            $mailOnBehalfOf,
            $refDataMap
        );
        
        $this->_emailLogDao->save($mailLog);
    }
}
