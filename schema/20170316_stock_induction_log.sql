/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'stock.induction.log.table';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------
DROP TABLE IF EXISTS `item_part_induction`;
DROP TABLE IF EXISTS `stock_induction_log`;

CREATE TABLE `stock_induction_log` (
  `id`                 INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id`            INT(10) UNSIGNED NOT NULL,
  `item_part_id`       INT(10) UNSIGNED NOT NULL,
  `item_part_stock_id` INT(10) UNSIGNED NOT NULL,
  `amount`             INT(10) UNSIGNED NOT NULL,
  `comment`            VARCHAR(255)              DEFAULT NULL,
  `created_at`         TIMESTAMP        NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_item` (`item_id`),
  KEY `FK_item_part` (`item_part_id`),
  KEY `FK_item_part_stock` (`item_part_stock_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
