/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'subscription.status.change.log';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

DROP TABLE IF EXISTS `profile_subscription_status_log`;

CREATE TABLE `profile_subscription_status_log` (
  `id`                      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_subscription_id` INT(10)          NOT NULL,
  `old_status`              VARCHAR(55)      NOT NULL,
  `new_status`              VARCHAR(55)      NOT NULL,
  `created_at`              TIMESTAMP        NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_profile_subscription_id` (`profile_subscription_id`)
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