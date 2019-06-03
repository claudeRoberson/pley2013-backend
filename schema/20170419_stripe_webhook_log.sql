/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'stripe.webhook.log.table';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------
DROP TABLE IF EXISTS `stripe_webhook_log`;

CREATE TABLE `stripe_webhook_log` (
  `id`                         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id`                   VARCHAR(55) NOT NULL,
  `event_type`                 VARCHAR(55) NOT NULL,
  `response_status_sent`       SMALLINT(5) UNSIGNED NOT NULL,
  `created_at`         TIMESTAMP        NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_event_id` (`event_id`)
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