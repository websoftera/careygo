# Production Database Update Guide

## Problem

Rate calculator and new delivery are not working in production because:

1. ❌ New tables not created (schema_v3.sql - DTDC tracking)
2. ❌ New columns not added (dtdc_awb, etc.)
3. ❌ Pincode data not imported (15,453 pincodes)
4. ❌ Pricing tables not updated (pricing_slabs with zones)

---

## Solution: Run Database Migrations

### **What Gets Updated**

| Schema File | What's Added | Status |
|-------------|--------------|--------|
| `schema.sql` | Core tables (users, shipments, addresses) | ✅ Already in prod |
| `schema_v2.sql` | Pricing, pincodes (pincode_tat) | ⚠️ Check if present |
| `schema_v3.sql` | DTDC tracking (shipment_tracking_events) | ❌ **MISSING** |
| `pincodes.sql` | 15,453 pincode entries | ❌ **MISSING** |

---

## 🚀 Production Update Method

### **Option 1: Use Setup.php (Recommended)**

**URL:** `https://careygo.everythingb2c.in/setup.php?key=careygo_setup_2026`

**Steps:**

1. **Open in browser:**
   ```
   https://yourdomain.com/setup.php?key=careygo_setup_2026
   ```

2. **Page shows:**
   - Migration results (✅ OK or ⚠️ Warning)
   - Tables created/updated
   - Status of each migration

3. **Import pincodes (takes 15-30 seconds):**
   ```
   https://yourdomain.com/setup.php?key=careygo_setup_2026&pincodes=1
   ```

4. **Verify:**
   - All migrations show ✅ OK
   - Tables listed at bottom
   - No errors

---

### **Option 2: Direct SQL (Advanced)**

If setup.php has access issues, run SQL directly:

**In phpMyAdmin or MySQL CLI:**

```bash
# Connect to database
mysql -h localhost -u u141519101_careygo -p u141519101_careygo

# Then paste each SQL file:
```

**Step 1: Run schema_v3.sql**
```sql
-- Add DTDC AWB column to shipments
ALTER TABLE `shipments`
    ADD COLUMN IF NOT EXISTS `dtdc_awb` VARCHAR(50) DEFAULT NULL
        COMMENT 'DTDC Air Waybill number for live tracking'
        AFTER `tracking_no`;

-- Create shipment tracking events table
CREATE TABLE IF NOT EXISTS `shipment_tracking_events` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `shipment_id` INT UNSIGNED  NOT NULL,
    `event_time`  DATETIME      NOT NULL,
    `location`    VARCHAR(200)  DEFAULT NULL,
    `status`      VARCHAR(100)  NOT NULL,
    `description` TEXT          DEFAULT NULL,
    `source`      ENUM('manual','dtdc') NOT NULL DEFAULT 'manual',
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_shipment_id` (`shipment_id`),
    INDEX `idx_event_time`  (`event_time`),
    FOREIGN KEY (`shipment_id`) REFERENCES `shipments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Step 2: Import pincodes.sql**

Download file: `database/pincodes.sql` from your Careygo installation
Upload and execute in phpMyAdmin

Or via CLI:
```bash
mysql -h localhost -u u141519101_careygo -p u141519101_careygo < pincodes.sql
```

---

## ✅ Verify Update Was Successful

### **Check 1: Tables exist**

Run in MySQL:
```sql
-- Check shipment_tracking_events table
SHOW TABLES LIKE 'shipment_tracking_events';
-- Should return: 1 row

-- Check if dtdc_awb column exists
DESCRIBE shipments;
-- Should show dtdc_awb column

