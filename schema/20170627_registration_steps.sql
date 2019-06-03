/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'user.registration.steps';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

-- As Profiles can be now also added after a successful purchase, we may not have an actual profile
-- to create a subscription, so the system will be creating a Dummy one (empty) to satisfy this
-- dependency
ALTER TABLE `user_profile` 
  MODIFY `gender` enum('male','female') COLLATE utf8_unicode_ci DEFAULT NULL,
  MODIFY `first_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL;


-- Making fields optional as registration v2 is made in steps and we may not get profile info at first
ALTER TABLE `user_incomplete_registration` 
  MODIFY `profile_name` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  MODIFY `profile_gender` enum('male','female') COLLATE utf8_unicode_ci DEFAULT NULL,
  MODIFY `profile_type_shirt_size_id` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL;

/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------



