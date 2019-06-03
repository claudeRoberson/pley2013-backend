/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'coupon.additional.fields';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------
ALTER TABLE `coupon`
  ADD COLUMN `subtitle` VARCHAR(255) DEFAULT NULL AFTER `min_boxes`;

ALTER TABLE `coupon`
  ADD COLUMN `title` VARCHAR(255) DEFAULT NULL AFTER `min_boxes`;

ALTER TABLE `coupon`
  ADD COLUMN `label_url` VARCHAR(255) DEFAULT NULL AFTER `title`;
/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------