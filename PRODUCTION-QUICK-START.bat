@echo off
REM PRODUCTION DEPLOYMENT QUICK START (Windows)
REM Run this script on your production Windows server to deploy all changes

setlocal enabledelayedexpansion

echo.
echo ================================================================================
echo Careygo Production Deployment - Windows Quick Start
echo ================================================================================
echo.

REM Colors/Formatting
set GREEN=[92m
set YELLOW=[93m
set RED=[91m
set RESET=[0m

REM Check if PHP is available
php -v >nul 2>&1
if errorlevel 1 (
    echo.
    echo %RED%ERROR: PHP not found. Please install PHP or ensure it's in PATH.%RESET%
    echo.
    pause
    exit /b 1
)

REM Display PHP version
echo %GREEN%PHP is installed:%RESET%
php -v | findstr /R "PHP [0-9]"
echo.

REM Get the script directory
set SCRIPT_DIR=%~dp0
cd /d "%SCRIPT_DIR%"

echo Working directory: %SCRIPT_DIR%
echo.

REM ============================================================================
REM PHASE 1: DTDC Master Pincode Import
REM ============================================================================

echo.
echo ================================================================================
echo PHASE 1: DTDC Master Pincode Data Import
echo ================================================================================
echo.

if exist "database\import-dtdc-master.php" (
    echo %YELLOW%Running pincode import script...%RESET%
    php database\import-dtdc-master.php

    if errorlevel 1 (
        echo.
        echo %RED%ERROR: Phase 1 failed%RESET%
        echo Aborting deployment. Please check the errors above.
        echo.
        pause
        exit /b 1
    )

    echo.
    echo %GREEN%Phase 1 completed successfully%RESET%
) else (
    echo %RED%ERROR: import-dtdc-master.php not found%RESET%
    echo.
    pause
    exit /b 1
)

echo.

REM ============================================================================
REM PHASE 2: Code Changes (Manual)
REM ============================================================================

echo.
echo ================================================================================
echo PHASE 2: Code Changes
echo ================================================================================
echo.

echo %YELLOW%NOTE: Code changes require manual updates.%RESET%
echo.
echo Files that need to be updated:
echo   1. config\database.php
echo   2. api\pricing.php
echo   3. api\shipments.php
echo.
echo Please refer to PRODUCTION-DEPLOYMENT.md for the specific changes needed.
echo.
pause

REM Check if files exist
set MISSING_FILES=0

if not exist "config\database.php" (
    echo %RED%ERROR: config\database.php not found%RESET%
    set MISSING_FILES=1
)

if not exist "api\pricing.php" (
    echo %RED%ERROR: api\pricing.php not found%RESET%
    set MISSING_FILES=1
)

if not exist "api\shipments.php" (
    echo %RED%ERROR: api\shipments.php not found%RESET%
    set MISSING_FILES=1
)

if %MISSING_FILES% equ 1 (
    echo.
    pause
    exit /b 1
)

echo %GREEN%All files present%RESET%
echo.

REM ============================================================================
REM PHASE 3: Weight Slab Reorganization
REM ============================================================================

echo.
echo ================================================================================
echo PHASE 3: Weight Slab Reorganization
echo ================================================================================
echo.

if exist "database\reorganize-slabs.php" (
    echo %YELLOW%Running weight slab reorganization...%RESET%
    php database\reorganize-slabs.php

    if errorlevel 1 (
        echo.
        echo %RED%ERROR: Phase 3 failed%RESET%
        echo Please check the errors above and refer to rollback procedure.
        echo.
        pause
        exit /b 1
    )

    echo.
    echo %GREEN%Phase 3 completed successfully%RESET%
) else (
    echo %RED%ERROR: reorganize-slabs.php not found%RESET%
    echo.
    pause
    exit /b 1
)

echo.

REM ============================================================================
REM Verification
REM ============================================================================

echo.
echo ================================================================================
echo VERIFICATION
echo ================================================================================
echo.

echo %YELLOW%Running verification checks...%RESET%
echo.

php -r "
require_once 'config/database.php';

\$checks = [];

// Check pincode count
\$stmt = \$pdo->query('SELECT COUNT(*) FROM pincode_tat');
\$pincodeCount = \$stmt->fetchColumn();
\$checks['Pincodes Imported'] = [
    'value' => number_format(\$pincodeCount),
    'expected' => '>= 15,000',
    'passed' => \$pincodeCount >= 15000
];

// Check weight slabs
\$stmt = \$pdo->query('SELECT COUNT(*) FROM pricing_slabs');
\$slabCount = \$stmt->fetchColumn();
\$checks['Weight Slabs'] = [
    'value' => \$slabCount,
    'expected' => '24',
    'passed' => \$slabCount === 24
];

// Check slabs per service
\$stmt = \$pdo->query('SELECT service_type, COUNT(*) FROM pricing_slabs GROUP BY service_type');
\$slabsPerService = \$stmt->fetchAll(PDO::FETCH_KEY_PAIR);
\$allHaveSix = true;
foreach (\$slabsPerService as \$count) {
    if (\$count !== 6) \$allHaveSix = false;
}
\$checks['Slabs Per Service'] = [
    'value' => implode(', ', \$slabsPerService),
    'expected' => 'All = 6',
    'passed' => \$allHaveSix
];

// Check metro vs non-metro distribution
\$stmt = \$pdo->query('SELECT COUNT(*) FROM pincode_tat WHERE tat_standard = 2');
\$metroCount = \$stmt->fetchColumn();
\$checks['Metro Pincodes'] = [
    'value' => number_format(\$metroCount),
    'expected' => '> 600',
    'passed' => \$metroCount > 600
];

// Print results
echo 'Verification Results:' . PHP_EOL;
echo str_repeat('-', 65) . PHP_EOL;

\$allPassed = true;
foreach (\$checks as \$name => \$check) {
    \$status = \$check['passed'] ? '[OK]' : '[FAIL]';
    echo \$status . ' ' . \$name . ': ' . \$check['value'] . ' (expected: ' . \$check['expected'] . ')' . PHP_EOL;
    if (!\$check['passed']) \$allPassed = false;
}

echo str_repeat('-', 65) . PHP_EOL . PHP_EOL;

if (\$allPassed) {
    echo 'SUCCESS: All verification checks PASSED!' . PHP_EOL;
    echo 'Deployment is ready for production use.' . PHP_EOL;
} else {
    echo 'FAILURE: Some verification checks FAILED.' . PHP_EOL;
    echo 'Please review the errors and rollback if necessary.' . PHP_EOL;
}
"

echo.
echo ================================================================================
echo DEPLOYMENT COMPLETE
echo ================================================================================
echo.
echo Next Steps:
echo 1. Apply the code changes from PRODUCTION-DEPLOYMENT.md manually
echo 2. Test the pricing API with various weights
echo 3. Monitor application logs for 24 hours
echo 4. Confirm with support team
echo.
echo For detailed information, see: PRODUCTION-DEPLOYMENT.md
echo.

pause
