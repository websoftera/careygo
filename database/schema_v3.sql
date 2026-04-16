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
    `status`      VARCHAR(100)  NOT NULL,
    `description` TEXT          DEFAULT NULL,
    `source`      ENUM('manual','dtdc') NOT NULL DEFAULT 'manual',
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_shipment_id` (`shipment_id`),
    INDEX `idx_event_time`  (`event_time`),
    FOREIGN KEY (`shipment_id`) REFERENCES `shipments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
