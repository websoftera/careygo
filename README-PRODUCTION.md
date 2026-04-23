# Careygo Production Deployment - Complete Guide

## 📋 What Has Been Completed

✅ **Phase 1: DTDC Master Pincode Import**
- Imported 15,452 pincodes from DTDC master file
- Created automatic backup tables
- Validated all data with proper error handling
- TAT values set based on Region (Metro/Non-Metro)

✅ **Phase 2: Code Updates (Ready to Deploy)**
- Service weight constraints (Standard Express: 2kg max)
- Air Cargo availability filtering
- Weight validation in booking API
- Database configuration fixes

✅ **Phase 3: Weight Slab Reorganization**
- Converted to standard 6-tier logistics system
- All 4 services now have proper tier structure
- Incremental pricing for weights above 5kg

---

## 🚀 How to Deploy to Production

### Option A: Automated Deployment (Recommended)

#### For Linux/Unix servers:
```bash
# 1. Copy the script to your production server
scp PRODUCTION-QUICK-START.sh user@your-server.com:/home/user/careygo/

# 2. SSH into the server
ssh user@your-server.com
cd /home/user/careygo

# 3. Make script executable
chmod +x PRODUCTION-QUICK-START.sh

# 4. Run the deployment
./PRODUCTION-QUICK-START.sh
```

#### For Windows servers:
```powershell
# 1. Copy the script to your production server
# (Using RDP or file transfer)

# 2. Open Command Prompt (or PowerShell)
cd C:\path\to\careygo

# 3. Run the deployment
PRODUCTION-QUICK-START.bat
```

### Option B: Manual Step-by-Step Deployment

See detailed instructions in **PRODUCTION-DEPLOYMENT.md**

---

## 📦 Files to Deploy

Copy these files to your production server:

### Scripts (run once during deployment):
```
database/import-dtdc-master.php        ← Run this first
database/reorganize-slabs.php          ← Run this second
```

### Code Files (update with changes):
```
config/database.php                    ← Update lines 21-24
api/pricing.php                        ← Add constraints (line 140)
api/shipments.php                      ← Add validation (line 107)
```

### Documentation:
```
PRODUCTION-DEPLOYMENT.md               ← Detailed instructions
PRODUCTION-QUICK-START.sh             ← Linux/Unix automation
PRODUCTION-QUICK-START.bat            ← Windows automation
```

---

## ⚡ Quick Deployment Checklist

- [ ] **Backup databases** (full database backup before deployment)
- [ ] **Copy Excel file** to production: `DTDC_Pincode_TAT_Details_Master_V0_09 April 2026.xlsx`
- [ ] **Run Phase 1** - Pincode import (automatic with quick-start script)
- [ ] **Apply Phase 2** - Code changes (3 files to update)
- [ ] **Run Phase 3** - Weight slab reorganization (automatic with quick-start script)
- [ ] **Verify** - Run SQL checks from PRODUCTION-DEPLOYMENT.md
- [ ] **Test** - Test pricing API with sample weights
- [ ] **Monitor** - Watch logs for 24 hours

---

## 🔄 What Changes Are Being Made

### 1. Database Changes
```
✓ 15,452 pincodes imported (from Excel)
✓ 24 weight slabs created (6 tiers × 4 services)
✓ TAT values: Metro (fast) vs Non-Metro (standard)
```

### 2. Service Weight Limits
```
✓ Standard Express:   max 2.0 kg
✓ Premium Express:    max 5.0 kg
✓ Air Cargo:          max 10.0 kg
✓ Surface Cargo:      max 25.0 kg
```

### 3. Weight Tier System
```
Tier 1: 0.000 - 0.250 kg   (First 250g)
Tier 2: 0.250 - 0.500 kg   (Next 250g)
Tier 3: 0.500 - 1.000 kg   (Next 500g)
Tier 4: 1.000 - 2.000 kg   (Next 1kg)
Tier 5: 2.000 - 5.000 kg   (Next 3kg)
Tier 6: 5.000 - ∞ kg       (Above 5kg with incremental pricing)
```

### 4. Code Updates
```
✓ Pricing API: Checks weight constraints before returning services
✓ Booking API: Validates weight limits per service
✓ Database Config: Uses correct credentials/defaults
```

---

## 🧪 Testing After Deployment

### 1. Test Pincode Import
```bash
mysql -u root -p careygo -e "SELECT COUNT(*) as 'Total Pincodes' FROM pincode_tat;"
# Should show: 15452
```

### 2. Test Weight Slabs
```bash
mysql -u root -p careygo -e "SELECT COUNT(*) as 'Total Slabs' FROM pricing_slabs;"
# Should show: 24
```

### 3. Test API Constraints
```bash
# Standard Express at 1.5kg (should work)
curl "https://your-domain.com/api/pricing.php?weight=1.5&pickup=110001&delivery=110002"

# Standard Express at 2.5kg (should exclude standard service)
curl "https://your-domain.com/api/pricing.php?weight=2.5&pickup=110001&delivery=110002"
```

