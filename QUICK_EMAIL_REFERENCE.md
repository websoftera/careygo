# Email System - Quick Reference Guide

## What's New ✨

Your Careygo platform now automatically sends **3 professional emails** for every booking:

### 1. **Booking Confirmation** 
📧 Sent to: **Customer (Sender)**
📌 When: Immediately after booking
✅ Contains: Confirmation, tracking number, shipment details, delivery address, track button

### 2. **AWB Receipt/Invoice**
📄 Sent to: **Customer (Sender)**
📌 When: Immediately after booking
✅ Contains: Professional invoice, QR code, full details, pricing, T&C, print option

### 3. **Package Notification**
📮 Sent to: **Receiver** (optional, if email provided)
📌 When: Immediately after booking
✅ Contains: Incoming package alert, sender name, tracking number, ETA, tracking link

---

## For Customers (Senders)

### How to provide receiver's email?
1. Go to **New Booking** page
2. Fill in **Step 1: Primary Details**
3. In **Delivery Address** section, find:
   ```
   Email Address (for AWB notification) (optional)
   ```
4. Enter receiver's email: `receiver@example.com`
5. Complete booking

**Result:** Receiver gets package notification automatically! 📬

### What will receiver see?
Receiver gets an email with:
- 📦 "You have an incoming package"
- 👤 From: John Doe
- 📍 Route: Mumbai → Delhi
- 📌 Tracking #: CGO20260416XXXXXXXX
- 📅 ETA: 19 Apr 2026
- 🔗 Track button (no login needed)

---

## Email Templates

