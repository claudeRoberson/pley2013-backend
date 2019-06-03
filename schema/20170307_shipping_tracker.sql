/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey        = 'shipment.tracker';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

ALTER TABLE `profile_subscription_shipment`
  ADD COLUMN `v_ship_tracker_id` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL AFTER `v_ship_id`;

/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/

UPDATE `_schema_version` SET `end_time` = NOW() WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
