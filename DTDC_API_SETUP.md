# DTDC API Configuration Guide

## Overview

The Careygo tracking system uses the DTDC API to fetch real-time shipment tracking data. Currently, the system is configured with **demo credentials** that need to be replaced with your actual DTDC account credentials.

---

## Current Issue

The DTDC API is not working because:
1. ❌ Demo credentials are being used (PL3537_trk_json / wafBo)
2. ❌ No actual API key configuration
3. ❌ Customer code is set to demo value (PL3537)

---

## Step 1: Get Your DTDC Credentials

### From DTDC Account Portal:

1. **Login** to your DTDC account at: https://www.dtdc.in/ (or your account URL)
2. **Navigate** to: **Account → API Management** (or **Integration Settings**)
3. **Find** or **Generate** your credentials:
   - **Username** (for API authentication)
   - **Password** (for API authentication)
   - **API Key** (unique identifier for your account)
   - **Customer Code** (your DTDC customer account code)

4. **Copy** these values for the next step

---

## Step 2: Configure in Production

### Option A: Using .env File (Recommended)

Edit `.env` file and update:

```env
# ── DTDC TRACKING API CREDENTIALS ─────────────────────────
DTDC_USERNAME=your_actual_username
DTDC_PASSWORD=your_actual_password
DTDC_API_KEY=your_actual_api_key
DTDC_CUSTOMER_CODE=your_customer_code
```

**Example:**
```env
DTDC_USERNAME=ABC1234_api_user
DTDC_PASSWORD=MySecurePassword123
DTDC_API_KEY=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
DTDC_CUSTOMER_CODE=ABC1234
```

### Option B: Direct Code Update (Not Recommended)

If you don't have a .env loader, edit `lib/dtdc.php` lines 25-28:

```php
public function __construct(array $cfg = [])
{
    $this->username     = $cfg['username']      ?? 'YOUR_USERNAME';
    $this->password     = $cfg['password']      ?? 'YOUR_PASSWORD';
    $this->apiKey       = $cfg['api_key']       ?? 'YOUR_API_KEY';
    $this->customerCode = $cfg['customer_code'] ?? 'YOUR_CUSTOMER_CODE';
    $this->timeout      = $cfg['timeout']       ?? 30;
}
```

---

## Step 3: Test the Configuration

### Test Tool:

Visit `/dtdc_test.php` (admin only) to:
1. **Verify** current credentials are loaded
2. **Test** API connection with a test call
3. **See** detailed error messages if something fails

**Usage:**
1. Login as admin
2. Visit: `https://careygo.everythingb2c.in/dtdc_test.php`
3. Click "Test DTDC Connection"
4. Check results

### Expected Success Response:
```
✅ DTDC API is working!
Authentication successful. System can fetch tracking data.
```

### Common Errors:

| Error | Cause | Solution |
|-------|-------|----------|
| "Network error" | Cannot reach DTDC API | Check firewall/proxy, verify DTDC API endpoint |
| "Invalid credentials" | Wrong username/password | Verify credentials from DTDC portal |
| "Invalid API Key" | Wrong API key | Get correct key from DTDC |
| "HTTP 401" | Authentication failed | Ensure all 4 credentials are correct |

---

## Step 4: Verify Live Tracking Works

### Manual Test:

1. **Create a test booking** in the system
2. **Add DTDC AWB** (from your DTDC shipment)
3. **Go to** tracking page: `/public-tracking.php?tracking=CGO-XXXXXXXX`
4. **Verify** tracking events are displayed from DTDC

### Check Database:

For a shipment with tracking_no `CGO-ABC123`:

```sql
SELECT dtdc_awb, status FROM shipments WHERE tracking_no = 'CGO-ABC123';
```

Result should show:
- `dtdc_awb`: Your DTDC AWB number (e.g., `12345678901234`)
- `status`: Current shipment status

---

## How the System Uses DTDC API

### Flow Diagram:

```
User visits tracking page
        ↓
GET /api/tracking.php?tracking=CGO-XXXXXXXX
        ↓
Fetch shipment from DB
        ↓
If dtdc_awb is set:
    → Call DTDC API: /rest/JSONCnTrk/getTrackDetails
    → DtdcClient authenticates using your credentials
    → Parse DTDC response
    → Return live events
        ↓
Display timeline to user
```

### Event Flow:

1. **User** searches for shipment tracking number
2. **API** fetches shipment details from database
3. **If DTDC AWB exists**:
   - Authenticate with DTDC using your credentials
   - Fetch tracking events from DTDC
   - Cache token for 55 minutes
   - Return events to user
4. **If DTDC unavailable**:
   - Fall back to manual events in database
   - Show error message to user
5. **Display** timeline on public tracking page

---

## API Credentials Scope

### What Each Credential Does:

| Field | Purpose | Example |
|-------|---------|---------|
| **Username** | Identifies your account | `ABC1234_api_user` |
| **Password** | Authenticates API access | `MySecurePassword123` |
| **API Key** | Authorizes API calls | `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6` |
| **Customer Code** | DTDC customer account ID | `ABC1234` |

---

## Troubleshooting

### Token Caching:

- Tokens are cached for **55 minutes** in `/tmp/dtdc_tok_*.json`
- Cache is **auto-cleared** on 401 error
- Next request will re-authenticate

### Debug Mode:

To enable detailed logging, add to `lib/dtdc.php`:

```php
// After $result = $dtdc->track($awb);
if (!$result['success']) {
    error_log('DTDC Error: ' . $result['error']);
    error_log('DTDC Response: ' . print_r($result['raw'], true));
}
```

### Check Logs:

```bash
tail -f /tmp/php-error.log | grep DTDC
```

---

## Security Notes

1. **Never commit** real credentials to Git
2. **Use .env** for sensitive data (already in .gitignore)
3. **Rotate credentials** periodically
4. **Delete test files** after setup:
   - Remove: `dtdc_test.php`
   - Remove: `setup.php`
5. **Restrict access** to configuration files

---

## DTDC Contact

For credential issues or API support:

- **DTDC Website**: https://www.dtdc.in/
- **Support Email**: support@dtdc.in
- **Customer Service**: +91-XXXX-XXXX-XX
- **Account Portal**: https://account.dtdc.in/

---

## Files Modified/Created

| File | Status | Purpose |
|------|--------|---------|
| `lib/dtdc.php` | ✅ Updated | Now reads credentials from .env |
| `.env` | ✅ Updated | Added DTDC credentials section |
| `.env.example` | ✅ Updated | Added DTDC example |
| `dtdc_test.php` | ✅ Created | Testing & configuration guide |

---

## Next Steps

1. ✅ Get credentials from DTDC portal
2. ✅ Update `.env` file with real credentials
3. ✅ Visit `/dtdc_test.php` to verify
4. ✅ Test with real booking
5. ✅ Delete `dtdc_test.php` after verification
