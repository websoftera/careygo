-- ============================================================
-- Careygo Database Schema v2 — Additional Tables
-- Run this AFTER schema.sql
-- ============================================================
USE `careygo`;

-- ============================================================
-- Saved customer addresses
-- ============================================================
CREATE TABLE IF NOT EXISTS `addresses` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`       INT UNSIGNED  NOT NULL,
    `label`         VARCHAR(60)   DEFAULT NULL,
    `full_name`     VARCHAR(120)  NOT NULL,
    `phone`         VARCHAR(20)   NOT NULL,
    `address_line1` VARCHAR(255)  NOT NULL,
    `address_line2` VARCHAR(255)  DEFAULT NULL,
    `city`          VARCHAR(100)  NOT NULL,
    `state`         VARCHAR(100)  NOT NULL,
    `pincode`       VARCHAR(10)   NOT NULL,
    `is_default`    TINYINT(1)    NOT NULL DEFAULT 0,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Pricing slabs (dynamic — managed by admin)
-- Logic: if weight_to IS NULL → incremental slab (base + ceil((w-weight_from)/increment_per_kg)*increment_price)
--        if weight_to IS NOT NULL → fixed price slab (base_price)
-- ============================================================
CREATE TABLE IF NOT EXISTS `pricing_slabs` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `service_type`     ENUM('standard','premium','surface','air_cargo') NOT NULL,
    `weight_from`      DECIMAL(8,3) NOT NULL DEFAULT 0.000,
    `weight_to`        DECIMAL(8,3) DEFAULT NULL,
    `base_price`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `increment_price`  DECIMAL(10,2) DEFAULT NULL,
    `increment_per_kg` DECIMAL(8,3)  NOT NULL DEFAULT 0.500,
    `sort_order`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_service`     (`service_type`),
    INDEX `idx_weight_from` (`weight_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Customer earning percentages by pricing slab
-- ============================================================
CREATE TABLE IF NOT EXISTS `customer_earning_slabs` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id`     INT UNSIGNED NOT NULL,
    `pricing_slab_id` INT UNSIGNED NOT NULL,
    `earning_pct`     DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_customer_slab` (`customer_id`, `pricing_slab_id`),
    INDEX `idx_customer_id` (`customer_id`),
    INDEX `idx_pricing_slab_id` (`pricing_slab_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Pincode TAT details (import from Excel / manage via admin)
-- ============================================================
CREATE TABLE IF NOT EXISTS `pincode_tat` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `pincode`      VARCHAR(10)   NOT NULL,
    `city`         VARCHAR(100)  NOT NULL,
    `state`        VARCHAR(100)  NOT NULL,
    `zone`         VARCHAR(50)   DEFAULT NULL,
    `tat_standard` TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `tat_premium`  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `tat_air`      TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `tat_surface`  TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `serviceable`  TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_pincode` (`pincode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Shipments
-- ============================================================
CREATE TABLE IF NOT EXISTS `shipments` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tracking_no`       VARCHAR(30)  NOT NULL UNIQUE,
    `customer_id`       INT UNSIGNED NOT NULL,
    -- Pickup snapshot
    `pickup_name`       VARCHAR(120) NOT NULL,
    `pickup_phone`      VARCHAR(20)  NOT NULL,
    `pickup_address`    TEXT         NOT NULL,
    `pickup_city`       VARCHAR(100) NOT NULL,
    `pickup_state`      VARCHAR(100) NOT NULL,
    `pickup_pincode`    VARCHAR(10)  NOT NULL,
    -- Delivery snapshot
    `delivery_name`     VARCHAR(120) NOT NULL,
    `delivery_phone`    VARCHAR(20)  NOT NULL,
    `delivery_address`  TEXT         NOT NULL,
    `delivery_city`     VARCHAR(100) NOT NULL,
    `delivery_state`    VARCHAR(100) NOT NULL,
    `delivery_pincode`  VARCHAR(10)  NOT NULL,
    -- Service
    `service_type`      ENUM('standard','premium','surface','air_cargo') NOT NULL,
    `weight`            DECIMAL(8,3) NOT NULL DEFAULT 0.000,
    `volumetric_weight` DECIMAL(8,3) DEFAULT NULL,
    `declared_value`    DECIMAL(10,2) DEFAULT NULL,
    `pieces`            TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `description`       TEXT         DEFAULT NULL,
    `customer_ref`      VARCHAR(100) DEFAULT NULL,
    -- Additional
    `ewaybill_no`       VARCHAR(100) DEFAULT NULL,
    `packing_material`  TINYINT(1)   NOT NULL DEFAULT 0,
    -- Pricing
    `base_price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_pct`      DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    `discount_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `final_price`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `customer_earning_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `customer_earning_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    -- Payment
    `payment_method`    ENUM('prepaid','cod','credit') NOT NULL DEFAULT 'prepaid',
    `gst_invoice`       TINYINT(1)   NOT NULL DEFAULT 0,
    `gstin`             VARCHAR(20)  DEFAULT NULL,
    `pan_number`        VARCHAR(15)  DEFAULT NULL,
    -- Status & dates
    `status`            ENUM('booked','picked_up','in_transit','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'booked',
    `estimated_delivery` DATE        DEFAULT NULL,
    `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_tracking`    (`tracking_no`),
    INDEX `idx_customer`           (`customer_id`),
    INDEX `idx_status`             (`status`),
    INDEX `idx_created_at`         (`created_at`),
    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Add zone column to pricing_slabs (safe migration — skipped if already exists)
-- ============================================================
ALTER TABLE `pricing_slabs`
    ADD COLUMN IF NOT EXISTS `zone`
        ENUM('within_city','within_state','metro','rest_of_india') DEFAULT NULL
        AFTER `service_type`;

-- ============================================================
-- Seed: Pricing slabs
-- ============================================================
INSERT IGNORE INTO `pricing_slabs`
    (`service_type`, `weight_from`, `weight_to`, `base_price`, `increment_price`, `increment_per_kg`, `sort_order`)
VALUES
    -- Standard: 0–250g = ₹100 | 250–500g = ₹110 | 500g+ = ₹110 + ₹60/500g
    ('standard', 0.000, 0.250, 100.00, NULL,  0.500, 1),
    ('standard', 0.250, 0.500, 110.00, NULL,  0.500, 2),
    ('standard', 0.500, NULL,  110.00, 60.00, 0.500, 3),
    -- Premium: 0–250g = ₹245 | 250–500g = ₹260 | 500g+ = ₹260 + ₹100/500g
    ('premium',  0.000, 0.250, 245.00, NULL,   0.500, 1),
    ('premium',  0.250, 0.500, 260.00, NULL,   0.500, 2),
    ('premium',  0.500, NULL,  260.00, 100.00, 0.500, 3),
    -- Surface: 0–2.5kg = ₹225 | 2.5kg+ = ₹225 + ₹75/500g
    ('surface',   0.000, 2.500, 225.00, NULL,  0.500, 1),
    ('surface',   2.500, NULL,  225.00, 75.00, 0.500, 2),
    -- Air Cargo: 0–250g = ₹150 | 250–500g = ₹175 | 500g+ = ₹175 + ₹80/500g
    ('air_cargo', 0.000, 0.250, 150.00, NULL,  0.500, 1),
    ('air_cargo', 0.250, 0.500, 175.00, NULL,  0.500, 2),
    ('air_cargo', 0.500, NULL,  175.00, 80.00, 0.500, 3);

-- ============================================================
-- Seed: Pincode TAT data (sample — import full list via admin)
-- ============================================================
INSERT IGNORE INTO `pincode_tat`
    (`pincode`, `city`, `state`, `zone`, `tat_standard`, `tat_premium`, `tat_air`, `tat_surface`)
VALUES
    ('110001', 'New Delhi',    'Delhi',             'Metro', 2, 1, 1, 4),
    ('110011', 'New Delhi',    'Delhi',             'Metro', 2, 1, 1, 4),
    ('400001', 'Mumbai',       'Maharashtra',       'Metro', 2, 1, 1, 4),
    ('400051', 'Mumbai',       'Maharashtra',       'Metro', 2, 1, 1, 4),
    ('411001', 'Pune',         'Maharashtra',       'Tier1', 3, 1, 2, 5),
    ('411028', 'Pune',         'Maharashtra',       'Tier1', 3, 1, 2, 5),
    ('560001', 'Bengaluru',    'Karnataka',         'Metro', 2, 1, 1, 4),
    ('560011', 'Bengaluru',    'Karnataka',         'Metro', 2, 1, 1, 4),
    ('600001', 'Chennai',      'Tamil Nadu',        'Metro', 2, 1, 1, 4),
    ('600011', 'Chennai',      'Tamil Nadu',        'Metro', 2, 1, 1, 4),
    ('700001', 'Kolkata',      'West Bengal',       'Metro', 3, 1, 2, 5),
    ('500001', 'Hyderabad',    'Telangana',         'Metro', 2, 1, 1, 4),
    ('380001', 'Ahmedabad',    'Gujarat',           'Tier1', 3, 2, 2, 5),
    ('302001', 'Jaipur',       'Rajasthan',         'Tier1', 3, 2, 2, 5),
    ('226001', 'Lucknow',      'Uttar Pradesh',     'Tier1', 4, 2, 3, 6),
    ('800001', 'Patna',        'Bihar',             'Tier2', 5, 2, 3, 7),
    ('440001', 'Nagpur',       'Maharashtra',       'Tier1', 3, 2, 2, 5),
    ('641001', 'Coimbatore',   'Tamil Nadu',        'Tier1', 3, 2, 2, 5),
    ('395001', 'Surat',        'Gujarat',           'Tier1', 3, 2, 2, 5),
    ('201301', 'Noida',        'Uttar Pradesh',     'Tier1', 3, 1, 2, 5),
    ('122001', 'Gurugram',     'Haryana',           'Tier1', 3, 1, 2, 5),
    ('160017', 'Chandigarh',   'Chandigarh',        'Tier1', 3, 2, 2, 5),
    ('452001', 'Indore',       'Madhya Pradesh',    'Tier1', 3, 2, 2, 5),
    ('462001', 'Bhopal',       'Madhya Pradesh',    'Tier1', 3, 2, 2, 5),
    ('831001', 'Jamshedpur',   'Jharkhand',         'Tier2', 5, 2, 3, 7),
    ('682001', 'Kochi',        'Kerala',            'Tier1', 3, 2, 2, 5),
    ('695001', 'Thiruvananthapuram', 'Kerala',      'Tier1', 3, 2, 2, 5),
    ('751001', 'Bhubaneswar',  'Odisha',            'Tier1', 4, 2, 3, 6),
    ('248001', 'Dehradun',     'Uttarakhand',       'Tier1', 4, 2, 3, 6),
    ('781001', 'Guwahati',     'Assam',             'Tier2', 5, 3, 4, 8);
