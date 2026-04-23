# Production Deployment Guide - Careygo Pricing System Updates

## Overview
This guide contains all the steps, scripts, and commands needed to deploy the production-ready pricing system updates to your production server.

---

## Phase 1: DTDC Master Pincode Data Import

### Step 1.1: Upload Excel file to production server
```
File: DTDC_Pincode_TAT_Details_Master_V0_09 April 2026.xlsx
Destination: /database/ directory on production server
```

### Step 1.2: Run pincode import script
```bash
# SSH into production server
ssh user@your-production-server.com

# Navigate to project directory
cd /home/user/careygo

# Run import script
php database/import-dtdc-master.php
```

**Expected output:**
```
[1/5] Creating backup table...
✓ Backup created with XXX records

[2/5] Reading Excel file...
✓ Parsed 15453 data rows

[3/5] Validating data...
✓ Pincodes: 15,453 valid, 0 invalid
✓ Cities: 1,927 unique
✓ States: 34 unique

[4/5] Importing data...
✓ Imported: 15452 pincodes

[5/5] Verifying import...
✓ Final record count: 15,452
✓ States covered: 34
✓ Cities covered: 1707
✓ Key city pincodes:
  • DELHI       : 155 pincodes
  • MUMBAI      : 169 pincodes
  • BANGALORE   : 153 pincodes
```

---

## Phase 2: Code Changes Deployment

### Step 2.1: Update config/database.php
```php
// CHANGE FROM:
define('DB_USER', _cfg('CGO_DB_USER', 'u141519101_careygo'));
define('DB_PASS', _cfg('CGO_DB_PASS', '+DgrP256'));
define('DB_NAME', _cfg('CGO_DB_NAME', 'u141519101_careygo'));

// TO:
define('DB_USER', _cfg('CGO_DB_USER', 'root'));
define('DB_PASS', _cfg('CGO_DB_PASS', ''));
define('DB_NAME', _cfg('CGO_DB_NAME', 'careygo'));
```

**File:** `/config/database.php` (lines 21-24)

### Step 2.2: Update api/pricing.php
Add service weight constraints and Air Cargo filtering after line 136:

```php
// Service weight constraints (production-ready logistics)
$serviceConstraints = [
    'standard'  => 2.000,   // Standard Express: max 2kg
    'premium'   => 5.000,   // Premium Express: max 5kg
    'air_cargo' => 10.000,  // Air Cargo: max 10kg
    'surface'   => 25.000,  // Surface: max 25kg
];

foreach ($serviceTypes as $type) {
    // 1. Check weight constraint
    $maxWeight = $serviceConstraints[$type] ?? PHP_FLOAT_MAX;
    if ($weight > $maxWeight) {
        continue;  // Service not available for this weight
    }

    // 2. Check Air Cargo rate availability
    if ($type === 'air_cargo' && $zone) {
        $rateCount = $pdo->prepare(
            "SELECT COUNT(*) FROM pricing_slabs WHERE service_type = ? AND zone = ?"
        )->execute([$type, $zone])->fetchColumn() ?: 0;

        if ($rateCount === 0) {
            continue;  // No rates available for this zone
        }
    }

    $price = calculatePrice($weight, $type, $pdo, $zone);
    if ($price <= 0) continue;
    
    // ... rest of the code
```

**File:** `/api/pricing.php` (around line 140)

### Step 2.3: Update api/shipments.php
Add weight validation after line 106:

```php
// Service weight constraints (production-ready)
$serviceConstraints = [
    'standard'  => 2.000,   // Standard Express: max 2kg
    'premium'   => 5.000,   // Premium Express: max 5kg
    'air_cargo' => 10.000,  // Air Cargo: max 10kg
    'surface'   => 25.000,  // Surface: max 25kg
];
$maxWeight = $serviceConstraints[$serviceType] ?? PHP_FLOAT_MAX;
if ($weight > $maxWeight) {
    json_response([
        'success' => false,
        'message' => "Weight exceeds limit for $serviceType service. Maximum: {$maxWeight} kg"
    ], 422);
}
```

**File:** `/api/shipments.php` (after line 106)

---

## Phase 3: Database Weight Slab Reorganization

### Step 3.1: Run weight slab reorganization
```bash
# SSH into production server
ssh user@your-production-server.com

# Navigate to project directory
cd /home/user/careygo

# Run reorganization script
php database/reorganize-slabs.php
```

