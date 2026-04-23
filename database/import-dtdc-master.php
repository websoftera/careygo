<?php
/**
 * DTDC Master Pincode Data Import - CORRECTED COLUMN MAPPING
 * Reads: DTDC_Pincode_TAT_Details_Master_V0_09 April 2026.xlsx
 * Imports into: pincode_tat table
 *
 * Excel Structure:
 * - Column 0: pincode (6 digits)
 * - Column 1: state (state name)
 * - Column 2: district (city name - using this as primary city)
 * - Column 3: pickUpStatus
 * - Column 4: deliveryStatus
 * - Column 5: Region (Metro, Non-Metro)
 *
 * TAT Calculation:
 * - Metro cities: tat_standard=2, tat_premium=1, tat_air=1, tat_surface=3
 * - Non-Metro: tat_standard=3, tat_premium=2, tat_air=2, tat_surface=5
 *
 * Usage: php import-dtdc-master.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/helpers.php';

// ══════════════════════════════════════════════════════════════════════════════
// STEP 1: Backup existing table
// ══════════════════════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "DTDC Master Pincode Import (CORRECTED)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$backupTimestamp = date('YmdHis');
$backupTable = "pincode_tat_backup_{$backupTimestamp}";

echo "[1/5] Creating backup table '{$backupTable}'...\n";
try {
    $pdo->exec("CREATE TABLE {$backupTable} LIKE pincode_tat");
    $pdo->exec("INSERT INTO {$backupTable} SELECT * FROM pincode_tat");
    $backupCount = $pdo->query("SELECT COUNT(*) FROM {$backupTable}")->fetchColumn();
    echo "✓ Backup created with {$backupCount} records\n\n";
} catch (Exception $e) {
    echo "✗ Backup failed: " . $e->getMessage() . "\n";
    exit(1);
}

// ══════════════════════════════════════════════════════════════════════════════
// STEP 2: Read Excel file
// ══════════════════════════════════════════════════════════════════════════════

$excelFile = getenv('HOME') . '/Downloads/DTDC_Pincode_TAT_Details_Master_V0_09 April 2026.xlsx';
if (!file_exists($excelFile)) {
    // Try Windows path
    $excelFile = 'C:\\Users\\' . getenv('USERNAME') . '\\Downloads\\DTDC_Pincode_TAT_Details_Master_V0_09 April 2026.xlsx';
}
if (!file_exists($excelFile)) {
    echo "✗ Excel file not found at:\n";
    echo "  {$excelFile}\n";
    exit(1);
}

echo "[2/5] Reading Excel file...\n";
echo "File: {$excelFile}\n";
echo "Size: " . number_format(filesize($excelFile)) . " bytes\n";

$rows = readExcelFile($excelFile);
echo "✓ Parsed {$rows['count']} data rows\n\n";

if ($rows['count'] === 0) {
    echo "✗ No data found in Excel file\n";
    exit(1);
}

// ══════════════════════════════════════════════════════════════════════════════
// STEP 3: Validate data
// ══════════════════════════════════════════════════════════════════════════════

echo "[3/5] Validating data...\n";

$validation = validatePincodeData($rows['data']);
echo "✓ Pincodes: " . number_format($validation['valid']) . " valid, " . $validation['invalid'] . " invalid\n";
echo "✓ Cities: " . number_format(count(array_unique($validation['cities']))) . " unique\n";
echo "✓ States: " . number_format(count(array_unique($validation['states']))) . " unique\n";
echo "✓ Regions: " . json_encode(array_count_values($validation['regions'])) . "\n";

if ($validation['invalid'] > 0) {
    echo "⚠ {$validation['invalid']} rows skipped due to validation errors\n";
}
echo "\n";

// ══════════════════════════════════════════════════════════════════════════════
// STEP 4: Truncate and import
// ══════════════════════════════════════════════════════════════════════════════

echo "[4/5] Importing data...\n";

$imported = 0;
$duplicates = 0;
$errors = 0;

try {
    $pdo->exec("TRUNCATE TABLE pincode_tat");
    echo "✓ Cleared existing data\n";

    $stmt = $pdo->prepare("
        INSERT INTO pincode_tat (
            pincode, city, state,
            tat_standard, tat_premium, tat_air, tat_surface,
            serviceable
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($rows['data'] as $row) {
        // Skip invalid rows
        if (!preg_match('/^\d{6}$/', $row['pincode'])) continue;
        if (empty($row['city']) || empty($row['state'])) continue;

        // Determine TAT values based on region (Metro vs Non-Metro)
        $isMctro = strtolower(trim($row['region'] ?? '')) === 'metro';

        $tat_std = $isMctro ? 2 : 3;
        $tat_prem = $isMctro ? 1 : 2;
        $tat_air = $isMctro ? 1 : 2;
        $tat_surf = $isMctro ? 3 : 5;

        try {
            $stmt->execute([
                $row['pincode'],
                trim($row['city']),
                trim($row['state']),
                $tat_std,
                $tat_prem,
                $tat_air,
                $tat_surf,
                1 // serviceable = yes
            ]);
            $imported++;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $duplicates++;
            } else {
                $errors++;
                error_log("Import error for pincode {$row['pincode']}: " . $e->getMessage());
            }
        }
    }

    echo "✓ Imported: {$imported} pincodes\n";
    if ($duplicates > 0) echo "⚠ Skipped: {$duplicates} duplicates\n";
    if ($errors > 0) echo "✗ Errors: {$errors}\n";
    echo "\n";

} catch (Exception $e) {
    echo "✗ Import failed: " . $e->getMessage() . "\n";
    echo "⚠ Rolling back to backup...\n";

    try {
        $pdo->exec("TRUNCATE TABLE pincode_tat");
        $pdo->exec("INSERT INTO pincode_tat SELECT * FROM {$backupTable}");
        echo "✓ Rolled back successfully\n";
    } catch (Exception $rb) {
        echo "✗ Rollback failed: " . $rb->getMessage() . "\n";
    }
    exit(1);
}

// ══════════════════════════════════════════════════════════════════════════════
// STEP 5: Verify import
// ══════════════════════════════════════════════════════════════════════════════

echo "[5/5] Verifying import...\n";

$finalCount = $pdo->query("SELECT COUNT(*) FROM pincode_tat")->fetchColumn();
echo "✓ Final record count: " . number_format($finalCount) . "\n";

$stateCount = $pdo->query("SELECT COUNT(DISTINCT state) FROM pincode_tat")->fetchColumn();
echo "✓ States covered: {$stateCount}\n";

$cityCount = $pdo->query("SELECT COUNT(DISTINCT city) FROM pincode_tat")->fetchColumn();
echo "✓ Cities covered: {$cityCount}\n";

// Verify key cities
$keyCities = ['DELHI', 'MUMBAI', 'BANGALORE', 'HYDERABAD', 'CHENNAI', 'KOLKATA', 'PUNE'];
echo "\n✓ Key city pincodes:\n";
foreach ($keyCities as $city) {
    $count = $pdo->query("SELECT COUNT(*) FROM pincode_tat WHERE UPPER(city) LIKE UPPER('%{$city}%')")->fetchColumn();
    echo "  • " . str_pad($city, 12) . ": {$count} pincodes\n";
}

// Verify TAT values
echo "\n✓ TAT Distribution:\n";
$tatStats = $pdo->query("
    SELECT
        COUNT(*) as count,
        tat_standard,
        tat_premium,
        tat_air,
        tat_surface
    FROM pincode_tat
    GROUP BY tat_standard, tat_premium, tat_air, tat_surface
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($tatStats as $stat) {
    $region = ($stat['tat_standard'] == 2) ? 'Metro' : 'Non-Metro';
    echo "  • {$region} ({$stat['count']} pincodes): Std={$stat['tat_standard']}, Prem={$stat['tat_premium']}, Air={$stat['tat_air']}, Surf={$stat['tat_surface']}\n";
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✓ Import completed successfully!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// ══════════════════════════════════════════════════════════════════════════════
// Helper Functions
// ══════════════════════════════════════════════════════════════════════════════

function readExcelFile($filePath) {
    $data = [];

    // Open as ZIP
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new Exception("Cannot open Excel file");
    }

    // Read shared strings
    $strings = [];
    if ($zip->locateName('xl/sharedStrings.xml') !== false) {
        $xmlContent = $zip->getFromName('xl/sharedStrings.xml');
        $xml = simplexml_load_string($xmlContent);
        foreach ($xml->si as $si) {
            $strings[] = (string)$si->t;
        }
    }

    // Read worksheet
    $xmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
    $xml = simplexml_load_string($xmlContent);

    $headerRow = true;
    $headers = [];
    $rowCount = 0;

    foreach ($xml->sheetData->row as $row) {
        $cells = [];
        $cellIndex = 0;

        foreach ($row->c as $cell) {
            $value = '';

            if (isset($cell->v)) {
                if ($cell['t'] == 's') {
                    // Shared string
                    $idx = (int)$cell->v;
                    $value = $strings[$idx] ?? '';
                } else {
                    $value = (string)$cell->v;
                }
            }

            $cells[$cellIndex] = $value;
            $cellIndex++;
        }

        if ($headerRow) {
            $headers = $cells;
            $headerRow = false;
            continue;
        }

        if (empty($cells[0])) continue; // Skip empty rows

        // Map columns (corrected)
        $row = [
            'pincode' => $cells[0] ?? '',        // Column 0
            'state' => $cells[1] ?? '',          // Column 1
            'city' => $cells[2] ?? '',           // Column 2 (district - use as city)
            'pickup_status' => $cells[3] ?? '',  // Column 3
            'delivery_status' => $cells[4] ?? '', // Column 4
            'region' => $cells[5] ?? '',         // Column 5
        ];

        $data[] = $row;
        $rowCount++;
    }

    $zip->close();

    return [
        'count' => $rowCount,
        'data' => $data
    ];
}

function validatePincodeData($data) {
    $valid = 0;
    $invalid = 0;
    $cities = [];
    $states = [];
    $regions = [];

    foreach ($data as $row) {
        if (preg_match('/^\d{6}$/', $row['pincode'])) {
            $valid++;
            if (!empty($row['city'])) $cities[] = $row['city'];
            if (!empty($row['state'])) $states[] = $row['state'];
            if (!empty($row['region'])) $regions[] = $row['region'];
        } else {
            $invalid++;
        }
    }

    return [
        'valid' => $valid,
        'invalid' => $invalid,
        'cities' => $cities,
        'states' => $states,
        'regions' => $regions
    ];
}
