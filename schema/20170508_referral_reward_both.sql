/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey        = 'referral.rewards';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------
ALTER TABLE `referral_engagement`
  ADD COLUMN `reward_amount` DECIMAL(8, 2) NULL AFTER `referral_token_id`;

UPDATE `referral_reward_option` SET `active` = 0 WHERE `id` IN (1,2,3);

INSERT INTO `coupon` (`code`, `type`, `enabled`, `discount_amount`, `subscription_id`, `max_usages`, `usages_per_user`, `min_boxes`, `description`, `expires_at`, `created_at`)
VALUES ('_InV1+3_', 3, 1, 15.00, NULL, NULL, 1, NULL, 'Special Coupon used for granting Invitee Discount', NULL, '2017-01-01 00:00:00');

/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/

UPDATE `_schema_version` SET `end_time` = NOW() WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
