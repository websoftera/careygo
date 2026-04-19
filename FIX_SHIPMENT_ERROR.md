# Fix: Shipment Booking Returns 500 Error

## Problem
When clicking "Confirm Booking" on Step 6 (Payment), the system returns **HTTP 500 Internal Server Error** from `/api/shipments.php`.

## Quick Diagnosis

### Step 1: Run Comprehensive Diagnostic

Visit this URL (admin only):
```
https://careygo.everythingb2c.in/debug_shipment.php
```

**What to look for:**
- All items should show `"status": "OK"`
- Any item showing `"status": "FAILED"` indicates the problem

### Step 2: Run Test Insert

Visit this URL to test actual booking creation:
```
https://careygo.everythingb2c.in/debug_shipment.php?test=1
```

**Look at the `"test_booking"` section:**
- If `"status": "OK"` → Database insert works fine
- If `"status": "FAILED"` → Shows exact error message

---

## Common Issues & Fixes

### ❌ Issue 1: Missing Database Tables

**Diagnostic shows:**
```json
"tables": {
  "status": "FAILED",
  "missing_tables": ["shipments", "shipment_tracking_events"]
}
```

**Fix:**
1. Run `/setup.php?key=careygo_setup_2026` to apply migrations
2. This creates missing tables

---

### ❌ Issue 2: Missing Table Columns

**Diagnostic shows:**
```json
"shipments_columns": {
  "status": "FAILED",
  "missing_columns": ["dtdc_awb", "discount_pct"]
}
```

**Fix:**
1. Run `/setup.php?key=careygo_setup_2026`
2. This adds missing columns via ALTER TABLE

---

### ❌ Issue 3: No Pricing Data

**Diagnostic shows:**
```json
"pricing_data": {
  "status": "OK",
  "slab_count": 0
}
```

**Fix:**
1. Visit `/import_pricing.php`
2. Click "Import Pricing Data"
3. Should see count > 0 after import

---

### ❌ Issue 4: No Pincode Data

**Diagnostic shows:**
```json
"pincode_data": {
  "status": "OK",
  "pincode_count": 0
}
```

**Fix:**
1. Run `/setup.php?key=careygo_setup_2026&pincodes=1`
2. This imports all 15,000+ pincodes

---

### ❌ Issue 5: Test Insert Fails

**Diagnostic shows:**
```json
"test_booking": {
  "status": "FAILED",
  "error": "Column 'discount_pct' doesn't have a default value",
  "code": 1364
}
```

**This is a detailed error message.** Common causes:

| Error | Cause | Fix |
|-------|-------|-----|
| `doesn't have a default value` | Missing column or wrong NOT NULL | Run setup.php migrations |
| `Unknown column` | Column doesn't exist in table | Run setup.php migrations |
| `Foreign key constraint fails` | User doesn't exist or wrong customer_id | Check users table has valid users |
| `Duplicate entry` | Tracking number already exists | Retry (generates different number) |
| `Out of range` | Number too large for field type | Check field data types |

---

## Step-by-Step Fix Process

### 1️⃣ Run Full Diagnostic
```
URL: /debug_shipment.php
Expected: All checks show "OK"
```

### 2️⃣ Check Database Migrations
```
URL: /setup.php?key=careygo_setup_2026
Action: Run all migrations
Expected: No errors shown
```

### 3️⃣ Import Pincodes
```
URL: /setup.php?key=careygo_setup_2026&pincodes=1
Action: Import pincodes (takes 10-20 seconds)
Expected: Completes successfully
```

### 4️⃣ Import Pricing
```
URL: /import_pricing.php
Action: Click "Import Pricing Data"
Expected: Shows summary with pricing inserted
```

### 5️⃣ Run Test Insert
```
URL: /debug_shipment.php?test=1
Expected: "test_booking": { "status": "OK" }
```

### 6️⃣ Test Real Booking
```
Action: Fill form and click "Confirm Booking"
Expected: Booking succeeds, shows tracking number
```

---

## If Still Failing

### Get Error Details

1. **Enable Debug Mode** in `/api/shipments.php`:
   ```php
   define('DEBUG_MODE', true); // Line 19
   ```

2. **Try booking again** - Error response will show actual error

3. **Check PHP Error Log**:
   - File: `/var/log/php_errors.log` or `/tmp/php_errors.log`
   - Look for lines containing `SHIPMENT_ERROR`

### Example Error Log Entry:
```
SHIPMENT_ERROR: {"timestamp":"2026-04-18 14:30:45","message":"SQLSTATE[42S22]: Column not found: 1054 Unknown column 'dtdc_awb'","code":0,"file":"/home/user/api/shipments.php","line":142}
```

This tells you **column `dtdc_awb` is missing** → Run `setup.php`

---

## Complete Checklist

Use this to verify everything is set up:

- [ ] Run `/debug_shipment.php` → All checks show "OK"
- [ ] Run `/debug_shipment.php?test=1` → test_booking shows "OK"
- [ ] Run `/import_pricing.php` → Shows pricing imported
- [ ] Run `/setup.php` → All schema migrations done
- [ ] Test booking → Works without 500 error
- [ ] Check `/api/shipments.php` line 19 → DEBUG_MODE = false (after done)

---

## Need More Help?

If still stuck after these steps:

1. Run `/debug_shipment.php` and share the JSON output
2. Run `/debug_shipment.php?test=1` and share the test_booking section
3. Check PHP error log and share SHIPMENT_ERROR entries
4. Visit `/setup.php` and check for any error messages

The diagnostic scripts will pinpoint exactly what's wrong! 🔍
