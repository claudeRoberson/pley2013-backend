/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'nat_geo.digital_exp';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------


CREATE TABLE `ng_mission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `ng_mission` (`id`, `name`) VALUES
(1, 'Biomes'),
(2, 'Artic'),
(3, 'Serengeti'),
(4, 'Amazon'),
(8, 'Great Barrier Reef'),
(10, 'Everglades'),
(11, 'Virunga');


CREATE TABLE `item_x_ng_mission` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL,
  `mission_id`int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_itemId` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*
INSERT INTO `item` (`name`, `description`, `length_cm`, `width_cm`, `height_cm`, `weight_gr`, `created_at`)
VALUES ('Amazon', '1 NatGeo Box', 26, 15, 10, 907, NOW());

SET @item_amazonId = LAST_INSERT_ID();

INSERT INTO `item_x_ng_mission` (`item_id`, `mission_id`)
VALUES (@item_amazonId, 4);
*/


/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
