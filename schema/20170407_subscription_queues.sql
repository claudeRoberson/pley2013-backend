/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey        = 'subscription.queue';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

CREATE TABLE `type_item_pull` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `type_item_pull` VALUES
(1, 'In Order', 'Use items for new subscriptions from the very first item available'),
(2, 'By Schedule', 'Use items for new subscriptions from the item matching the current subscription period');



ALTER TABLE `subscription` 
  ADD `type_item_pull_id` tinyint(3) unsigned NOT NULL AFTER `description`,
  ADD KEY `FK_type_item_pull` (`type_item_pull_id`);
UPDATE `subscription` SET `type_item_pull_id` = 1 WHERE `id` = 1;



RENAME TABLE `subscription_schedule` TO `subscription_item_sequence`;
ALTER TABLE `subscription_item_sequence`
  DROP KEY `UNI_subscriptionId_scheduleIndex`,
  CHANGE COLUMN `units_available` `units_programmed` int(10) unsigned NOT NULL,
  ADD COLUMN `store_units_reserved` int(10) unsigned NOT NULL AFTER `units_programmed`,
  ADD COLUMN `influencer_units_reserved` int(10) unsigned NOT NULL AFTER `store_units_reserved`,
  ADD COLUMN `subscription_units_programmed` int(10) unsigned NOT NULL COMMENT 'Result of `units_programmed` - (store+influencer reserved)' AFTER `influencer_units_reserved`,
  ADD COLUMN `subscription_units_purchased` int(10) unsigned NOT NULL COMMENT "Units that have been paid for." AFTER `subscription_units_programmed` ,
  ADD COLUMN `subscription_units_reserved` int(10) unsigned NOT NULL COMMENT "Units reserved for active subscriptions that haven't been charged yet" AFTER `subscription_units_purchased`,
  CHANGE COLUMN `schedule_index` `sequence_index` int(10) unsigned NOT NULL COMMENT 'Helper number to treat the entries as regular 0-indexed Array' AFTER `subscription_id`,
  DROP COLUMN `is_sold_out`,
  DROP COLUMN `charge_date`,
  DROP COLUMN `delivery_start_date`,
  DROP COLUMN `delivery_end_date`,
  DROP COLUMN `units_sold`,
  DROP COLUMN `has_shipments`,
  ADD UNIQUE KEY `UNI_subscriptionId_sequenceIndex` (`subscription_id`,`sequence_index`);

UPDATE `subscription_item_sequence` SET `subscription_units_programmed` = `units_programmed`;
UPDATE `subscription_item_sequence` SET `units_programmed` = 5000, `store_units_reserved` = 500, `subscription_units_programmed` = 4500 WHERE `id` = 1;
UPDATE `subscription_item_sequence` SET `units_programmed` = 2500, `subscription_units_programmed` = 2500 WHERE `id` = 2;
UPDATE `subscription_item_sequence` SET `units_programmed` = 7000, `subscription_units_programmed` = 7000 WHERE `id` = 3;


ALTER TABLE `profile_subscription`
  ADD COLUMN `item_sequence_queue_json` varchar(2000) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Queue of items to be shipped or are reserved after successful charge.  Node struct `{"seq_idx":###,"type":"P|R"}`, with 2K chars, we can store about 5 years worth of future items reserved.' AFTER `is_auto_renew`;
UPDATE `profile_subscription` SET `item_sequence_queue_json` = '[]';



DELETE FROM `profile_subscription_shipment` WHERE `subscription_schedule_id` >= 2;
ALTER TABLE `profile_subscription_shipment`
  DROP KEY `FK_subscription_schedule`,
  ADD COLUMN `subscription_id` int(10) unsigned NOT NULL AFTER `profile_subscription_id`,
  ADD COLUMN `schedule_index` int(10) unsigned NOT NULL AFTER `shipment_source_id`,
  CHANGE COLUMN `subscription_schedule_id` `item_sequence_index` int(10) unsigned NOT NULL,
  ADD COLUMN `item_id` int(10) unsigned NULL AFTER `item_sequence_index`,
  ADD KEY `FK_subscription` (`subscription_id`),
  ADD KEY `IDX_scheduleIdx` (`schedule_index`),
  ADD KEY `IDX_itemSequenceIdx` (`item_sequence_index`),
  ADD KEY `FK_item` (`item_id`);
UPDATE `profile_subscription_shipment` SET `subscription_id` = 1;
UPDATE `profile_subscription_shipment` SET `item_sequence_index` = 0;
UPDATE `profile_subscription_shipment` SET `item_id` = 1;


/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/

UPDATE `_schema_version` SET `end_time` = NOW() WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------





