/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'frontend.popup';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

CREATE TABLE `popup_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `index` int(10) unsigned NOT NULL,
  `is_enabled` tinyint(1) unsigned NOT NULL,
  `type_popup_event_id` int(10) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `body` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
  `sec_delay` int(10) unsigned NOT NULL,
  `coupon_id` int(10) unsigned DEFAULT NULL,
  `type_popup_action_id` int(10) unsigned DEFAULT NULL,
  `popup_action_params_json` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_index` (`index`),
  KEY `IDX_coupon` (`coupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*
INSERT INTO `popup_event` (`id`, `index`, `is_enabled`, `type_popup_event_id`, `title`, `body`, `sec_delay`, `coupon_id`, `type_popup_action_id`, `popup_action_params_json`, `created_at`) VALUES
(1, 0, 1, 1, 'First Popup', 'Bla Bla Bla', 3, 2, NULL, NULL, NOW()),
(2, 1, 1, 2, 'Second Popup', 'Whoa whoa', 5, NULL, 1, '{\"emailTemplateId\":123, \"couponId\":123}', NOW());
*/

CREATE TABLE `type_popup_event` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `type_popup_event` (`id`, `name`) VALUES
(1, 'Email Share'),
(2, 'Social Media Share');


CREATE TABLE `type_popup_action` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `type_popup_action` (`id`, `name`) VALUES
(1, 'Send Email with Coupon');


/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------


