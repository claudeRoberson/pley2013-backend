/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'shipping.zone.table';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

DROP TABLE IF EXISTS `shipping_rate`;
DROP TABLE IF EXISTS `shipping_zone`;
DROP TABLE IF EXISTS `shipping_zone_x_payment_plan`;

CREATE TABLE `shipping_zone` (
  `id`      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `country` VARCHAR(255) DEFAULT NULL COMMENT '2 char country ISO code, comma separated',
  `state`   VARCHAR(25) DEFAULT NULL COMMENT 'State/Region, comma separated',
  `zip`     VARCHAR(10) DEFAULT NULL COMMENT 'ZIP/Postal code',
  `name`    VARCHAR(55) DEFAULT NULL COMMENT 'Zone Display Name',
  PRIMARY KEY (`id`),
  KEY `IDX_country` (`country`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

INSERT INTO shipping_zone (`id`, `country`, `state`, `zip`, `name`) VALUES 
(1, 'US', '*', '*', 'Continental US Shipping Zone');
-- (2, 'US', 'AK,HI', '*', 'US Alaska & Hawaii Shipping Zone');
-- (3, 'CA,GB,AU,NZ,ES,IE,IL,SG,NO', '*', '*', 'Major Countries International Shipping Zone');

ALTER TABLE `payment_plan`
  DROP COLUMN `price_period`,
  DROP COLUMN `price_unit`;

ALTER TABLE `payment_plan_x_vendor_payment_plan`
  DROP INDEX `UNI_paymentPlanId_vPaymentSystemId`;

ALTER TABLE `payment_plan_x_vendor_payment_plan`
  ADD `shipping_zone_id` INT(10) UNSIGNED NOT NULL AFTER `payment_plan_id`,
  ADD `base_price` DECIMAL(8, 2) NOT NULL DEFAULT 0 AFTER `shipping_zone_id`,
  ADD `unit_price` DECIMAL(8, 2) NOT NULL DEFAULT 0 AFTER `base_price`,
  ADD `shipping_price` DECIMAL(8, 2) NOT NULL DEFAULT 0 AFTER `unit_price`,
  ADD `total` DECIMAL(8, 2) NOT NULL DEFAULT 0 AFTER `shipping_price`;

UPDATE `payment_plan_x_vendor_payment_plan`
SET `shipping_zone_id` = 1,
    `shipping_price`   = 5.00
WHERE 1;

# UPDATE queries for DisneyPrincess subscription

UPDATE `payment_plan_x_vendor_payment_plan`
SET
  `base_price` = 24.99,
  `unit_price` = 24.99,
  `total`      = 29.99
WHERE `v_payment_plan_id` = 1000;

UPDATE `payment_plan_x_vendor_payment_plan`
SET
  `base_price` = 24.99,
  `unit_price` = 23.99,
  `total`      = 86.97
WHERE `v_payment_plan_id` = 1001;

UPDATE `payment_plan_x_vendor_payment_plan`
SET
  `base_price` = 24.99,
  `unit_price` = 22.99,
  `total`      = 167.94
WHERE `v_payment_plan_id` = 1002;

ALTER TABLE `user_address`
  ADD `shipping_zone_id` INT(10) UNSIGNED DEFAULT NULL
  AFTER `country`;

# set all existing user addresses as US shipping zone
# as there is no possibility to enter non-US address at all
UPDATE `user_address`
SET `shipping_zone_id` = 1
WHERE 1;

ALTER TABLE `profile_subscription_shipment`
  ADD `shipping_zone_id` INT(10) UNSIGNED DEFAULT NULL AFTER `country`;

ALTER TABLE `profile_subscription_shipment`
  CHANGE `shipping_zone` `shipping_zone_usps` INT(10) UNSIGNED DEFAULT NULL;

/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
