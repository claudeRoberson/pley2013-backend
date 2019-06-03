/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'user.registration.waitlist_schedule';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

CREATE TABLE `user_waitlist_schedule` (
  `id`                   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subscription_id`      INT(10) UNSIGNED NOT NULL,
  `subscription_item_id` INT(10) UNSIGNED          DEFAULT NULL,
  `waitlist_from_date`   TIMESTAMP        NOT NULL DEFAULT '0000-00-00 00:00:00',
  `waitlist_till_date`   TIMESTAMP        NOT NULL DEFAULT '0000-00-00 00:00:00',
  `enabled`              TINYINT(1) UNSIGNED       DEFAULT 0,
  `created_at`           TIMESTAMP        NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `FK_subscription` (`subscription_id`),
  KEY `FK_subscription_item_id` (`subscription_item_id`)
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




