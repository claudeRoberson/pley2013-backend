/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'user.registration.waitlist';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

CREATE TABLE `user_waitlist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `user_profile_id` int(10) unsigned DEFAULT NULL,
  `user_address_id` int(10) unsigned DEFAULT NULL,
  `subscription_id` int(10) unsigned NOT NULL,
  `payment_plan_id` int(10) unsigned DEFAULT NULL,
  `gift_id` int(10) unsigned DEFAULT NULL,
  `referral_token` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `coupon_id` int(10) unsigned DEFAULT NULL,
  `notification_count` smallint(5) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user_id`),
  KEY `FK_user_profile` (`user_profile_id`),
  KEY `FK_user_address` (`user_address_id`),
  KEY `FK_subscription` (`subscription_id`),
  KEY `FK_payment_plan` (`payment_plan_id`),
  KEY `FK_gift` (`gift_id`),
  KEY `FK_coupon` (`coupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `user_waitlist_shared` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `FK_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `subscription` 
  ADD COLUMN `deadline_extended_days` tinyint(2) unsigned NOT NULL AFTER `delivery_day_end`;

UPDATE `subscription` SET `deadline_extended_days` = 30 WHERE `id` = 1;


/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------