---

## 🔙 Rollback Procedure

If something goes wrong:

### Rollback Pincodes
```sql
-- List available backups
SHOW TABLES LIKE 'pincode_tat_backup_%';

-- Restore (replace YYYYMMDDHHMMSS with actual backup table date)
TRUNCATE TABLE pincode_tat;
INSERT INTO pincode_tat SELECT * FROM pincode_tat_backup_YYYYMMDDHHMMSS;
```

### Rollback Weight Slabs
```sql
-- List available backups
SHOW TABLES LIKE 'pricing_slabs_backup_%';

-- Restore (replace YYYYMMDDHHMMSS with actual backup table date)
TRUNCATE TABLE pricing_slabs;
INSERT INTO pricing_slabs SELECT * FROM pricing_slabs_backup_YYYYMMDDHHMMSS;
```

### Rollback Code Changes
Simply revert the 3 PHP files to their previous versions from your backup.

---

## 📊 Expected Results After Deployment

### Database State
- **Total Pincodes**: 15,452
- **States Covered**: 34
- **Cities/Districts**: 1,927
- **Metro Pincodes**: ~626
- **Non-Metro Pincodes**: ~14,826

### Pricing Slabs
- **Total Slabs**: 24 (6 per service type)
- **Services**: Standard, Premium, Air Cargo, Surface
- **Weight Tiers**: 6 progressive tiers from 0kg to 25kg+

### Service Availability
```
Weight 1.0kg   → All 4 services available
Weight 2.0kg   → All 4 services available (Standard at max)
Weight 2.5kg   → Premium, Air Cargo, Surface (Standard excluded)
Weight 5.0kg   → Premium, Air Cargo, Surface (Premium at max)
Weight 5.5kg   → Air Cargo, Surface (Premium excluded)
Weight 10.0kg  → Surface (Air Cargo at max)
Weight 25.0kg  → Surface (Surface at max)
Weight 25.5kg  → None available
```

---

## 🚨 Critical Notes

1. **Backup First**: Always backup your database before deployment
2. **Test First**: Test in staging environment if possible
3. **Excel File**: Ensure the DTDC Excel file is in the `/database/` directory
4. **Database Credentials**: Update .env if production credentials are different
5. **Code Review**: Have a developer review code changes before deployment
6. **Monitoring**: Monitor logs for 24 hours post-deployment
7. **Support**: Keep DTDC support contact handy in case of data issues

---

## 📞 Support & Troubleshooting

### Common Issues

**Issue**: "Excel file not found"
- **Solution**: Place `DTDC_Pincode_TAT_Details_Master_V0_09 April 2026.xlsx` in `/database/` directory

**Issue**: "Database connection failed"
- **Solution**: Check `.env` file database credentials match your production setup

**Issue**: "Weight slabs reorganization failed"
- **Solution**: Check backup table was created; use rollback procedure

**Issue**: "Pricing API not returning services"
- **Solution**: Verify weight slabs exist; check if weight exceeds service limits

---

## 📈 Performance Impact

- **Pincode Import**: One-time operation (~2-3 minutes)
- **Slab Reorganization**: One-time operation (~1 minute)
- **API Performance**: No impact (pricing lookup is O(1))
- **Database Size**: Increases by ~2-3 MB (pincodes + slabs)

---

## ✅ Success Criteria

Deployment is successful when:

✓ All 15,452 pincodes imported  
✓ All 24 weight slabs created  
✓ Standard Express limited to 2kg  
✓ Premium Express limited to 5kg  
✓ Air Cargo limited to 10kg  
✓ Surface limited to 25kg  
✓ Zone determination working (within_city, within_state, metro, rest_of_india)  
✓ Customer bookings processed successfully  
✓ Pricing API returns correct services per weight  
✓ Email notifications sending correctly  
✓ No errors in application logs  

---

## 📚 Documentation Files

1. **PRODUCTION-DEPLOYMENT.md** - Detailed step-by-step guide
2. **PRODUCTION-QUICK-START.sh** - Linux/Unix automation script
3. **PRODUCTION-QUICK-START.bat** - Windows automation script
4. **README-PRODUCTION.md** - This file
5. **database/import-dtdc-master.php** - Pincode import script
6. **database/reorganize-slabs.php** - Weight slab reorganization script

---

## 🎯 Final Steps

1. Copy all files from `/database/`, `/api/`, `/config/` to production
2. Run appropriate quick-start script (`.sh` for Linux, `.bat` for Windows)
3. Apply code changes if not done automatically
4. Run SQL verification queries
5. Test pricing API with various weights
6. Monitor logs for 24 hours
7. Notify support team of successful deployment

---

**Version**: 1.0  
**Last Updated**: April 23, 2026  
**Status**: Production Ready  
**Backup Tables**: `pincode_tat_backup_YYYYMMDDHHMMSS` and `pricing_slabs_backup_YYYYMMDDHHMMSS`

---

For questions or issues, refer to PRODUCTION-DEPLOYMENT.md or contact your development team.
