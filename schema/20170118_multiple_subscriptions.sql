/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey        = 'subscription.multiple';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

ALTER TABLE `user`
ADD COLUMN `default_user_payment_method_id` int(10) unsigned NULL AFTER `v_payment_account_id`;

UPDATE `user` AS `u`
JOIN `user_payment_method` AS `upm` ON `u`.`id` = `upm`.`user_id`
SET `u`.`default_user_payment_method_id` = `upm`.`id`;

ALTER TABLE `user`
ADD COLUMN `is_receive_newsletter` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `default_user_payment_method_id`;




ALTER TABLE `user_address`
CHANGE COLUMN `address_1` `street_1` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
CHANGE COLUMN `address_2` `street_2` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL;



ALTER TABLE `profile_subscription_transaction`
ADD COLUMN `user_payment_method_id` int(10) unsigned NOT NULL AFTER `profile_subscription_plan_id`;

UPDATE `profile_subscription_transaction` AS `pst`
JOIN `user_payment_method` AS `upm` ON `upm`.`user_id` = `pst`.`user_id`
SET `pst`.`user_payment_method_id` = `upm`.`id`;



ALTER TABLE `user_payment_method`
ADD COLUMN `is_visible` tinyint(1) unsigned NOT NULL DEFAULT '1' AFTER `v_payment_method_id`;



ALTER TABLE `profile_subscription`
MODIFY COLUMN `user_address_id` int(10) unsigned NULL;



ALTER TABLE `subscription`
ADD COLUMN `welcome_email_header_img` varchar(512) COLLATE utf8_unicode_ci NOT NULL AFTER `gift_price_id_list_json`;

UPDATE `subscription`
SET `welcome_email_header_img` = 'https://dnqe9n02rny0n.cloudfront.net/pleybox/email-assets/disney-princess/headerDisneyPrincess_f44d101qhh45.jpg'
WHERE `id` = 1;

/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/

UPDATE `_schema_version` SET `end_time` = NOW() WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