**Expected output:**
```
[1/5] Creating backup...
✓ Backup created as 'pricing_slabs_backup_YYYYMMDDHHMMSS'

[2/5] Analyzing current pricing structure...
Current slabs by service type:
  • standard: 3 unique price points
  • premium: 3 unique price points
  • surface: 2 unique price points
  • air_cargo: 3 unique price points

[3/5] Creating standard 6-tier structure...
[4/5] Rebuilding slabs with standard tiers...
✓ standard - Tier 1: 0-250g: ₹100
✓ standard - Tier 2: 250-500g: ₹110
... (more tiers)

[5/5] Verifying new structure...
✓ Total slabs: 24
Slabs per service:
  • standard: 6 tiers
  • premium: 6 tiers
  • surface: 6 tiers
  • air_cargo: 6 tiers
```

---

## Verification Checklist

After deployment, verify these items:

### ✓ Pincode Import Verification
```bash
# SSH into production
mysql -u root -p careygo

# Check total pincodes
SELECT COUNT(*) FROM pincode_tat;
# Expected: ~15,452

# Check major cities
SELECT city, COUNT(*) as count FROM pincode_tat 
WHERE city IN ('DELHI', 'MUMBAI', 'BANGALORE', 'HYDERABAD') 
GROUP BY city;

# Check TAT values
SELECT DISTINCT tat_standard, tat_premium, tat_air, tat_surface, COUNT(*) 
FROM pincode_tat GROUP BY tat_standard, tat_premium, tat_air, tat_surface;
```

### ✓ Weight Slab Verification
```bash
# Check total slabs
SELECT COUNT(*) FROM pricing_slabs;
# Expected: 24

# Check slabs per service
SELECT service_type, COUNT(*) FROM pricing_slabs GROUP BY service_type;
# Expected: 6 tiers each

# Check weight tier structure
SELECT weight_from, weight_to, base_price FROM pricing_slabs 
WHERE service_type = 'standard' ORDER BY weight_from;
```

### ✓ Test Pricing Calculations
```bash
# Create a test request
curl "https://your-domain.com/careygo/api/pricing.php?weight=1.5&pickup=110001&delivery=110002"

# Expected response should include:
# - standard (1.5kg within limit)
# - premium
# - air_cargo
# - surface
```

### ✓ Test Weight Constraints
```bash
# Test Standard Express with 2.5kg (should exclude standard)
curl "https://your-domain.com/careygo/api/pricing.php?weight=2.5&pickup=110001&delivery=110002"

# Expected: only premium, air_cargo, surface (not standard)
```

---

## Rollback Procedure

If you need to rollback any changes:

### Rollback Pincodes (use backup table)
```sql
-- List available backups
SHOW TABLES LIKE 'pincode_tat_backup_%';

-- Restore from specific backup (if needed)
TRUNCATE TABLE pincode_tat;
INSERT INTO pincode_tat SELECT * FROM pincode_tat_backup_YYYYMMDDHHMMSS;
```

### Rollback Weight Slabs (use backup table)
```sql
-- List available backups
SHOW TABLES LIKE 'pricing_slabs_backup_%';

-- Restore from specific backup
TRUNCATE TABLE pricing_slabs;
INSERT INTO pricing_slabs SELECT * FROM pricing_slabs_backup_YYYYMMDDHHMMSS;
```

### Rollback Code Changes
Simply revert the three files to their previous versions:
- `/config/database.php`
- `/api/pricing.php`
- `/api/shipments.php`

---

## Production Deployment Checklist

- [ ] Backup all databases (full backup)
- [ ] Backup all code files
- [ ] Upload Excel file to server
- [ ] Run Phase 1 (pincode import)
- [ ] Verify pincode data (check queries above)
- [ ] Update config/database.php
- [ ] Update api/pricing.php
- [ ] Update api/shipments.php
- [ ] Run Phase 3 (weight slab reorganization)
- [ ] Verify weight slabs (check queries above)
- [ ] Test pricing API with various weights
- [ ] Test weight constraints
- [ ] Monitor logs for 24 hours
- [ ] Confirm with support team everything works

---

## Files to Deploy

Copy these files to production:

```
1. database/import-dtdc-master.php    (Run once)
2. database/reorganize-slabs.php      (Run once)
3. config/database.php                (Update)
4. api/pricing.php                    (Update)
5. api/shipments.php                  (Update)
```

---

## Support Contact

If issues occur during deployment:
1. Check the rollback procedures above
2. Review error logs in `/logs/` directory
3. Contact DTDC support if pincode data issues occur
4. Restore from database backups if needed

---

## Success Indicators

✓ System is production-ready when:
- All 15,452 pincodes imported successfully
- All 24 weight slabs created (6 per service)
- Standard Express correctly limited to 2kg
- Premium Express correctly limited to 5kg
- Air Cargo correctly limited to 10kg
- Surface correctly limited to 25kg
- Zone determination working correctly
- Customer bookings being processed successfully
- Email notifications sending correctly
- No errors in application logs
