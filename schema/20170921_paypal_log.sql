/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'paypal.log.table';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------
DROP TABLE IF EXISTS `paypal_api_log`;

CREATE TABLE `paypal_api_log` (
  `id`            INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`          VARCHAR(55)      NOT NULL,
  `request_json`  TEXT                      DEFAULT NULL,
  `response_json` TEXT                      DEFAULT NULL,
  `error`         TEXT                      DEFAULT NULL,
  `created_at`    TIMESTAMP        NOT NULL DEFAULT NOW(),
  `updated_at`    TIMESTAMP        NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_type` (`type`)
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