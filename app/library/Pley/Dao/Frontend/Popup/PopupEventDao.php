<?php /** @copyright Pley (c) 2017, All Rights Reserved */
namespace Pley\Dao\Frontend\Popup;

use Pley\Db\AbstractDatabaseManager as DatabaseManager;

/**
 * The <kbd>PopupEventDao</kbd>
 *
 * @author Alejandro Salazar (alejandros@pley.com)
 * @version 1.0
 * @package Pley.Dao.Frontend.Popup
 * @subpackage Dao
 */
class PopupEventDao extends \Pley\DataMap\Dao
{
    public function __construct(DatabaseManager $databaseManager)
    {
        parent::__construct($databaseManager);
        
        $this->setEntityClass(\Pley\Entity\Frontend\Popup\PopupEvent::class);
    }
    
    /**
     * Get a list of active popup events ordered by their index.
     * @return \Pley\Entity\Frontend\Popup\PopupEvent[]
     */
    public function getActiveList()
    {
        $popupEventList = $this->where('`is_enabled` = 1', []);
        
        usort($popupEventList, function($event1, $event2) {
            /* @var $event1 \Pley\Entity\Frontend\Popup\PopupEvent */
            /* @var $event2 \Pley\Entity\Frontend\Popup\PopupEvent */
            return $event1->getIndex() - $event2->getIndex();
        });
        
        return $popupEventList;
    }
}