### 🟦 Careygo Branding
All emails use:
- **Careygo Logistics** logo & colors
- **Blue gradient header** (#001A93)
- **Professional layout**
- **Mobile responsive**

### Customization
All emails are fully customizable:
- Change colors in `lib/email.php`
- Update text in email template methods
- Add/remove sections
- Modify footer content

---

## Technical Setup

### Default Configuration
```php
From:      noreply@careygo.in
Reply-To:  support@careygo.in
Admin:     admin@careygo.in
```

### Production Email Setup

**Option 1: Use built-in PHP mail() (Recommended for most)**
1. Ensure mail server running: `postfix` or `sendmail`
2. Configure DNS records:
   - SPF record
   - DKIM record
   - DMARC policy

**Option 2: Use SMTP (Gmail, SendGrid, AWS SES)**
1. Install PHPMailer:
   ```bash
   composer require phpmailer/phpmailer
   ```
2. Update `lib/email.php` (see EMAIL_SETUP_GUIDE.md)
3. Set environment variables:
   ```bash
   SMTP_HOST=smtp.gmail.com
   SMTP_USER=your-email@gmail.com
   SMTP_PASS=your-app-password
   ```

---

## Testing

### Local Development
Emails logged to: `/tmp/cgo_emails/`
```bash
# View test emails
ls -la /tmp/cgo_emails/
cat /tmp/cgo_emails/2026-04-16_*.txt
```

### Production Testing
1. Create test booking with real email
2. Verify 3 emails received
3. Click links and verify working
4. Test public tracking page

---

## Email Content Preview

### Booking Confirmation
```
✅ BOOKING CONFIRMED!

🎫 Tracking Number: CGO20260416ABC12345
    (Save for your records)

📦 SHIPMENT DETAILS
Service: Standard Express
Weight: 1.500 kg
Amount: ₹500
Delivery: 19 Apr 2026

🎯 DELIVERY ADDRESS
Jane Smith, +91-98765-43210
123 Main Street, Delhi, 110001

[TRACK YOUR SHIPMENT →]
```

### AWB Receipt
```
📦 CAREYGO LOGISTICS
AIR WAYBILL RECEIPT

📌 TRACKING NUMBER
   ┌─────────────────────────┐
   │ CGO20260416ABC12345     │
   │      [QR Code]          │
   │      ✓ BOOKED           │
   └─────────────────────────┘

📤 SENDER DETAILS
Name: John Doe
Address: 456 Office Street
Mumbai, 400001

📥 RECEIVER DETAILS
Name: Jane Smith
Address: 123 Main Street
Delhi, 110001

📦 SHIPMENT DETAILS
Service: Standard Express
Weight: 1.500 kg
Contents: Documents

💰 BILLING
Base Price: ₹600
Discount (20%): -₹100
TOTAL: ₹500

⚖️ Terms & Conditions...

[🖨️ PRINT / SAVE AS PDF]
```

### Receiver Notification
```
📦 INCOMING COURIER!

Hello Jane,

John Doe has sent you a package via Careygo Logistics.

📌 TRACKING NUMBER
CGO20260416ABC12345
[QR Code]

📋 SHIPMENT INFORMATION
From: Mumbai
To: Delhi
Weight: 1.500 kg
Contents: Documents
Expected Delivery: 19 Apr 2026

[TRACK PACKAGE →]

For questions: support@careygo.in
```

---

## Public Tracking Page

Receivers can track WITHOUT login:

**URL:** `https://careygo.in/public-tracking.php?tracking=CGO20260416ABC12345`

**Features:**
- No authentication required
- Live status timeline
- DTDC tracking integration
- Mobile responsive
- Share button

---

## Troubleshooting

### Problem: Email not received
**Check:**
1. Spam/junk folder ✉️
2. Email logs in `/tmp/cgo_emails/`
3. PHP error log `/var/log/php-fpm.log`
4. Mail server status: `systemctl status postfix`

### Problem: Receiver email field not working
**Check:**
1. JavaScript console for errors (F12)
2. Verify form field exists: `delivery_email`
3. Browser cache (clear or hard refresh Ctrl+Shift+R)

### Problem: Email template looks weird
**Check:**
1. Try in Gmail first (most compatible)
2. Check email client (Outlook, Apple Mail)
3. Use Litmus email testing tool
4. Verify all images HTTPS

### Problem: No emails after booking
**Check:**
1. Customer email captured correctly
2. SMTP server running
3. DNS SPF/DKIM records configured
4. PHP mail() enabled

---

## Configuration Files

### Main Files
```
lib/email.php                    # Email service (create emails)
api/shipments.php               # Integration (trigger emails)
customer/new-booking.php        # Form (capture receiver email)
js/delivery.js                  # JavaScript (submit form)
public-tracking.php             # Public tracking page
```

### Documentation
```
EMAIL_SETUP_GUIDE.md            # Complete setup guide
EMAIL_SYSTEM_IMPLEMENTATION.md  # Technical details
QUICK_EMAIL_REFERENCE.md        # This file
```

---

## Feature Checklist

### Implemented ✅
- [x] Booking confirmation email to sender
- [x] AWB receipt with QR code
- [x] Package notification to receiver
- [x] Careygo branding on all emails
- [x] Mobile responsive templates
- [x] Public tracking page (no login)
- [x] Error handling (graceful)
- [x] Development logging (/tmp)
- [x] Optional receiver email field
- [x] All 3 emails sent per booking

### Email Features ✅
- [x] HTML formatted emails
- [x] Color-coded status badges
- [x] Tracking number prominent
- [x] QR codes for scanning
- [x] Professional layout
- [x] Support contact info
- [x] Terms & conditions
- [x] Print-friendly design

---

## Go-Live Checklist

Before deploying to production:

- [ ] Email server configured (SMTP or local)
- [ ] DNS records set (SPF, DKIM, DMARC)
- [ ] Send test booking, verify 3 emails
- [ ] Check spam folder
- [ ] Test public tracking link
- [ ] Verify QR code scans
- [ ] Mobile email view tested
- [ ] Error logging working
- [ ] Support email in footer correct
- [ ] Admin notified of feature

---

## Quick Commands

### View Email Logs (Local)
```bash
tail -f /tmp/cgo_emails/*.txt
```

### Check Mail Server Status
```bash
systemctl status postfix
# or
systemctl status sendmail
```

### Test Email Sending
```bash
echo "Test" | mail -s "Test Email" your@email.com
```

### View PHP Error Log
```bash
tail -f /var/log/php-fpm.log | grep "Email"
```

---

## Support

**Questions? Need help?**
- 📧 Email: support@careygo.in
- 📞 Phone: +91-98502-96178
- 📚 Docs: See EMAIL_SETUP_GUIDE.md

---

## Version Info

**Email System v1.0**
- Released: April 16, 2026
- Status: Production Ready ✅
- Last Updated: April 16, 2026

**New in this version:**
- Automated booking confirmation emails
- Professional AWB receipts with Careygo branding
- Package notifications for receivers
- Public tracking page (no login)
- Full HTML email templates
- Development logging support
- Error handling & graceful degradation

---

**You're all set! 🎉 Email notifications are live!**
