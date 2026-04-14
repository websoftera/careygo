-- ============================================================
-- Careygo Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS `careygo` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `careygo`;

-- ============================================================
-- Users table (customers + admins)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `full_name`     VARCHAR(120)    NOT NULL,
    `email`         VARCHAR(191)    NOT NULL UNIQUE,
    `phone`         VARCHAR(20)     NOT NULL,
    `company_name`  VARCHAR(120)    DEFAULT NULL,
    `password_hash` VARCHAR(255)    NOT NULL,
    `role`          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    `status`        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email`  (`email`),
    INDEX `idx_status` (`status`),
    INDEX `idx_role`   (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- JWT token blacklist (for logout / token revocation)
-- ============================================================
CREATE TABLE IF NOT EXISTS `token_blacklist` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `jti`        VARCHAR(64)  NOT NULL UNIQUE,
    `expires_at` DATETIME     NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_jti`        (`jti`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Default admin seed  (password: Admin@123)
-- ============================================================
INSERT IGNORE INTO `users`
    (`full_name`, `email`, `phone`, `company_name`, `password_hash`, `role`, `status`)
VALUES
    ('Super Admin', 'admin@careygo.in', '9850000000', 'Careygo Logistics',
     '$2y$12$7LmXpHVDk1N.E6X9tI5D2OqF8Bz2VGm5lT4k6wS1fN9PQ3cH7YkXe',
     'admin', 'approved');
