/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey        = 'registration.incomplete';
SET @schemaKeyVersion = 1;

ALTER TABLE `_schema_version`
  ADD COLUMN `key_version` INT(10) UNSIGNED NOT NULL AFTER `key`;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

CREATE TABLE `user_incomplete_registration` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `subscription_id` int(10) unsigned NOT NULL,
  `payment_plan_id` int(10) unsigned NOT NULL,
  `profile_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `profile_gender` enum('male','female') COLLATE utf8_unicode_ci NOT NULL,
  `profile_type_shirt_size_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNI_userId` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


INSERT INTO `user_incomplete_registration` 
    (`id`, `user_id`, `subscription_id`, `payment_plan_id`, `profile_name`, `profile_gender`, `profile_type_shirt_size_id`, `created_at`)
SELECT 
    `user`.`id`, `user`.`id` AS `user_id`,
    1 AS `subscription_id`, 
    1 AS `payment_plan_id`,
    CONCAT(`user_profile`.`first_name`, COALESCE(CONCAT(' ', `user_profile`.`last_name`), '')) AS `profile_name`, 
    `user_profile`.`gender`,
    `user_profile`.`type_shirt_size_id`,
    NOW()
FROM `user`
LEFT JOIN `user_profile` ON `user`.`id` = `user_profile`.`user_id`
WHERE `v_payment_account_id` IS NULL;


ALTER TABLE `profile_subscription`
  ADD COLUMN `gift_id` int(10) unsigned NULL AFTER `user_payment_method_id`,
  ADD UNIQUE KEY `UNI_giftId` (`gift_id`);

-- ADD manually the ids on the profile subscriptin for redeemed gifts

/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/

UPDATE `_schema_version` SET `end_time` = NOW() WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------