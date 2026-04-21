<?php
// Simple script to deliver a sample CSV for pincode import
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=sample_pincodes.csv');

$output = fopen('php://output', 'w');

// CSV headers expected by the import script
fputcsv($output, ['pincode', 'city', 'state', 'zone', 'tat_standard', 'tat_premium', 'tat_air', 'tat_surface', 'serviceable']);

// Output some sample rows
fputcsv($output, ['411001', 'Pune', 'Maharashtra', 'West', '2', '1', '1', '4', '1']);
fputcsv($output, ['110001', 'New Delhi', 'Delhi', 'North', '3', '1', '1', '4', '1']);
fputcsv($output, ['700001', 'Kolkata', 'West Bengal', 'East', '4', '2', '2', '5', '1']);

fclose($output);
exit;
