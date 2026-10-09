-- ============================================================
-- Careygo Database Schema v3 — DTDC Tracking
-- Run this AFTER schema_v2.sql
-- ============================================================
USE `careygo`;

-- ── Add DTDC AWB column to shipments ─────────────────────────
ALTER TABLE `shipments`
    ADD COLUMN IF NOT EXISTS `dtdc_awb` VARCHAR(50) DEFAULT NULL
        COMMENT 'DTDC Air Waybill number for live tracking'
        AFTER `tracking_no`;

-- ── Manual / enriched tracking events ────────────────────────
CREATE TABLE IF NOT EXISTS `shipment_tracking_events` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `shipment_id` INT UNSIGNED  NOT NULL,
    `event_time`  DATETIME      NOT NULL,
    `location`    VARCHAR(200)  DEFAULT NULL,
    `next_destination` VARCHAR(200) DEFAULT NULL,
    `expected_at` DATETIME DEFAULT NULL,
    `status`      VARCHAR(100)  NOT NULL,
    `description` TEXT          DEFAULT NULL,
    `destination_details` TEXT DEFAULT NULL,
    `source`      ENUM('manual','dtdc') NOT NULL DEFAULT 'manual',
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_shipment_id` (`shipment_id`),
    INDEX `idx_event_time`  (`event_time`),
    FOREIGN KEY (`shipment_id`) REFERENCES `shipments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Blog posts
-- ============================================================
CREATE TABLE IF NOT EXISTS `blogs` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`            VARCHAR(180) NOT NULL,
    `slug`             VARCHAR(220) NOT NULL,
    `excerpt`          TEXT DEFAULT NULL,
    `content`          MEDIUMTEXT NOT NULL,
    `featured_image`   VARCHAR(255) DEFAULT NULL,
    `author_name`      VARCHAR(120) DEFAULT NULL,
    `meta_title`       VARCHAR(180) DEFAULT NULL,
    `meta_description` VARCHAR(255) DEFAULT NULL,
    `meta_keywords`    VARCHAR(255) DEFAULT NULL,
    `status`           ENUM('draft','published') NOT NULL DEFAULT 'draft',
    `published_at`     DATETIME DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_blogs_slug` (`slug`),
    KEY `idx_blogs_status_published` (`status`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Password reset tokens for admin and customer accounts
-- ============================================================
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `email`      VARCHAR(191) NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at`    DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `request_ip` VARCHAR(64) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_token_hash` (`token_hash`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_email` (`email`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
