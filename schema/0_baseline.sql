
/***************************************************************************************************
 * CREATING VERSIONING SCHEMA AN INITIALIZING THE BASE LINE VERSION *******************************/
CREATE TABLE `_schema_version` (
  `version` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(128) COLLATE utf8_unicode_ci NOT NULL,
  `start_time` TIMESTAMP NULL DEFAULT NULL,
  `end_time` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

SET @versionKey = 'baseline';
INSERT INTO `_schema_version` (`key`, `start_time`) VALUES (@versionKey, NOW());
-- -------------------------------------------------------------------------------------------------

/***************************************************************************************************
 * ADDING THE `type` TABLES ***********************************************************************/
CREATE TABLE `type_shirt_size` (
  `id` TINYINT(3) UNSIGNED NOT NULL,
  `name` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  `description` VARCHAR(256) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `type_shirt_size` (`id`, `name`, `description`)
VALUES
    (1, 'XXS', 'Extra Extra Small'),
    (2, 'XS', 'Extra Small'),
    (3, 'S', 'Small'),
    (4, 'M', 'Medium'),
    (5, 'L', 'Large'),
    (6, 'XL', 'Extra Large'),
    (7, 'XXL', 'Extra Extra Large');

CREATE TABLE `type_brand` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `type_brand` (`id`, `name`) VALUES
(1, 'Disney');

CREATE TABLE `type_item_part` (
  `id` SMALLINT(5) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `type_item_part` (`id`, `name`) VALUES
(1, 'Generic'),
(2, 'Shirt');

CREATE TABLE `type_transaction` (
  `id` TINYINT(3) UNSIGNED NOT NULL,
  `name` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `type_transaction` (`id`, `name`) VALUES
(1, 'Charge'),
(2, 'Credit'),
(3, 'Decline');

CREATE TABLE `type_shipment_source` (
  `id` TINYINT(3) UNSIGNED NOT NULL,
  `name` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  `table_source` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL, 
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `type_shipment_source` (`id`, `name`, `table_source`) VALUES
(1, 'Billing Transaction', 'profile_subscription_transaction'),
(2, 'Gift', 'gift');

CREATE TABLE `vendor_payment_system` (
  `id` TINYINT(3) UNSIGNED NOT NULL,
  `name` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `vendor_payment_system` (`id`, `name`) VALUES (1, 'Stripe');

CREATE TABLE `status_shipment` (
  `id` TINYINT(3) UNSIGNED NOT NULL,
  `name` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `status_shipment` (`id`, `name`) VALUES 
(1, 'Preshipment'),
(2, 'Processed'),
(3, 'In Transit'),
(4, 'Delivered');

CREATE TABLE `status_subscription` (
  `id` TINYINT(3) UNSIGNED NOT NULL,
  `name` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `status_subscription` (`id`, `name`) VALUES 
(1, 'Active'),
(2, 'Past Due'),
(3, 'Cancelled');

CREATE TABLE `sync_locks` (
  `id` int(10) unsigned NOT NULL,
  `description` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `sync_locks` (`id`, `description`) VALUES 
(1, 'Record Lock for processes working on `profile_subscription_shipment` when deciding which record to collect to process a label for.');
-- -------------------------------------------------------------------------------------------------

/***************************************************************************************************
 * ADDING THE Project TABLES **********************************************************************/

CREATE TABLE `payment_plan` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  `description` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  `internal_description` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  `period` TINYINT(3) UNSIGNED COLLATE utf8_unicode_ci NOT NULL,
  `period_unit` ENUM('month', 'week') NOT NULL,
  `price_period` DECIMAL(8,2) NOT NULL,
  `price_unit` DECIMAL(8,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `payment_plan` (`id`, `title`, `description`, `internal_description`, `period`, `period_unit`, `price_period`, `price_unit`, `created_at`)
VALUES
    (1, '2 Month Plan',  '', '1 charge every 2 months',   2, 'month',  29.99, 29.99, NOW()),
    (2, '6 Month Plan',  '', '1 charge every 6 months',   6, 'month',  86.97, 29.99, NOW()),
    (3, '12 Month Plan', '', '1 charge every 12 months', 12, 'month', 167.94, 29.99, NOW());

CREATE TABLE `payment_plan_x_vendor_payment_plan` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_plan_id` INT(10) UNSIGNED NOT NULL,
  `v_payment_system_id` INT(10) UNSIGNED NOT NULL,
  `v_payment_plan_id` INT(10) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNI_paymentPlanId_vPaymentSystemId` (`payment_plan_id`, `v_payment_system_id`),
  KEY `FK_vendor_payment_system` (`v_payment_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `payment_plan_x_vendor_payment_plan` (`id`, `payment_plan_id`, `v_payment_system_id`, `v_payment_plan_id`, `created_at`)
VALUES
    (1, 1, 1, 2000, NOW()),
    (2, 2, 1, 2001, NOW()),
    (3, 3, 1, 2002, NOW());

CREATE TABLE `gift_price` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  `internal_description` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  `price_total` DECIMAL(8,2) NOT NULL,
  `price_unit` DECIMAL(8,2) NOT NULL,
  `equivalent_payment_plan_id` INT(10) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `FK_payment_plan` (`equivalent_payment_plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `gift_price` (`id`, `title`, `internal_description`, `price_total`, `price_unit`, `equivalent_payment_plan_id`, `created_at`) VALUES
(1, 'Gift for 1 Toybox',   'Gift for equivalent plan of 1 box every 2 months', 34.49, 34.49, 1, NOW()),
(2, 'Gift for 3 Toyboxes', 'Gift for equivalent plan of 3 box every 6 months', 95.67, 34.49, 2, NOW()),
(3, 'Gift for 6 Toyboxes', 'Gift for equivalent plan of 6 box every 12 months', 172.98, 34.49, 3, NOW());

CREATE TABLE `subscription` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type_brand_id` INT(10) UNSIGNED NOT NULL,
  `name` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  `description` VARCHAR(128) COLLATE utf8_unicode_ci NOT NULL,
  `period` TINYINT(3) UNSIGNED NOT NULL,
  `period_unit` ENUM('month', 'week') NOT NULL,
  `start_period` TINYINT(2) UNSIGNED NOT NULL,
  `start_year` SMALLINT(4) UNSIGNED NOT NULL,
  `charge_day` TINYINT(2) UNSIGNED NOT NULL COMMENT 'Day in relation to the period and period_unit\nIf Unit = Month, then day = day of the month (1st -> 31st)\nIf Unit = Week, then day = day of the week (1 = Sunday -> 7 Saturday)\nCharge happens on the specified day based on unit, every X periods.',
  `delivery_day_start` TINYINT(2) UNSIGNED NOT NULL,
  `delivery_day_end` TINYINT(2) UNSIGNED NOT NULL COMMENT 'Day of the month/week where the expected last day of delivery could happen (if day is smaller than `delivery_day_start`, then it means the end day is in the following month or week)',
  `payment_plan_id_signup_list_json` VARCHAR(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gift_price_id_list_json` VARCHAR(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_item_charge_date` DATE NOT NULL DEFAULT '0000-00-00',
  `first_item_delivery_day_start_date` DATE NOT NULL DEFAULT '0000-00-00',
  `first_item_delivery_day_end_date` DATE NOT NULL DEFAULT '0000-00-00',
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_type_brand` (`type_brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `item` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL,
  `description` VARCHAR(256) COLLATE utf8_unicode_ci NOT NULL,
  `length_cm` SMALLINT(5) NOT NULL,
  `width_cm` SMALLINT(5) NOT NULL,
  `height_cm` SMALLINT(5) NOT NULL,
  `weight_gr` SMALLINT(5) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `item_part` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT(10) UNSIGNED NOT NULL,
  `name` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  `type_item_part_id` SMALLINT(5) UNSIGNED NOT NULL,
  `is_need_mod` TINYINT(1) UNSIGNED NOT NULL COMMENT 'indicates whether this item requires a shipment modification (like a shirt size, shirt size is needed for the specific box)',
  `stock` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `image` VARCHAR(128) COLLATE utf8_unicode_ci NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_item` (`item_id`),
  KEY `FK_type_item_part` (`type_item_part_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `item_part_induction` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT(10) UNSIGNED NOT NULL,
  `item_part_id` INT(10) UNSIGNED NOT NULL,
  `amount` INT(10) UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_item` (`item_id`),
  KEY `FK_item_part` (`item_part_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `subscription_schedule` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type_brand_id` INT(10) UNSIGNED NOT NULL COMMENT 'Only supplied for querying simplicity',
  `subscription_id` INT(10) UNSIGNED NOT NULL,
  `item_id` INT(10) UNSIGNED NOT NULL,
  `units_available` INT(10) UNSIGNED NOT NULL,
  `units_sold` INT(10) UNSIGNED NOT NULL,
  `is_sold_out` TINYINT(1) UNSIGNED NOT NULL,
  `schedule_index` INT(10) UNSIGNED NOT NULL COMMENT 'Represents the index in an array',
  `has_shipments` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Indicates if at least one shipment has been added for this scheduled item, useful to prevent future alterations of inserted items',
  `charge_date` DATE NOT NULL DEFAULT '0000-00-00',
  `delivery_start_date` DATE NOT NULL DEFAULT '0000-00-00',
  `delivery_end_date` DATE NOT NULL DEFAULT '0000-00-00',
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_subscription` (`subscription_id`),
  KEY `FK_item` (`item_id`),
  KEY `FK_type_brand` (`type_brand_id`),
  UNIQUE KEY `UNI_subscriptionId_scheduleIndex` (`subscription_id`, `schedule_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- No time to redisign, so we'll have to c/p the older tables
/*CREATE TABLE `op_user` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL,
  `username` VARCHAR(24) COLLATE utf8_unicode_ci NOT NULL,
  `email` VARCHAR(64) COLLATE utf8_unicode_ci NULL,
  `password` VARCHAR(128) COLLATE utf8_unicode_ci NOT NULL,
  `status` TINYINT(2) UNSIGNED NOT NULL,
  `role_rules_json` VARCHAR(256) COLLATE utf8_unicode_ci NOT NULL DEFAULT '{}' COMMENT 'Json Containig roles and optional restrictions',
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;*/
/* Sample Rules JSON
{
   isAdmin: boolean, // If admin, a role is optional, if not, then a role(s) is/are required
   roleId: {
      // optional param, if supplied, global setting for only read
      isReadOnly: boolean, 
      // optional param, if supplied, specify access
      viewOverrides: {
         viewName : write, // If Write, then Read is implied
         view2Name : null, // Means no access,
         view3Name : read, // Means that can only read
      }
   },
   roleId2_if any …
}
*/
CREATE TABLE `op_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(64) COLLATE utf8_unicode_ci DEFAULT '',
  `password` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `activated` tinyint(4) NOT NULL DEFAULT '0',
  `banned` tinyint(4) NOT NULL DEFAULT '0',
  `role` int(11) NOT NULL DEFAULT '0',
  `warehouse_id` int(10) unsigned NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNI_email` (`email`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `op_user` (`id`, `username`, `first_name`, `last_name`, `email`, `password`, `created_at`)
VALUES
	(1, 'sadmin', 'Admin', '-', 'superman@pley.com', '$2y$10$6ojPQmUMVapQSjUizyUlhOh8RHoNIvOhFxeaso6UL9QbpGaSdsjKK', NOW());


CREATE TABLE `user` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL,
  `email` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL,
  `password` VARCHAR(128) COLLATE utf8_unicode_ci NULL,
  `fb_token` VARCHAR(256) COLLATE utf8_unicode_ci NULL,
  `is_verified` TINYINT(1) UNSIGNED NOT NULL,
  `v_payment_system_id` TINYINT(3) UNSIGNED NULL,
  `v_payment_account_id` VARCHAR(40) COLLATE utf8_unicode_ci NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNI_email` (`email`),
  UNIQUE KEY `UNI_v_payment_account_id` (`v_payment_account_id`),
  KEY `FK_vendor_payment_system` (`v_payment_system_id`),
  KEY `IDX_first_name` (`first_name`),
  KEY `IDX_last_name` (`first_name`)
) ENGINE=InnoDB AUTO_INCREMENT=1000 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `user_address` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `address_1` VARCHAR(128) COLLATE utf8_unicode_ci NOT NULL,
  `address_2` VARCHAR(128) COLLATE utf8_unicode_ci NULL,
  `city` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL,
  `state` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL,
  `zip` VARCHAR(16) COLLATE utf8_unicode_ci NOT NULL,
  `country` CHAR(2) COLLATE utf8_unicode_ci NOT NULL COMMENT 'ISO ALPHA-2 Country Codes (http://www.nationsonline.org/oneworld/country_code_list.htm)',
  `shipping_zone_usps` TINYINT(3) UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `user_payment_method` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `v_payment_system_id` TINYINT(3) UNSIGNED NOT NULL,
  `v_payment_method_id` VARCHAR(40) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user_id`),
  KEY `FK_vendor_payment_system` (`v_payment_system_id`),
  UNIQUE KEY `UNI_v_payment_method_id` (`v_payment_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `user_profile` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `gender` ENUM('male', 'female') NOT NULL,
  `first_name` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL,
  `last_name` VARCHAR(64) COLLATE utf8_unicode_ci NULL,
  `birth_date` DATE NULL,
  `picture` VARCHAR(128) COLLATE utf8_unicode_ci NULL,
  `type_shirt_size_id` TINYINT(3) UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user_id`),
  KEY `FK_type_shirt_size` (`type_shirt_size_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `profile_subscription` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `user_profile_id` INT(10) UNSIGNED NOT NULL,
  `subscription_id` INT(10) UNSIGNED NOT NULL COMMENT 'Represents the ToyBox group (Disney Princess, Mattel hot wheels , etc)',
  `user_address_id` INT(10) UNSIGNED NOT NULL,
  `user_payment_method_id` INT(10) UNSIGNED NULL COMMENT 'Only used as reference to know a card is attached to a subscription', 
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user_id`),
  KEY `FK_user_profile` (`user_profile_id`),
  KEY `FK_subscription` (`subscription_id`),
  KEY `FK_user_address` (`user_address_id`),
  KEY `FK_user_payment_method` (`user_payment_method_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `profile_subscription_plan` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `user_profile_id` INT(10) UNSIGNED NOT NULL,
  `profile_subscription_id` INT(10) UNSIGNED NOT NULL,
  `payment_plan_id` INT(10) UNSIGNED NOT NULL,
  `status` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
  `is_auto_renew` TINYINT(1) UNSIGNED NOT NULL,
  `v_payment_system_id` TINYINT(3) UNSIGNED NOT NULL,
  `v_payment_plan_id` VARCHAR(40) COLLATE utf8_unicode_ci NOT NULL,
  `v_payment_subscription_id` VARCHAR(40) COLLATE utf8_unicode_ci NOT NULL,
  `cancel_at` TIMESTAMP NULL,
  `cancel_source` TINYINT(3) UNSIGNED NULL COMMENT 'Indicates whether it was the user that cancelled it (stop auto-renew, or an upgrade/downgrade of a plan), or payment failure, or by CS user on behalf of the user',
  `cancel_op_user_id` INT(10) UNSIGNED NULL COMMENT 'Supplied if an cancellation was done by an Operations (CS) user on behalf of the user',
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user_id`),
  KEY `FK_user_profile` (`user_profile_id`),
  KEY `FK_profile_subscription` (`profile_subscription_id`),
  KEY `FK_payment_plan` (`payment_plan_id`),
  KEY `FK_vendor_payment_system` (`v_payment_system_id`),
  KEY `FK_op_user` (`cancel_op_user_id`),
  UNIQUE KEY `UNI_v_payment_subscription_id` (`v_payment_subscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `queue_subscription_plan_change` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `user_profile_id` INT(10) UNSIGNED NOT NULL,
  `profile_subscription_id` INT(10) UNSIGNED NOT NULL,
  `payment_plan_id` INT(10) UNSIGNED NOT NULL,
  `due_date` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user_id`),
  KEY `FK_user_profile` (`user_profile_id`),
  KEY `FK_profile_subscription` (`profile_subscription_id`),
  KEY `FK_payment_plan` (`payment_plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `profile_subscription_transaction` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `user_profile_id` INT(10) UNSIGNED NOT NULL,
  `profile_subscription_id` INT(10) UNSIGNED NOT NULL,
  `profile_subscription_plan_id` INT(10) UNSIGNED NULL COMMENT 'Nullable only so that we can add the first immediate charge before a subscription is created, then it is updated, it should technically never be NULL',
  `type_transaction_id` TINYINT(3) UNSIGNED NOT NULL,
  `amount` DECIMAL(8,2) NOT NULL,
  `v_payment_system_id` TINYINT(3) UNSIGNED NOT NULL,
  `v_payment_method_id` VARCHAR(40) COLLATE utf8_unicode_ci NULL COMMENT 'Empty if transaction was a Credit, otherwise, it should be set',
  `v_payment_transaction_id` VARCHAR(40) COLLATE utf8_unicode_ci NOT NULL,
  `transaction_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `transaction_op_user_id` INT(10) UNSIGNED NULL COMMENT 'Needed when there is a Credit, or a Refund',
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_profileSubscriptionId_typeTransactionId` (`profile_subscription_id`, `type_transaction_id`),
  KEY `FK_user` (`user_id`),
  KEY `FK_user_profile` (`user_profile_id`),
  KEY `FK_profile_subscription` (`profile_subscription_id`),
  KEY `FK_profile_subscription_plan` (`profile_subscription_plan_id`),
  KEY `FK_type_transaction` (`type_transaction_id`),
  KEY `FK_vendor_payment_system` (`v_payment_system_id`),
  KEY `FK_op_user` (`transaction_op_user_id`),
  UNIQUE KEY `UNI_v_payment_transaction_id` (`v_payment_transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `profile_subscription_shipment` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `user_profile_id` INT(10) UNSIGNED NOT NULL,
  `profile_subscription_id` INT(10) UNSIGNED NOT NULL,
  `type_shipment_source_id` INT(10) UNSIGNED NOT NULL,
  `shipment_source_id` INT(10) UNSIGNED NOT NULL,
  `subscription_schedule_id` INT(10) UNSIGNED NOT NULL,
  `status` TINYINT(3) UNSIGNED NOT NULL,
  `type_shirt_size_id` TINYINT(3) UNSIGNED NULL,
  `carrier_id` INT(10) UNSIGNED NULL,
  `carrier_service_id` INT(10) UNSIGNED NULL,
  `carrier_rate` DECIMAL(8,2) UNSIGNED NULL,
  `label_url` VARCHAR(128) COLLATE utf8_unicode_ci NULL,
  `tracking_no` VARCHAR(128) COLLATE utf8_unicode_ci NULL,
  `v_ship_id` VARCHAR(40) COLLATE utf8_unicode_ci NULL,
  `shipped_at` TIMESTAMP NULL,
  `delivered_at` TIMESTAMP NULL,
  `street_1` VARCHAR(128) COLLATE utf8_unicode_ci NULL,
  `street_2` VARCHAR(128) COLLATE utf8_unicode_ci NULL,
  `city` VARCHAR(64) COLLATE utf8_unicode_ci NULL,
  `state` VARCHAR(64) COLLATE utf8_unicode_ci NULL,
  `zip` VARCHAR(16) COLLATE utf8_unicode_ci NULL,
  `country` CHAR(2) COLLATE utf8_unicode_ci NULL COMMENT 'ISO ALPHA-2 Country Codes (http://www.nationsonline.org/oneworld/country_code_list.htm)',
  `shipping_zone` TINYINT(3) UNSIGNED NULL,
  `label_purchase_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_user` (`user_id`),
  KEY `FK_user_profile` (`user_profile_id`),
  KEY `FK_profile_subscription` (`profile_subscription_id`),
  KEY `FK_type_shipment_source` (`type_shipment_source_id`),
  KEY `FK_subscription_schedule` (`subscription_schedule_id`),
  UNIQUE KEY `UNI_profileSubscriptionId_subscriptionScheduleId` (`profile_subscription_id`, `subscription_schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `gift` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(48) COLLATE utf8_unicode_ci NOT NULL,
  `is_redeemed` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `subscription_id` int(10) unsigned NOT NULL,
  `gift_price_id` int(10) unsigned NOT NULL,
  `v_payment_system_id` TINYINT(3) UNSIGNED NOT NULL,
  `v_payment_transaction_id` VARCHAR(40) COLLATE utf8_unicode_ci NOT NULL,
  `from_first_name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `from_last_name` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `from_email` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `to_first_name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `to_last_name` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `to_email` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `message` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_email_sent` tinyint(1) DEFAULT '0',
  `notify_date` DATE NOT NULL,
  `redeemed_at` timestamp NULL DEFAULT NULL,
  `redeem_user_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNI_token` (`token`),
  KEY `IDX_is_redeemed` (`is_redeemed`),
  KEY `FK_user` (`redeem_user_id`),
  KEY `FK_vendor_payment_system` (`v_payment_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*
CREATE TABLE `profile_subscription_shipment_part_mod` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_subscription_shipment_id` INT(10) UNSIGNED NOT NULL,
  `item_part_id` INT(10) UNSIGNED NOT NULL,
  `value` VARCHAR(45) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_profile_subscription_shipment` (`profile_subscription_shipment_id`),
  KEY `FK_subscription_item_part` (`item_part_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
*/

CREATE TABLE `op_cs_note` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `op_user_id` INT(10) UNSIGNED NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `note` VARCHAR(10000) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `FK_op_user` (`op_user_id`),
  INDEX `FK_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `email_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `email_template_id` int(10) unsigned NOT NULL,
  `email_to` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `email_from` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `email_on_behalf_of` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ref_data` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL,
  `version` tinyint(3) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_emailTo` (`email_to`),
  KEY `IDX_emailOnBehalfOf` (`email_on_behalf_of`),
  KEY `FK_user` (`user_id`),
  KEY `FK_email_template` (`email_template_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `email_template` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



CREATE TABLE `user_invite_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `invite_token_type_id` int(2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `FK_user` (`user_id`),
  KEY `FK_invite_token_type` (`invite_token_type_id`),
  KEY `IDX_userId_token` (`user_id`,`token`),
  KEY `IDX_userId_tokenTypeId` (`user_id`,`invite_token_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `user_invite` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(48) COLLATE utf8_unicode_ci DEFAULT NULL,
  `invite_source_type_id` smallint(5) unsigned NOT NULL,
  `invite_email` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `invite_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `invite_user_id` int(10) unsigned DEFAULT NULL,
  `status` smallint(5) unsigned DEFAULT NULL,
  `reward_status` smallint(5) unsigned DEFAULT NULL,
  `reward_date` timestamp NULL DEFAULT NULL,
  `reward_amount` decimal(8,2) DEFAULT NULL,
  `reminder_count` smallint(5) unsigned NOT NULL DEFAULT '0',
  `reminder_last_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNI_token` (`token`),
  UNIQUE KEY `UNI_userId_inviteEmail` (`user_id`,`invite_email`),
  KEY `FK_user` (`user_id`),
  KEY `FK_user-invite` (`invite_user_id`),
  KEY `FK_invite_source_type` (`invite_source_type_id`),
  KEY `IDX_inviteEmail` (`invite_email`),
  KEY `IDX_status` (`status`),
  KEY `IDX_rewardStatus_rewardDate` (`reward_status`,`reward_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;








/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/

UPDATE `_schema_version` SET `end_time` = NOW() WHERE `key` = @versionKey COLLATE utf8_general_ci;
-- -------------------------------------------------------------------------------------------------






/* Expected timeline
5th - Billing date for existing customers for this month box (if not monthly subscription, for the month the box it is shipped. i.e. all customers are aligning to 5th of the month after the first payment) 
6-10th - charge attempts for existing customers
10th - All changes to account (address, T-shirt size) should be entered
11th - Label printing existing members
12th - Start kitting existing customers
16th - New customer cut off date for this month. 
17th - Label printing new members that were added from 5th to 16th
*/
-- Cronjobs for purchase labels should run by 11th so labels are ready by 12th
-- For last two points, we may need to keep running cronjob until last delivery date
-- We may need to figure out actual deadline of box.

-- SAMPLE DATA
INSERT INTO `subscription` (
    `id`, `type_brand_id`, `name`, `description`, `period`, `period_unit`, `start_period`, `start_year`, 
    `charge_day`, `delivery_day_start`, `delivery_day_end`, 
    `payment_plan_id_signup_list_json`, `gift_price_id_list_json`,
    `first_item_charge_date`, `first_item_delivery_day_start_date`, `first_item_delivery_day_end_date`, 
    `created_at`)
VALUES (
    1, 1, 'Disney Princess', 'Princess ToyBox', 2, 'month', 3, 2017,
    5, 13, 17, '[1,2,3]', '[1,2,3]',
    '2017-03-05', '2017-03-13', '2017-03-17', 
    NOW()
);

INSERT INTO `item` (`id`, `name`, `description`, `length_cm`, `width_cm`, `height_cm`, `weight_gr`, `created_at`)
VALUES
	(1, 'Beauty & The Beast', 'First Box', 26, 15, 10, 907, NOW());

/**
-- Example
INSERT INTO `item_part` (`id`, `item_id`, `name`, `type_item_part_id`, `is_need_mod`, `image`, `stock`, `created_at`)
VALUES
	(1, 1, 'Belle Shirt', 2, 1, 'http://local.be.toybox.com/img/subscription/1/sub_001_01.JPG', 0, NOW()),
	(2, 1, 'B&B Magazine', 1, 0, 'http://local.be.toybox.com/img/subscription/1/sub_001_02.JPG', 0, NOW()),
	(3, 1, 'Belle Tiara', 1, 0, 'http://local.be.toybox.com/img/subscription/1/sub_001_03.JPG', 0, NOW()),
	(4, 1, 'B&B Figurines', 1, 0, 'http://local.be.toybox.com/img/subscription/1/sub_001_04.JPG', 0, NOW());
**/

INSERT INTO `subscription_schedule` (
    `id`, `type_brand_id`, `subscription_id`, `item_id`, `units_available`, `is_sold_out`,
    `schedule_index`, `has_shipments`, 
    `charge_date`, `delivery_start_date`, `delivery_end_date`,
    `created_at`
)
VALUES 
    (1,  1, 1, 1, 4900, 0, 1,  0, '2017-02-05', '2017-02-20', '2017-02-28', NOW()),
    (2,  1, 1, 1, 10000, 0, 2,  0, '2017-04-05', '2017-04-20', '2017-04-28', NOW()),
    (3,  1, 1, 1, 10000, 0, 3,  0, '2017-06-05', '2017-06-20', '2017-06-28', NOW()),
    (4,  1, 1, 1, 10000, 0, 4,  0, '2017-08-05', '2017-08-20', '2017-08-28', NOW()),
    (5,  1, 1, 1, 10000, 0, 5,  0, '2017-10-05', '2017-10-20', '2017-10-28', NOW()),
    (6,  1, 1, 1, 10000, 0, 6,  0, '2017-12-05', '2017-12-20', '2017-12-28', NOW()),
    (7,  1, 1, 1, 10000, 0, 7,  0, '2018-02-05', '2018-02-20', '2018-02-28', NOW()),
    (8,  1, 1, 1, 10000, 0, 8,  0, '2018-04-05', '2018-04-20', '2018-04-28', NOW()),
    (9,  1, 1, 1, 10000, 0, 9,  0, '2018-06-05', '2018-06-20', '2018-06-28', NOW()),
    (10, 1, 1, 1, 10000, 0, 10, 0, '2018-08-05', '2018-08-20', '2018-08-28', NOW()),
    (11, 1, 1, 1, 10000, 0, 11, 0, '2018-10-05', '2018-10-20', '2018-10-28', NOW()),
    (12, 1, 1, 1, 10000, 0, 12, 0, '2018-12-05', '2018-12-20', '2018-12-28', NOW()),
    (13, 1, 1, 1, 10000, 0, 13, 0, '2019-02-05', '2019-02-20', '2019-02-28', NOW()),
    (14, 1, 1, 1, 10000, 0, 14, 0, '2019-04-05', '2019-04-20', '2019-04-28', NOW()),
    (15, 1, 1, 1, 10000, 0, 15, 0, '2019-06-05', '2019-06-20', '2019-06-28', NOW()),
    (16, 1, 1, 1, 10000, 0, 16, 0, '2019-08-05', '2019-08-20', '2019-08-28', NOW()),
    (17, 1, 1, 1, 10000, 0, 17, 0, '2019-10-05', '2019-10-20', '2019-10-28', NOW()),
    (18, 1, 1, 1, 10000, 0, 18, 0, '2019-12-05', '2019-12-20', '2019-12-28', NOW())
;

    

