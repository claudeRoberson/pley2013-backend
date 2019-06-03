/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'payment.plans.additional.fields';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------
ALTER TABLE `payment_plan`
  ADD COLUMN `sort_order` TINYINT(1) DEFAULT NULL AFTER `period_unit`;

ALTER TABLE `payment_plan`
  ADD COLUMN `is_featured` TINYINT(1) DEFAULT NULL AFTER `sort_order`;

UPDATE payment_plan SET sort_order = 1 WHERE period = 1 OR period = 2;
UPDATE payment_plan SET sort_order = 3 WHERE period = 6;
UPDATE payment_plan SET sort_order = 2 WHERE period = 12;
UPDATE payment_plan SET is_featured = 1 WHERE period = 12;

/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------