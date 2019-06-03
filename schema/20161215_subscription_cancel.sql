/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey        = 'subscription.cancel';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

CREATE TABLE `type_subscription_cancel_source` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `type_subscription_cancel_source` (`id`, `name`)
VALUES
    (1, 'User'),
    (2, 'Customer Service'),
    (3, 'Past Due');


ALTER TABLE `profile_subscription`
  ADD COLUMN `status` int(10) unsigned NOT NULL DEFAULT "1" AFTER `gift_id`;

UPDATE `profile_subscription`
SET `status` = 4
WHERE `user_payment_method_id` IS NULL;


ALTER TABLE `profile_subscription`
    ADD COLUMN `is_auto_renew` tinyint(1) unsigned NOT NULL AFTER `status`;

UPDATE `profile_subscription`
SET `is_auto_renew` = 1
WHERE `status` <> 4;

ALTER TABLE `profile_subscription_plan` 
    ADD COLUMN `auto_renew_stop_at` timestamp NULL DEFAULT NULL AFTER `v_payment_subscription_id`;


/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/

UPDATE `_schema_version` SET `end_time` = NOW() WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------