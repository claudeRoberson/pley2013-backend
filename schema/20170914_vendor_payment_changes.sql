/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'payment.plan.vendor';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

ALTER TABLE `payment_plan_x_vendor_payment_plan`
  CHANGE COLUMN `v_payment_plan_id` `v_payment_plan_id` VARCHAR(255) DEFAULT NULL;

UPDATE payment_plan
SET description = 'Disney 1 charge every 2 months'
WHERE id = 1;
UPDATE payment_plan
SET description = 'Disney 1 charge every 6 months'
WHERE id = 2;
UPDATE payment_plan
SET description = 'Disney 1 charge every 12 months'
WHERE id = 3;
UPDATE payment_plan
SET description = 'NatGeo 1 charge every 1 months'
WHERE id = 4;
UPDATE payment_plan
SET description = 'NatGeo 1 charge every 6 months'
WHERE id = 5;
UPDATE payment_plan
SET description = 'NatGeo 1 charge every 12 months'
WHERE id = 6;

INSERT INTO payment_plan_x_vendor_payment_plan (payment_plan_id, shipping_zone_id, base_price, unit_price, shipping_price,  total, v_payment_system_id, v_payment_plan_id, created_at, updated_at) VALUES (1, 1, 24.99, 24.99, 5.00,  29.99, 2, 'P-29F05213LL1014839QEHLTSI', '2017-09-14 15:47:19', '2017-09-14 15:47:19');
INSERT INTO payment_plan_x_vendor_payment_plan (payment_plan_id, shipping_zone_id, base_price, unit_price, shipping_price,  total, v_payment_system_id, v_payment_plan_id, created_at, updated_at) VALUES (2, 1, 24.99, 23.99, 5.00, 86.97, 2, 'P-5YU651965B295824UQEH35II', '2017-09-14 15:47:19', '2017-09-14 15:47:19');
INSERT INTO payment_plan_x_vendor_payment_plan (payment_plan_id, shipping_zone_id, base_price, unit_price, shipping_price,  total, v_payment_system_id, v_payment_plan_id, created_at, updated_at) VALUES (3, 1, 24.99, 22.99, 5.00, 167.94, 2, 'P-1E017658RL863372FQEIIRBQ', '2017-09-14 15:47:19', '2017-09-14 15:47:19');

INSERT INTO payment_plan_x_vendor_payment_plan (payment_plan_id, shipping_zone_id, base_price, unit_price, shipping_price,  total, v_payment_system_id, v_payment_plan_id, created_at, updated_at) VALUES (4, 1, 19.99, 19.99, 4.95, 24.94, 2, 'P-4Y438307AJ967330VQXFCGSY', '2017-09-14 15:47:19', '2017-09-14 15:47:19');
INSERT INTO payment_plan_x_vendor_payment_plan (payment_plan_id, shipping_zone_id, base_price, unit_price, shipping_price,  total, v_payment_system_id, v_payment_plan_id, created_at, updated_at) VALUES (5, 1, 19.99, 18.99, 4.95, 143.64, 2, 'P-55C70111PU926624TQXFQRHI', '2017-09-14 15:47:19', '2017-09-14 15:47:19');
INSERT INTO payment_plan_x_vendor_payment_plan (payment_plan_id, shipping_zone_id, base_price, unit_price, shipping_price,  total, v_payment_system_id, v_payment_plan_id, created_at, updated_at) VALUES (6, 1, 19.99, 17.99, 4.95, 275.28, 2, 'P-37L78169T66856940QXGBZLI', '2017-09-14 15:47:19', '2017-09-14 15:47:19');

/* PRODUCTION PAYMENT PLAN IDs
INSERT INTO payment_plan_x_vendor_payment_plan (payment_plan_id, shipping_zone_id, base_price, unit_price, shipping_price,  total, v_payment_system_id, v_payment_plan_id, created_at, updated_at) VALUES (1, 1, 24.99, 24.99, 5.00,  29.99, 2, 'P-6PK75655FS986390NXHBHO3I', '2017-09-14 15:47:19', '2017-09-14 15:47:19');
INSERT INTO payment_plan_x_vendor_payment_plan (payment_plan_id, shipping_zone_id, base_price, unit_price, shipping_price,  total, v_payment_system_id, v_payment_plan_id, created_at, updated_at) VALUES (2, 1, 24.99, 23.99, 5.00, 86.97, 2, 'P-8V136694EH316762KXHBQPQA', '2017-09-14 15:47:19', '2017-09-14 15:47:19');
INSERT INTO payment_plan_x_vendor_payment_plan (payment_plan_id, shipping_zone_id, base_price, unit_price, shipping_price,  total, v_payment_system_id, v_payment_plan_id, created_at, updated_at) VALUES (3, 1, 24.99, 22.99, 5.00, 167.94, 2, 'P-6D831500WS710391UXHBZE7A', '2017-09-14 15:47:19', '2017-09-14 15:47:19');

INSERT INTO payment_plan_x_vendor_payment_plan (payment_plan_id, shipping_zone_id, base_price, unit_price, shipping_price,  total, v_payment_system_id, v_payment_plan_id, created_at, updated_at) VALUES (4, 1, 19.99, 19.99, 4.95, 24.94, 2, 'P-00W05512BG047562RXHB73DY', '2017-09-14 15:47:19', '2017-09-14 15:47:19');
INSERT INTO payment_plan_x_vendor_payment_plan (payment_plan_id, shipping_zone_id, base_price, unit_price, shipping_price,  total, v_payment_system_id, v_payment_plan_id, created_at, updated_at) VALUES (5, 1, 19.99, 18.99, 4.95, 143.64, 2, 'P-13S7197587038881PXHCGLXY', '2017-09-14 15:47:19', '2017-09-14 15:47:19');
INSERT INTO payment_plan_x_vendor_payment_plan (payment_plan_id, shipping_zone_id, base_price, unit_price, shipping_price,  total, v_payment_system_id, v_payment_plan_id, created_at, updated_at) VALUES (6, 1, 19.99, 17.99, 4.95, 275.28, 2, 'P-2S70033508134472LXHCN2XA', '2017-09-14 15:47:19', '2017-09-14 15:47:19');
*/
/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------



