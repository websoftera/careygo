<?php
/**
 * Lightweight idempotent database migrations.
 *
 * This runs from config/database.php after PDO connects, so newly deployed code
 * can create the small tables/columns it depends on without a manual SQL step.
 */

function cgo_column_exists(PDO $pdo, string $table, string $column): bool
{
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        @error_log("Migration column check failed for {$table}.{$column}: " . $e->getMessage());
        return true;
    }
}

function cgo_run_auto_migrations(PDO $pdo): void
{
    static $done = false;
    if ($done) return;

    try {
        if (!cgo_column_exists($pdo, 'shipments', 'dtdc_awb')) {
            $pdo->exec("ALTER TABLE shipments ADD COLUMN dtdc_awb VARCHAR(50) DEFAULT NULL AFTER tracking_no");
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS shipment_tracking_events (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                shipment_id INT UNSIGNED NOT NULL,
                event_time DATETIME NOT NULL,
                location VARCHAR(200) DEFAULT NULL,
                next_destination VARCHAR(200) DEFAULT NULL,
                expected_at DATETIME DEFAULT NULL,
                status VARCHAR(100) NOT NULL,
                description TEXT DEFAULT NULL,
                destination_details TEXT DEFAULT NULL,
                source ENUM('manual','dtdc') NOT NULL DEFAULT 'manual',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_shipment_id (shipment_id),
                INDEX idx_event_time (event_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $trackingColumns = [
            'next_destination' => "ALTER TABLE shipment_tracking_events ADD COLUMN next_destination VARCHAR(200) DEFAULT NULL AFTER location",
            'expected_at' => "ALTER TABLE shipment_tracking_events ADD COLUMN expected_at DATETIME DEFAULT NULL AFTER next_destination",
            'destination_details' => "ALTER TABLE shipment_tracking_events ADD COLUMN destination_details TEXT DEFAULT NULL AFTER description",
        ];
        foreach ($trackingColumns as $column => $sql) {
            if (!cgo_column_exists($pdo, 'shipment_tracking_events', $column)) {
                $pdo->exec($sql);
            }
        }

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL,
                email VARCHAR(191) NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                request_ip VARCHAR(64) DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_token_hash (token_hash),
                KEY idx_user_id (user_id),
                KEY idx_email (email),
                KEY idx_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {
        @error_log('Auto migration failed: ' . $e->getMessage());
    }

    $done = true;
}
