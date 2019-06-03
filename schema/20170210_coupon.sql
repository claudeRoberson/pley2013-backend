/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey        = 'coupon';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

CREATE TABLE `coupon` (
  `id`              INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`            VARCHAR(55) COLLATE utf8_unicode_ci NOT NULL,
  `type`            TINYINT(3) COLLATE utf8_unicode_ci NOT NULL,
  `enabled`         TINYINT(1) COLLATE utf8_unicode_ci DEFAULT 0,
  `discount_amount` DECIMAL(8, 2) NOT NULL,
  `subscription_id` INT(10) UNSIGNED DEFAULT NULL,
  `max_usages`      INT(10) UNSIGNED DEFAULT NULL,
  `usages_per_user` INT(10) UNSIGNED DEFAULT NULL,
  `min_boxes`       INT(10) UNSIGNED DEFAULT NULL,
  `expires_at`      TIMESTAMP NULL,
  `created_at`      TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at`      TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNQ_coupon_code` (`code`),
  KEY FK_subscription (`subscription_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;


CREATE TABLE `type_coupon` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `type_coupon` (`id`, `name`) VALUES
(1, 'Fixed amount'),
(2, 'Percent of First Box');


CREATE TABLE `user_coupon_redemption` (
  `id`                      INT(10) UNSIGNED                         NOT NULL AUTO_INCREMENT,
  `user_id`                 INT(10) UNSIGNED COLLATE utf8_unicode_ci NOT NULL,
  `coupon_id`               INT(10) UNSIGNED COLLATE utf8_unicode_ci NOT NULL,
  `transaction_id`          INT(10) UNSIGNED COLLATE utf8_unicode_ci NOT NULL,
  `profile_subscription_id` INT(10) UNSIGNED COLLATE utf8_unicode_ci NOT NULL,
  `redeemed_at`             DATETIME                                          DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user_id`),
  KEY `FK_coupon` (`coupon_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;


CREATE TABLE `type_discount` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `type_discount` (`id`, `name`) VALUES
(1, 'Coupon');


ALTER TABLE `profile_subscription_transaction`
  ADD COLUMN `base_amount` DECIMAL(8, 2) NOT NULL AFTER `type_transaction_id`;
ALTER TABLE `profile_subscription_transaction`
  ADD COLUMN `discount_amount` DECIMAL(8, 2) NOT NULL AFTER `base_amount`;
ALTER TABLE `profile_subscription_transaction`
  ADD COLUMN `discount_type` TINYINT(3) UNSIGNED DEFAULT NULL AFTER `amount`;
ALTER TABLE `profile_subscription_transaction`
  ADD COLUMN `discount_source_id` INT(10) UNSIGNED DEFAULT NULL AFTER `discount_type`;



/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/

UPDATE `_schema_version` SET `end_time` = NOW() WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
