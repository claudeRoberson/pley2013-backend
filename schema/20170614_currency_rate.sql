/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'currency.rate.table';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

DROP TABLE IF EXISTS `currency_rate`;

CREATE TABLE `currency_rate` (
  `id`      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `country` VARCHAR(255) DEFAULT NULL COMMENT '2 char country ISO codes, comma separated',
  `code`   VARCHAR(25) DEFAULT NULL COMMENT 'Currency 3 char ISO code',
  `rate`   DECIMAL(8, 2) NOT NULL DEFAULT 1 COMMENT 'Exchange rate',
  `created_at`      TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at`      TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `IDX_country` (`country`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;

INSERT INTO currency_rate (`id`, `country`, `code`, `rate`) VALUES
  (NULL, 'US', 'USD', 1.00),
  (NULL, 'CA', 'CAD', 1.35),
  (NULL, 'GB', 'GBP', 0.77),
  (NULL, 'AU', 'AUD', 1.32),
  (NULL, 'NZ', 'NZD', 1.38),
  (NULL, 'IL', 'ILS', 18.71),
  (NULL, 'ES', 'EUR', 0.89),
  (NULL, 'NO', 'EUR', 0.89),
  (NULL, 'IE', 'EUR', 0.89),
  (NULL, 'SG', 'SGD', 1.38);

ALTER TABLE `user`
  ADD `country` CHAR(2) DEFAULT NULL AFTER `email`;
UPDATE `user` SET `country` = 'US' WHERE 1;
/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------
