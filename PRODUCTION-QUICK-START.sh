#!/bin/bash

# PRODUCTION DEPLOYMENT QUICK START
# Run this script on your production server to deploy all changes
# Usage: bash PRODUCTION-QUICK-START.sh

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Careygo Production Deployment - Quick Start"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo -e "${RED}✗ PHP not found. Please install PHP or ensure it's in PATH.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ PHP found$(php -v | head -1)${NC}"
echo ""

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "Working directory: $SCRIPT_DIR"
echo ""

# ══════════════════════════════════════════════════════════════════════════════
# PHASE 1: DTDC Master Pincode Import
# ══════════════════════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "PHASE 1: DTDC Master Pincode Data Import"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ -f "database/import-dtdc-master.php" ]; then
    echo -e "${YELLOW}▸ Running pincode import script...${NC}"
    php database/import-dtdc-master.php
    IMPORT_RESULT=$?

    if [ $IMPORT_RESULT -eq 0 ]; then
        echo -e "${GREEN}✓ Phase 1 completed successfully${NC}"
    else
        echo -e "${RED}✗ Phase 1 failed with exit code $IMPORT_RESULT${NC}"
        echo "Aborting deployment. Please check the errors above."
        exit 1
    fi
else
    echo -e "${RED}✗ import-dtdc-master.php not found${NC}"
    exit 1
fi

echo ""

# ══════════════════════════════════════════════════════════════════════════════
# PHASE 2: Code Changes (Automated)
# ══════════════════════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "PHASE 2: Code Changes"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo -e "${YELLOW}▸ Applying code changes...${NC}"

# Check if files exist
FILES_TO_UPDATE=(
    "config/database.php"
    "api/pricing.php"
    "api/shipments.php"
)

for file in "${FILES_TO_UPDATE[@]}"; do
    if [ ! -f "$file" ]; then
        echo -e "${RED}✗ File not found: $file${NC}"
        exit 1
    fi
done

echo -e "${GREEN}✓ All files present and ready for update${NC}"
echo ""
echo -e "${YELLOW}NOTE: Code changes require manual updates.${NC}"
echo "Please refer to PRODUCTION-DEPLOYMENT.md for the specific changes needed."
echo ""

# ══════════════════════════════════════════════════════════════════════════════
# PHASE 3: Weight Slab Reorganization
# ══════════════════════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "PHASE 3: Weight Slab Reorganization"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ -f "database/reorganize-slabs.php" ]; then
    echo -e "${YELLOW}▸ Running weight slab reorganization...${NC}"
    php database/reorganize-slabs.php
    SLAB_RESULT=$?

    if [ $SLAB_RESULT -eq 0 ]; then
        echo -e "${GREEN}✓ Phase 3 completed successfully${NC}"
    else
        echo -e "${RED}✗ Phase 3 failed with exit code $SLAB_RESULT${NC}"
        echo "Please check the errors above and refer to rollback procedure."
        exit 1
    fi
else
    echo -e "${RED}✗ reorganize-slabs.php not found${NC}"
    exit 1
fi

echo ""

# ══════════════════════════════════════════════════════════════════════════════
# Verification
# ══════════════════════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "VERIFICATION"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Create verification script
cat > /tmp/verify-deployment.php << 'VERIFY_SCRIPT'
<?php
require_once 'config/database.php';

$checks = [];

// Check pincode count
$stmt = $pdo->query("SELECT COUNT(*) FROM pincode_tat");
$pincodeCount = $stmt->fetchColumn();
$checks['Pincodes Imported'] = [
    'value' => number_format($pincodeCount),
    'expected' => '>= 15,000',
    'passed' => $pincodeCount >= 15000
];

// Check weight slabs
$stmt = $pdo->query("SELECT COUNT(*) FROM pricing_slabs");
$slabCount = $stmt->fetchColumn();
$checks['Weight Slabs'] = [
    'value' => $slabCount,
    'expected' => '24',
    'passed' => $slabCount === 24
];

// Check slabs per service
$stmt = $pdo->query("SELECT service_type, COUNT(*) FROM pricing_slabs GROUP BY service_type");
$slabsPerService = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$allHaveSix = true;
foreach ($slabsPerService as $count) {
    if ($count !== 6) $allHaveSix = false;
}
$checks['Slabs Per Service'] = [
    'value' => implode(', ', $slabsPerService),
    'expected' => 'All = 6',
    'passed' => $allHaveSix
];

// Check metro vs non-metro distribution
$stmt = $pdo->query("SELECT COUNT(*) FROM pincode_tat WHERE tat_standard = 2");
$metroCount = $stmt->fetchColumn();
$checks['Metro Pincodes'] = [
    'value' => number_format($metroCount),
    'expected' => '> 600',
    'passed' => $metroCount > 600
];

// Print results
echo "Verification Results:\n";
echo "─────────────────────────────────────────────────────────────────\n";

$allPassed = true;
foreach ($checks as $name => $check) {
    $status = $check['passed'] ? '✓' : '✗';
    echo "{$status} {$name}: {$check['value']} (expected: {$check['expected']})\n";
    if (!$check['passed']) $allPassed = false;
}

echo "─────────────────────────────────────────────────────────────────\n";
if ($allPassed) {
    echo "\n✓ All verification checks PASSED!\n";
    echo "Deployment successful. System is production-ready.\n";
} else {
    echo "\n✗ Some verification checks FAILED.\n";
    echo "Please review the errors and rollback if necessary.\n";
}
VERIFY_SCRIPT

php /tmp/verify-deployment.php

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "DEPLOYMENT COMPLETE"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Next Steps:"
echo "1. Apply the code changes from PRODUCTION-DEPLOYMENT.md manually"
echo "2. Test the pricing API with various weights"
echo "3. Monitor application logs for 24 hours"
echo "4. Confirm with support team"
echo ""
echo "For detailed information, see: PRODUCTION-DEPLOYMENT.md"
echo ""
