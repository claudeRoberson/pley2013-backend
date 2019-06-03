/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'subscribtion.item.table';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

CREATE TABLE `subscription_item` (
  `id`              INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscription_id` INT(10) UNSIGNED NOT NULL,
  `item_id`         INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_subscription` (`subscription_id`),
  KEY `FK_item` (`item_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
INSERT INTO `subscription_item` VALUES (NULL, 1, 1);
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
