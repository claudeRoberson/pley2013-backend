/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'payment_plan.subscription_id';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

ALTER TABLE `payment_plan`
  ADD `subscription_id` TINYINT(1) NOT NULL DEFAULT 0 AFTER `id`;

UPDATE `payment_plan` SET
  subscription_id = 1 WHERE `payment_plan`.description LIKE "%Disney%";
UPDATE `payment_plan` SET
  subscription_id = 2 WHERE `payment_plan`.description LIKE "%NatGeo%";
UPDATE `payment_plan` SET
  subscription_id = 3 WHERE `payment_plan`.description LIKE "%HotWheels%";

/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
