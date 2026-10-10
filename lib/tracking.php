<?php
/**
 * Tracking event schema helpers.
 */

function tracking_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    try {
        $shipmentCols = $pdo->query("SHOW COLUMNS FROM shipments")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('dtdc_awb', $shipmentCols, true)) {
            $pdo->exec("ALTER TABLE shipments ADD COLUMN dtdc_awb VARCHAR(50) DEFAULT NULL AFTER tracking_no");
        }
    } catch (Exception $e) {
        @error_log('Tracking shipment column check failed: ' . $e->getMessage());
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

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM shipment_tracking_events")->fetchAll(PDO::FETCH_COLUMN);
        $additions = [
            'next_destination' => "ALTER TABLE shipment_tracking_events ADD COLUMN next_destination VARCHAR(200) DEFAULT NULL AFTER location",
            'expected_at' => "ALTER TABLE shipment_tracking_events ADD COLUMN expected_at DATETIME DEFAULT NULL AFTER next_destination",
            'destination_details' => "ALTER TABLE shipment_tracking_events ADD COLUMN destination_details TEXT DEFAULT NULL AFTER description",
        ];
        foreach ($additions as $column => $sql) {
            if (!in_array($column, $cols, true)) {
                $pdo->exec($sql);
            }
        }
    } catch (Exception $e) {
        @error_log('Tracking event column check failed: ' . $e->getMessage());
    }

    $done = true;
}

function tracking_status_to_shipment_status(string $status): ?string
{
    $normalized = strtolower(trim($status));
    $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);
    $normalized = trim($normalized, '_');

    $map = [
        'pickup_requested' => 'booked',
        'booked' => 'booked',
        'picked_up' => 'picked_up',
        'pickup_done' => 'picked_up',
        'in_transit' => 'in_transit',
        'on_hold' => 'in_transit',
        'damage' => 'in_transit',
        'reached_hub' => 'in_transit',
        'departed_hub' => 'in_transit',
        'arrived_at_destination_hub' => 'in_transit',
        'out_for_delivery' => 'out_for_delivery',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
        'return_to_origin' => 'cancelled',
        'exception' => 'in_transit',
        'returned' => 'cancelled',
    ];

    return $map[$normalized] ?? null;
}
