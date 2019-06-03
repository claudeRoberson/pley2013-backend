/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'user.registration.waitlist_release_attempts';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------
ALTER TABLE `user_waitlist`
  ADD COLUMN release_attempts INT(10) UNSIGNED DEFAULT 0 NOT NULL
  AFTER `notification_count`;
ALTER TABLE `user_waitlist`
  ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP
  AFTER `created_at`;
ALTER TABLE `user_waitlist`
  ADD COLUMN `payment_attempt_at` TIMESTAMP DEFAULT NULL
  AFTER `release_attempts`;

UPDATE `user_waitlist`
SET `release_attempts` = 0
WHERE 1;
/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------




