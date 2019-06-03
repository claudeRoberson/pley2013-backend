<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use \Pley\Db\AbstractDatabaseManager;

class UserReferrer extends Migration
{

    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;

    public function __construct()
    {
        $this->_dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');
    }

    public function up()
    {
        $sql = 'ALTER TABLE `user` ADD COLUMN 
`referrer` TEXT DEFAULT NULL 
AFTER `is_receive_newsletter`';

        $prepStmt = $this->_dbManager->prepare($sql);
        $prepStmt->execute();
        $prepStmt->closeCursor();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }

}
