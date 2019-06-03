<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Dao\Frontend\Popup;

use Pley\Db\AbstractDatabaseManager as DatabaseManager;

/**
 * The <kbd>PopupEmailCaptureDao</kbd> DAO.
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.Frontend.Popup
 * @subpackage Dao
 */
class PopupEmailCaptureDao extends \Pley\DataMap\Dao
{
    public function __construct(DatabaseManager $databaseManager)
    {
        parent::__construct($databaseManager);
        
        $this->setEntityClass(\Pley\Entity\Frontend\Popup\PopupEmailCapture::class);
    }
    
    /**
     * Get a list of active popup events ordered by their index.
     * @return \Pley\Entity\Frontend\Popup\PopupEmailCapture
     */
    public function findByEmail($email)
    {
        $popupEmailCaptureList = $this->where('`email` = ?', [$email]);
        
        return empty($popupEmailCaptureList)? null : $popupEmailCaptureList[0];
    }
    
}
