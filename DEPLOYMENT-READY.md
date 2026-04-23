# ✅ READY FOR PRODUCTION DEPLOYMENT

## Files to Upload to Production

All fixes are complete and ready. Upload these files to your production server:

---

## 📦 CRITICAL FILES (MUST UPLOAD)

### 1. `/api/pricing.php` 
**REQUIRED** - Contains Air Cargo filtering logic
- Lines 156-173: Air Cargo filtering (only show if zone-specific rates exist)
- Lines 141-147: Service weight constraints
- Status: ✅ Ready to deploy

### 2. `/api/shipments.php`
**REQUIRED** - Contains weight validation
- Lines 108-121: Service weight validation on booking
- Status: ✅ Ready to deploy

### 3. `/config/database.php`
**ALREADY CORRECT** - Database config fallback fixed
- Lines 16-19: Correct localhost defaults
- Status: ✅ No changes needed

---

## 🗄️ SETUP SCRIPTS (UPLOAD & RUN ONCE)

### 4. `/database/import-dtdc-master.php`
**Run once via browser setup script**
- Imports 15,452 pincodes
- Status: ✅ Ready

### 5. `/database/reorganize-slabs.php`
**Run once via browser setup script**
- Creates 24 weight slabs (6 per service)
- Status: ✅ Ready

### 6. `/setup-production.php`
**Run once, then DELETE**
- Automatically runs both database scripts above
- Access: `https://your-domain.com/careygo/setup-production.php?run=yes`
- Status: ✅ Ready

---

## 🚀 DEPLOYMENT STEPS

### Step 1: Upload Core Files
Upload these 3 files via FTP/File Manager:
```
✓ api/pricing.php           → /public_html/careygo/api/
✓ api/shipments.php         → /public_html/careygo/api/
✓ config/database.php       → /public_html/careygo/config/
```

### Step 2: Run Setup Script
1. Upload `setup-production.php` → `/public_html/careygo/`
2. Open: `https://your-domain.com/careygo/setup-production.php?run=yes`
3. Wait for "SETUP COMPLETED SUCCESSFULLY"
4. **DELETE** `setup-production.php` immediately

### Step 3: Test
```
Test URL: https://your-domain.com/careygo/api/pricing.php?weight=3&pickup=110001&delivery=122105

Expected Response (3 services, NO Air Cargo):
{
  "success": true,
  "services": [
    {"type": "premium", "price": 275, "tat": 2},
    {"type": "air_cargo", "price": ???},  ← Should NOT be here
    {"type": "surface", "price": 255, "tat": 5}
  ]
}
```

### Step 4: Verify
- ✅ Air Cargo NOT showing for weight 3kg
- ✅ Premium, Surface showing correctly
- ✅ Pincodes imported (15,452+)
- ✅ Weight slabs created (24 total)
- ✅ No errors in logs

---

## 🔍 WHAT'S BEING DEPLOYED

### Code Changes
1. **Air Cargo Filtering** (pricing.php)
   - Hidden unless admin adds zone-specific rates
   - Currently all rates are GLOBAL, so Air Cargo is hidden

2. **Weight Constraints** (pricing.php + shipments.php)
   - Standard: Max 2kg
   - Premium: Max 5kg
   - Air Cargo: Max 10kg
   - Surface: Max 25kg

3. **Weight Slab System** (database)
   - 6 tiers per service
   - Standard progression: 0-0.25, 0.25-0.5, 0.5-1, 1-2, 2-5, 5+

4. **Pincode Database** (database)
   - 15,452 pincodes imported
   - 34 states, 1,927 cities
   - TAT values per service type

---

## 📋 DEPLOYMENT CHECKLIST

Before uploading:
- [ ] Have database backup ready
- [ ] Download all files below from local folder
- [ ] Note your domain name

Uploading files:
- [ ] Upload `api/pricing.php`
- [ ] Upload `api/shipments.php`
- [ ] Upload `setup-production.php`
- [ ] (Skip database.php - already correct on production)

Running setup:
- [ ] Open setup script URL in browser
- [ ] Wait for completion message
- [ ] DELETE `setup-production.php`

Testing:
- [ ] Test pricing API
- [ ] Clear browser cache
- [ ] Verify Air Cargo is hidden
- [ ] Check error logs

Final:
- [ ] Monitor for 24 hours
- [ ] Notify team of completion

---

## 📥 FILES TO DOWNLOAD & UPLOAD

**From your local /careygo/ folder:**

```
careygo/
├── api/
│   ├── pricing.php          ← UPLOAD THIS
│   └── shipments.php        ← UPLOAD THIS
├── config/
│   └── database.php         ← No changes needed
├── database/
│   ├── import-dtdc-master.php   ← UPLOAD (run via setup script)
│   └── reorganize-slabs.php     ← UPLOAD (run via setup script)
└── setup-production.php         ← UPLOAD (run once, then DELETE)
```

---

## ✨ VERIFICATION COMMANDS (After Setup)

Run these in your database client or phpMyAdmin:

```sql
-- Check pincodes
SELECT COUNT(*) FROM pincode_tat;
-- Expected: 15452

-- Check weight slabs  
SELECT COUNT(*) FROM pricing_slabs;
-- Expected: 24

-- Check backup tables exist
SHOW TABLES LIKE '%backup%';
-- Expected: 2 tables
```

---

## 🔙 ROLLBACK (If Needed)

If something breaks, contact your host to restore:

```sql
-- Restore pincodes from backup
TRUNCATE TABLE pincode_tat;
INSERT INTO pincode_tat SELECT * FROM pincode_tat_backup_YYYYMMDDHHMMSS;

-- Restore weight slabs from backup
TRUNCATE TABLE pricing_slabs;
INSERT INTO pricing_slabs SELECT * FROM pricing_slabs_backup_YYYYMMDDHHMMSS;
```

---

## ⚠️ CRITICAL REMINDERS

1. **DELETE setup-production.php after running it**
   - This is a security risk if left on server
   - Anyone could run it again

2. **Clear browser cache**
   - Old cached responses may show old data
   - Force refresh: Ctrl+Shift+Delete (Windows) or Cmd+Shift+Delete (Mac)

3. **Monitor logs**
   - Watch error logs for 24 hours
   - Check for any database errors

4. **Backup first**
   - Database backup before deployment
   - Can restore from backup tables if needed

---

## 📞 SUPPORT

If deployment fails:
1. Check error message from setup script
2. Verify database is accessible
3. Contact hosting support if database issues
4. Restore from backup tables if needed

---

## ✅ STATUS

- [x] Code fixes completed
- [x] Database scripts ready
- [x] Setup automation created
- [x] Documentation complete
- [ ] Files uploaded (YOU DO THIS)
- [ ] Setup script executed (YOU DO THIS)
- [ ] Testing completed (YOU DO THIS)

**Ready to deploy!** 🚀
