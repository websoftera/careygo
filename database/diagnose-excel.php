<?php
/**
 * Diagnose Excel file structure
 */

$excelFile = 'C:\\Users\\DELL\\Downloads\\DTDC_Pincode_TAT_Details_Master_V0_09 April 2026.xlsx';

echo "Analyzing Excel file structure...\n\n";

$zip = new ZipArchive();
$zip->open($excelFile);

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

echo "First 10 rows of data:\n";
echo "─────────────────────────────────────────────────────────────────────────────\n";

$rowNum = 0;
foreach ($xml->sheetData->row as $row) {
    $rowNum++;
    if ($rowNum > 10) break;

    $cells = [];
    foreach ($row->c as $cell) {
        $value = '';
        if (isset($cell->v)) {
            if ($cell['t'] == 's') {
                $idx = (int)$cell->v;
                $value = $strings[$idx] ?? '';
            } else {
                $value = (string)$cell->v;
            }
        }
        $cells[] = $value;
    }

    echo "Row {$rowNum}: " . implode(" | ", $cells) . "\n";
}

echo "─────────────────────────────────────────────────────────────────────────────\n";
echo "\nColumn Count: " . count($cells) . "\n";
$zip->close();
