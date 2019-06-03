/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'user.registration.waitlist_status';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------
ALTER TABLE `user_waitlist`
  ADD COLUMN status INT(10) UNSIGNED DEFAULT '1' NOT NULL
  AFTER `user_id`;

UPDATE `user_waitlist` SET `status` = 1 WHERE 1;
/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------




