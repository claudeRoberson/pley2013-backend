/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'referral.tables';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------
DROP TABLE IF EXISTS `user_invite_token`;
DROP TABLE IF EXISTS `referral_token`;
DROP TABLE IF EXISTS `referral_engagement`;
DROP TABLE IF EXISTS `referral_reward`;
DROP TABLE IF EXISTS `referral_reward_status`;
DROP TABLE IF EXISTS `type_referral_reward`;
DROP TABLE IF EXISTS `referral_reward_option`;


CREATE TABLE `referral_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `token_type_id` int(2) DEFAULT NULL,
  `active`  TINYINT(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `FK_user` (`user_id`),
  KEY `IDX_tokenType` (`token_type_id`),
  KEY `IDX_userId_token` (`user_id`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `referral_engagement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_user_id` int(10) unsigned NOT NULL,
  `engaged_user_id` int(10) unsigned NOT NULL,
  `referral_token_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_engaged_user` (`engaged_user_id`),
  KEY `FK_source_user` (`source_user_id`),
  KEY `FK_referral_token` (`referral_token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `referral_reward` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `status_id` TINYINT(1) DEFAULT 1,
  `enagaged_users_num` int(10) DEFAULT 0,
  `rewarded_option_id` VARCHAR(55) DEFAULT NULL,
  `rewarded_comment` VARCHAR(255) DEFAULT NULL,
  `rewarded_at` timestamp NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `referral_reward_option` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reward_name` VARCHAR(55) DEFAULT NULL,
  `min_engagements_threshold` TINYINT(5) DEFAULT 0,
  `active` TINYINT(5) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/**
ALTER statements for an existing user_invite table to support new referral tokens logic and flow
**/
DROP INDEX `UNI_token` ON `user_invite`;
DROP INDEX `UNI_userId_inviteEmail` ON `user_invite`;
DROP INDEX `FK_user-invite` ON `user_invite`;
DROP INDEX `FK_invite_source_type` ON `user_invite`;
DROP INDEX `IDX_rewardStatus_rewardDate` ON `user_invite`;

ALTER TABLE `user_invite`
  DROP COLUMN `token`,
  DROP COLUMN `reward_status`,
  DROP COLUMN `reward_date`,
  DROP COLUMN `reward_amount`,
  DROP COLUMN `invite_source_type_id`,
  MODIFY COLUMN `reminder_last_date` timestamp NULL,
  ADD COLUMN `referral_token_id` int(10) unsigned NOT NULL AFTER `user_id`;
CREATE INDEX `FK_token` ON `user_invite`(`referral_token_id`);

INSERT INTO `referral_reward_option` VALUES
  (NULL, "Other", 0, 1),
  (NULL, "Free Shirt", 3, 1),
  (NULL, "Movie Ticket", 5, 1);
/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
