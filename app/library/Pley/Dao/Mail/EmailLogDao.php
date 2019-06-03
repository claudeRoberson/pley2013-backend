<?php /** @copyright Pley (c) 2015, All Rights Reserved */
namespace Pley\Dao\Mail;

use \Pley\Dao\AbstractDbDao;
use \Pley\Dao\DaoInterface;
use \Pley\Dao\DbDaoInterface;
use \Pley\Dao\Exception\DaoUnsupportedMethodException;
use \Pley\Entity\Mail\MailLog;

/**
 * The <kbd>EmailLogDao</kbd> class provides implementation to interact with the Email Log table in
 * the DB.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 2.0
 * @package Pley.Dao.Mail
 * @subpackage Dao
 */
class EmailLogDao extends AbstractDbDao implements DaoInterface, DbDaoInterface
{
    const VERSION = 2;
    
    /** @var string */
    protected $_tableName = 'email_log';
    /**
     * The string list of escaped column names to retrieve data for the table controlled by this DAO
     * @var string
     */
    protected $_columnNames;
 
    public function __construct()
    {
        $escapedColumnNames = $this->_escapedFields([
            'id', 'user_id', 'email_template_id', 'email_to', 'email_from', 'email_on_behalf_of',
            'ref_data','created_at'
        ]);
        $this->_columnNames = implode(',', $escapedColumnNames);
    }
    
    /** {@inheritdoc} */
    public function find($id)
    {
        throw new \Pley\Exception\MissingImplementationException(__METHOD__);
    }
    
    /**
     * find whether an email template was sent to the user.
     * true if email was sent, false otherwise
     * @param type $userId
     * @param type $templateId
     * @return boolean
     */
    public function findByUserId($userId, $templateId) {
        
        $prepSql  = "SELECT {$this->_columnNames} FROM `{$this->_tableName}` "
                  . 'WHERE `user_id` = ? AND `email_template_id` = ?';
        $pstmt    = $this->_prepare($prepSql);
        $bindings = [$userId, $templateId];

        $pstmt->execute($bindings);
                
        $dbRecord = $pstmt->fetch(\PDO::FETCH_ASSOC);
        
        $pstmt->closeCursor();
        
        if (is_null($dbRecord) || empty($dbRecord)) {
            return false;
        }
        
        return true;

        
    }
    
    /**
     * Takes an <kbd>EmailLog</kbd> entity object and saves it into the Storage.
     * <p>Update operation is not allowed.</p>
     * 
     * @param \PleyWorld\Entity\Mail\MailLog $mailLog The Entity object to save
     * @throws \PleyWorld\Dao\Exception\DaoUnsupportedMethodException If an attempt to update is done.
     */
    public function save(MailLog $mailLog)
    {
        // Update operation not allowed
        if (!empty($mailLog->getId())) {
            new DaoUnsupportedMethodException($this, 'save{update}');
        }
        
        $prepSql = "INSERT INTO `{$this->_tableName}` ("
                 .     '`user_id`, `email_template_id`, `email_to`, `email_from`, `email_on_behalf_of`, '
                 .     '`ref_data`, '
                 .     '`version`, `created_at`'
                 . ') VALUES (?, ?, ?, ?, ?, ?, ?, NOW())';
        
        $pstmt    = $this->_prepare($prepSql);
        
        // Parsing the Reference Data array into a json string
        $refData = null;
        if ($mailLog->getRefData() != null) {
            $refData = json_encode($mailLog->getRefData());
        }
        
        $bindings = [
            $mailLog->getUserId(),
            $mailLog->getTemplateId(),
            $mailLog->getMailTo(),
            $mailLog->getMailFrom(),
            $mailLog->getMailOnBehalfOf(),
            $refData,
            self::VERSION,
        ];
        
        $pstmt->execute($bindings);
        
        // Updating the ID of the Entity
        $id = $this->_dbManager->lastInsertedId();
        $pstmt->closeCursor();
        $mailLog->setId($id);
    }
    
    /**
     * Map an associative array DB record into a EmailLog Entity.
     * 
     * @param array $dbRecord
     * @return \PleyWorld\Entity\Mail\MailLog
     */
    protected function _toEntity($dbRecord)
    {
        if (empty($dbRecord)) {
            return null;
        }
        
        
        
    }
}