-- Check pincodes were imported
SELECT COUNT(*) as pincode_count FROM pincode_tat;
-- Should show: 15453 (or close to it)
```

### **Check 2: Features work**

In production:
1. **Rate Calculator** - Click button, enter pincodes
   - Should show cities/states
   - Should calculate prices

2. **New Booking** - Create booking
   - Should allow entering receiver email
   - Should create shipment

3. **Admin Tracking** - Go to deliveries
   - Should see tracking button 🌍
   - Should open tracking management modal

---

## 🔍 Troubleshooting

### **Issue: "setup.php not accessible"**

**Solutions:**
1. Check access key is correct: `?key=careygo_setup_2026`
2. Check if setup.php exists in root folder
3. Check file permissions: `chmod 644 setup.php`
4. Use Option 2 (Direct SQL) instead

---

### **Issue: "Table already exists"**

**Good news!** This is a ✅ warning, not an error.

It means:
- Table already created
- Migration already ran before
- Schema is up to date

**Action:** Check if all features work

---

### **Issue: "Pincode table shows 0 rows"**

**Solutions:**
1. Run pincodes import:
   ```
   ?key=careygo_setup_2026&pincodes=1
   ```
2. Wait 15-30 seconds
3. Check count again:
   ```sql
   SELECT COUNT(*) FROM pincode_tat;
   ```

---

### **Issue: "Rate calculator still not working"**

**Check these:**

1. **Verify pincode_tat table has data:**
   ```sql
   SELECT COUNT(*) FROM pincode_tat;
   -- Should be ~15,453
   ```

2. **Check API is working:**
   - Test: `https://yourdomain.com/api/pincode.php?pincode=400001`
   - Should return city/state info

3. **Check pricing_slabs exists:**
   ```sql
   SHOW TABLES LIKE 'pricing_slabs';
   -- Should return: 1 row
   ```

4. **Check if pricing_slabs has data:**
   ```sql
   SELECT COUNT(*) FROM pricing_slabs;
   -- Should be > 0
   ```

5. **Check browser console for errors:**
   - Press F12
   - Go to Console tab
   - Try rate calculator again
   - Look for red error messages

---

## 📋 Database Schema Changes

### New Table: `shipment_tracking_events`

```sql
CREATE TABLE shipment_tracking_events (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    shipment_id     INT UNSIGNED NOT NULL,
    event_time      DATETIME NOT NULL,
    location        VARCHAR(200),
    status          VARCHAR(100) NOT NULL,
    description     TEXT,
    source          ENUM('manual','dtdc') DEFAULT 'manual',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    INDEX idx_shipment_id (shipment_id),
    INDEX idx_event_time (event_time)
);
```

**Purpose:** Store tracking events (manual updates + DTDC live tracking)

---

### New Column: `shipments.dtdc_awb`

```sql
ALTER TABLE shipments ADD COLUMN dtdc_awb VARCHAR(50) DEFAULT NULL;
```

**Purpose:** Store DTDC Air Waybill number for live tracking

---

### New Table: `pincode_tat`

```sql
CREATE TABLE pincode_tat (
    pincode         CHAR(6) PRIMARY KEY,
    city            VARCHAR(100),
    state           VARCHAR(100),
    zone            VARCHAR(50),
    tat_standard    INT DEFAULT 3,
    tat_premium     INT DEFAULT 1,
    tat_air         INT DEFAULT 2,
    tat_surface     INT DEFAULT 5,
    serviceable     BOOLEAN DEFAULT TRUE
);
```

**Purpose:** Store pincode data (15,453 entries) for rate calculation

---

### Updated Table: `pricing_slabs`

New column added (if not already present):

```sql
ALTER TABLE pricing_slabs ADD COLUMN zone VARCHAR(50) DEFAULT NULL;
```

**Purpose:** Support zone-based pricing (within_city, within_state, metro, rest_of_india)

---

## Step-by-Step Production Update

### **For Shared Hosting / cPanel**

1. **Access cPanel** → phpMyAdmin
2. **Select your database** (u141519101_careygo)
3. **Click "Import" tab**
4. **Upload files in order:**
   - Don't upload: schema.sql (already exists)
   - Upload: `schema_v2.sql` (if not already done)
   - Upload: `schema_v3.sql`
   - Upload: `pincodes.sql` (takes 30 seconds)
5. **Click "Go"**
6. **Check results** - all should say "Executed successfully"

