/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey        = 'subscription.item.part-stock';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

CREATE TABLE `item_part_stock` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `item_part_id` int(10) unsigned NOT NULL,
  `type_item_part_id` smallint(5) unsigned NOT NULL,
  `type_item_part_source_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `inducted_stock` int(10) unsigned NOT NULL DEFAULT '0',
  `stock` int(10) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_item` (`item_id`),
  KEY `FK_item_part` (`item_part_id`),
  KEY `FK_type_item_part` (`type_item_part_id`),
  UNIQUE KEY `UNI_itemPart_typeItemPartSource` (`item_part_id`, `type_item_part_source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `item_part_stock` (`id`, `item_id`, `item_part_id`, `type_item_part_id`, `type_item_part_source_id`, `inducted_stock`, `stock`, `created_at`, `updated_at`)
VALUES
	(1, 1, 1, 2, 4, 0, 0, NOW(), '0000-00-00 00:00:00'),
	(2, 1, 1, 2, 5, 0, 0, NOW(), '0000-00-00 00:00:00'),
	(3, 1, 1, 2, 6, 0, 0, NOW(), '0000-00-00 00:00:00'),
	(4, 1, 2, 1, 0, 0, 0, NOW(), '0000-00-00 00:00:00'),
	(6, 1, 3, 1, 0, 0, 0, NOW(), '0000-00-00 00:00:00'),
	(7, 1, 4, 1, 0, 0, 0, NOW(), '0000-00-00 00:00:00');



ALTER TABLE `item_part` DROP COLUMN `stock`;

INSERT INTO `item_part` (`id`, `item_id`, `name`, `type_item_part_id`, `is_need_mod`, `image`, `created_at`, `updated_at`)
VALUES
	(1, 1, 'Belle Shirt', 2, 1, 'http://local.be.toybox.com/img/subscription/1/sub_001_01.JPG', NOW(), '0000-00-00 00:00:00'),
	(2, 1, 'B&B Magazine', 1, 0, 'http://local.be.toybox.com/img/subscription/1/sub_001_02.JPG', NOW(), '0000-00-00 00:00:00'),
	(3, 1, 'Belle Tiara', 1, 0, 'http://local.be.toybox.com/img/subscription/1/sub_001_03.JPG', NOW(), '0000-00-00 00:00:00'),
	(4, 1, 'B&B Figurines', 1, 0, 'http://local.be.toybox.com/img/subscription/1/sub_001_04.JPG', NOW(), '0000-00-00 00:00:00');


/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/

UPDATE `_schema_version` SET `end_time` = NOW() WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
