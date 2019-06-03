/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'referral.program.tables';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------
DROP TABLE IF EXISTS `referral_program`;

CREATE TABLE `referral_program` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) DEFAULT NULL,
  `reward_credit` DECIMAL(8, 2) NOT NULL DEFAULT 0.00,
  `acquisition_coupon_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

SET @defaultAcquisitionCouponId = (SELECT id FROM `coupon` WHERE code = "_InV1+3_");

INSERT INTO `referral_program` VALUES (NULL, 'Generic Referral Reward Program', 7.00, @defaultAcquisitionCouponId, NOW(), NOW());

ALTER TABLE `referral_token`
    ADD COLUMN `referral_program_id` int(10) unsigned NOT NULL AFTER token;
UPDATE `referral_token` SET `referral_program_id` = 1 WHERE 1;

RENAME TABLE `referral_engagement` TO `referral_acquisition`;

ALTER TABLE `referral_acquisition`
  CHANGE COLUMN `engaged_user_id` `acquired_user_id` int(10) unsigned NOT NULL;

ALTER TABLE `referral_reward`
  CHANGE COLUMN `enagaged_users_num` `acquired_users_num` int(10) DEFAULT 0;

ALTER TABLE `referral_reward_option`
  CHANGE COLUMN `min_engagements_threshold` `min_acquisitions_threshold` tinyint(5) DEFAULT 0;

ALTER TABLE `user_invite`
  CHANGE COLUMN `user_id` `user_id` int(10) DEFAULT NULL;

ALTER TABLE `user_invite`
  ADD COLUMN `referral_user_email` VARCHAR(55) DEFAULT NULL AFTER user_id;

ALTER TABLE `referral_token`
  CHANGE COLUMN `user_id` `user_id` int(10) DEFAULT NULL;

ALTER TABLE `referral_reward`
  CHANGE COLUMN `user_id` `user_id` int(10) DEFAULT NULL;

ALTER TABLE `referral_reward`
  ADD COLUMN `referral_user_email` VARCHAR(55) DEFAULT NULL AFTER user_id;

ALTER TABLE `referral_acquisition`
  CHANGE COLUMN `source_user_id` `source_user_id` int(10) DEFAULT NULL;

ALTER TABLE `referral_acquisition`
  ADD COLUMN `referral_user_email` VARCHAR(55) DEFAULT NULL AFTER source_user_id;

ALTER TABLE `referral_token`
  ADD COLUMN `referral_user_email` VARCHAR(55) DEFAULT NULL AFTER user_id;

ALTER TABLE `referral_program`
  ADD COLUMN `active` TINYINT(1) DEFAULT 0 AFTER name;
UPDATE `referral_program` SET `active` = 1 WHERE 1;

/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