---

### **For VPS / Dedicated Server**

1. **SSH into server:**
   ```bash
   ssh user@yourdomain.com
   ```

2. **Navigate to Careygo:**
   ```bash
   cd /var/www/html/careygo
   ```

3. **Run migrations:**
   ```bash
   # Option A: Via setup.php
   curl "https://yourdomain.com/setup.php?key=careygo_setup_2026"
   curl "https://yourdomain.com/setup.php?key=careygo_setup_2026&pincodes=1"
   
   # Option B: Via MySQL directly
   mysql -h localhost -u u141519101_careygo -p < database/schema_v3.sql
   mysql -h localhost -u u141519101_careygo -p < database/pincodes.sql
   ```

4. **Verify:**
   ```bash
   mysql -h localhost -u u141519101_careygo -p -e "SELECT COUNT(*) as pincodes FROM u141519101_careygo.pincode_tat;"
   ```

---

### **For Docker / Cloud (AWS, GCP, Azure)**

1. **Connect to database:**
   ```bash
   docker exec careygo-mysql mysql -u u141519101_careygo -p u141519101_careygo
   ```

2. **Run SQL files:**
   ```bash
   docker cp database/schema_v3.sql careygo-mysql:/tmp/
   docker exec careygo-mysql mysql -u u141519101_careygo -p u141519101_careygo < /tmp/schema_v3.sql
   ```

3. **Import pincodes:**
   ```bash
   docker cp database/pincodes.sql careygo-mysql:/tmp/
   docker exec careygo-mysql mysql -u u141519101_careygo -p u141519101_careygo < /tmp/pincodes.sql
   ```

---

## ⚠️ Important Notes

### **Before Running Updates:**

- ✅ **Backup your database first!**
  ```bash
  mysqldump -h localhost -u u141519101_careygo -p u141519101_careygo > backup.sql
  ```

- ✅ **Don't interrupt** - Let migrations complete (pincodes takes 15-30 sec)

- ✅ **Check file permissions** - setup.php must be readable

### **After Running Updates:**

- ✅ **Delete setup.php** from production (security risk)
  ```bash
  rm setup.php
  ```

- ✅ **Verify all features work:**
  - Rate calculator
  - New booking
  - Tracking management

- ✅ **Check error logs** if something doesn't work
  ```bash
  tail -f /var/log/php-fpm.log
  ```

---

## 📊 Expected Results

After successful update:

| Feature | Before | After |
|---------|--------|-------|
| Rate Calculator | ❌ Not working | ✅ Working |
| New Booking | ❌ Missing fields | ✅ Works |
| Tracking | ❌ Not available | ✅ Available |
| Pincodes | ❌ Empty | ✅ 15,453 entries |
| DTDC Tracking | ❌ Not possible | ✅ Supported |

---

## 🔒 Security After Update

1. **Delete setup.php:**
   ```bash
   rm /var/www/html/careygo/setup.php
   ```

2. **Verify access key:**
   - Old key: `careygo_setup_2026`
   - Change in setup.php before next use

3. **Check database backups:**
   - Keep backup before migration
   - Store safely (not in web root)

---

## Quick Checklist

- [ ] Backup database
- [ ] Run schema_v3.sql
- [ ] Import pincodes.sql
- [ ] Check pincode_tat has 15,453 rows
- [ ] Test rate calculator (enter pincode 400001)
- [ ] Test new booking
- [ ] Test tracking (if DTDC enabled)
- [ ] Delete setup.php (security)
- [ ] Check error logs
- [ ] Verify all features working

---

## Support

**If updates fail:**

1. Check `/tmp/cgo_emails/` logs
2. Check MySQL error log
3. Verify database credentials
4. Try direct SQL method instead
5. Contact: support@careygo.in

**Database credentials in production:**
- Host: localhost
- User: u141519101_careygo
- Pass: +DgrP256
- DB: u141519101_careygo

---

**That's it! Your production database will be updated and features will work! 🎉**
