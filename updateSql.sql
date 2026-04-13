ALTER TABLE `products` CHANGE `warehouse_id` `warehouse_id` INT(11) NULL DEFAULT NULL;

ALTER TABLE invoices
    ADD COLUMN division_id INT DEFAULT NULL AFTER order_no,
ADD COLUMN district_id INT DEFAULT NULL AFTER division_id;


ALTER TABLE order_items
    ADD COLUMN serial_group_id INT NULL
AFTER product_id;

ALTER TABLE `stock_purchases` CHANGE `note` `note` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `stock_purchases` CHANGE `attachment` `attachment`
    VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `product_serials` ADD `invoice_id` INT NOT NULL DEFAULT '0' AFTER `user_id`;
ALTER TABLE `order_items` ADD `serial_group_id` INT NOT NULL DEFAULT '0' AFTER `warehouse_id`;


INSERT INTO settings (`key`,`type`,setting_type,value,is_visible,created_at,updated_at) VALUES
                                                                                            ('division_manager_role','select','role_setting','6',1,'2026-02-03 04:24:59','2026-02-03 04:32:21'),
                                                                                            ('district_manager_role','select','role_setting','7',1,'2026-02-03 04:27:42','2026-02-03 04:32:21'),
                                                                                            ('financial_manager_role','select','role_setting','8',1,'2026-02-03 04:28:11','2026-02-03 04:32:21');

ALTER TABLE `orders` ADD `payment_status` INT NOT NULL DEFAULT '0' AFTER `status`;

ALTER TABLE `orders` ADD `payment_confirmed_by` INT NULL DEFAULT NULL AFTER `payment_status`;
ALTER TABLE `orders` ADD `order_approved_by` INT NULL DEFAULT NULL AFTER `payment_confirmed_by`;
ALTER TABLE `invoices` ADD `division_id` INT NULL DEFAULT NULL AFTER `order_no`,
    ADD `district_id` INT NULL DEFAULT NULL AFTER `division_id`;
ALTER TABLE `invoices` ADD `invoice_status` INT NOT NULL DEFAULT '0' AFTER `status`;
ALTER TABLE `invoices` ADD `payment_confirmed_by` INT NULL DEFAULT NULL AFTER `invoice_status`;
ALTER TABLE `invoices` ADD `order_approved_by` INT NULL DEFAULT NULL AFTER `invoice_status`;


ALTER TABLE `assign_target_products` ADD `target_type` INT NULL DEFAULT '1' COMMENT '1=Percent, 2=Fixed' AFTER `product_id`, ADD `from_amount` INT NOT NULL DEFAULT '0' AFTER `target_type`, ADD `to_amount` INT NOT NULL DEFAULT '0' AFTER `from_amount`;
ALTER TABLE `assign_target_products` CHANGE `target_amount` `commission` INT(11) NULL DEFAULT '0';


ALTER TABLE `product_serial_groups` ADD `stock_id` INT NULL DEFAULT NULL AFTER `warehouse_id`;


-- TRUNCATE TABLE `stock_purchases`;
-- TRUNCATE TABLE `stocks`;
-- TRUNCATE TABLE `product_serial_groups`;
-- TRUNCATE TABLE `product_serials`;


ALTER TABLE `products` CHANGE `expire_date` `expire_date` DATE NULL DEFAULT NULL;

