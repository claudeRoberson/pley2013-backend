<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MayAssignmentsTemp extends Migration
{

    /** @var \Pley\Db\AbstractDatabaseManager */
    protected $_dbManager;

    public function __construct()
    {
        $this->_dbManager = \App::make('\Pley\Db\AbstractDatabaseManager');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = '
CREATE TABLE `may_assignments_temp` (
  `id`            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_subscription_id`          VARCHAR(55)      NOT NULL,
  `size_id`  TEXT                      DEFAULT NULL,
  `item_id` TEXT                      DEFAULT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
';

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
        //
    }

}
