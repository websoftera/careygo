# SMTP Configuration Guide

## Overview

Your Careygo email system now supports two methods:

1. **PHP Mail()** - Uses server's built-in mail system (default)
2. **SMTP** - Uses external email service (Gmail, SendGrid, etc.)

---

## Quick Setup

### **Option 1: Use PHP Mail() (Easiest)**

This is the **default** setting and requires no additional configuration.

**How it works:**
- Uses your server's local mail server (Postfix, Sendmail, etc.)
- No external dependencies needed
- Emails logged to `/tmp/cgo_emails/` for debugging

**To enable:** No action needed, it's already enabled!

---

### **Option 2: Use SMTP (Recommended for Production)**

#### **Step 1: Install PHPMailer**

```bash
cd /path/to/careygo
composer require phpmailer/phpmailer
```

**or manually:**

1. Download: https://github.com/PHPMailer/PHPMailer/releases
2. Extract to: `careygo/vendor/PHPMailer/`

#### **Step 2: Edit `.env` File**

Open `.env` in your Careygo root folder and enable SMTP:

```env
# Enable SMTP
SMTP_ENABLED=1

# Your SMTP Server Details
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
```

#### **Step 3: That's it!**

Emails will now use SMTP instead of PHP mail()

---

## SMTP Provider Configurations

### **1. Gmail (Google Account)**

**Configuration:**
```env
SMTP_ENABLED=1
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
```

**Steps to get App Password:**
1. Go to: https://myaccount.google.com/security
2. Enable "2-Step Verification" (if not already)
3. Create "App password":
   - Select "Mail"
   - Select "Windows PC" or device
   - Copy the 16-character password
4. Paste into `SMTP_PASS` in `.env`

✅ **Advantages:**
- Free tier available
- Easy setup
- Good delivery rate

⚠️ **Limitations:**
- Rate limited (100-500 emails/day free)
- Requires 2FA setup

---

### **2. SendGrid (Best for Production)**

**Configuration:**
```env
SMTP_ENABLED=1
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=apikey
SMTP_PASS=SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Steps:**
1. Sign up: https://sendgrid.com/
2. Go to: Settings → API Keys
3. Create new API Key
4. Copy key and paste into `SMTP_PASS`
5. Keep `SMTP_USER` as "apikey" (literal)

✅ **Advantages:**
- Excellent deliverability
- 100+ emails/day free
- Great analytics
- Webhook support for tracking

---

### **3. Mailgun**

**Configuration:**
```env
SMTP_ENABLED=1
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=postmaster@yourdomain.mailgun.org
SMTP_PASS=your-mailgun-password
```

**Steps:**
1. Sign up: https://www.mailgun.com/
2. Add domain
3. Go to Domain Settings → SMTP credentials
4. Use credentials provided

✅ **Advantages:**
- Free tier available
- Excellent for APIs
- Good documentation

---

### **4. AWS SES (Simple Email Service)**

**Configuration:**
```env
SMTP_ENABLED=1
SMTP_HOST=email-smtp.us-east-1.amazonaws.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=AKIAIOSFODNN7EXAMPLE
SMTP_PASS=BDkvGTu....
```

**Steps:**
1. Create AWS account
2. Go to SES console
3. Verify sender email
4. Create SMTP credentials
5. Copy to `.env`

⚠️ **Note:** Change region in SMTP_HOST if needed:
- `us-east-1` → us-east-1 (default)
- `eu-west-1` → eu-west-1
- etc.

✅ **Advantages:**
- 62,000 emails/month free
- Low cost at scale
- AWS ecosystem integration

---

### **5. Outlook/Office365**

**Configuration:**
```env
SMTP_ENABLED=1
SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-email@outlook.com
SMTP_PASS=your-password
```

✅ **Works with:**
- outlook.com
- office365.com
- Custom domain on Office365

---

### **6. Your Custom Server (Postfix, Exim, etc.)**

**Configuration:**
```env
SMTP_ENABLED=1
SMTP_HOST=mail.yourdomain.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-username
SMTP_PASS=your-password
```

**Common Ports:**
- 25 - Plain SMTP (unsecured)
- 465 - SMTPS (SSL)
- 587 - SMTP (TLS) ← Recommended
- 2525 - Alternative SMTP

---

## Configuration Reference

### `.env` File Location

**Windows:**
```
C:\xampp\htdocs\careygo\.env
```

**Linux/Mac:**
```
/var/www/html/careygo/.env
/home/user/careygo/.env
```

### Required Fields

```env
# Enable/disable SMTP
SMTP_ENABLED=1              # 0=disable, 1=enable

# Server details
SMTP_HOST=smtp.gmail.com    # SMTP server address
SMTP_PORT=587               # Port (usually 587 or 465)
SMTP_SECURE=tls             # 'tls' or 'ssl'

# Credentials
SMTP_USER=user@gmail.com    # Username/email
SMTP_PASS=app-password      # Password/API key
```

### Optional Fields

```env
# Email from/reply settings
CGO_EMAIL_FROM=noreply@careygo.in
CGO_EMAIL_REPLY=support@careygo.in
```

---

## Testing Your SMTP Configuration

### **Method 1: Create a Test Booking**

1. Go to Careygo platform
2. Create a booking with valid emails
3. Check if emails received
4. Check logs: `/tmp/cgo_emails/`

### **Method 2: Run Test Script**

Create `test-email.php`:

```php
<?php
require_once 'config/database.php';
require_once 'lib/email.php';

$emailService = new EmailService();

$customer = [
    'full_name' => 'Test User',
    'email' => 'your-email@gmail.com',
];

$shipment = [
    'id' => 1,
    'tracking_no' => 'CGO20260416TEST001',
    'pickup_name' => 'John Doe',
    'pickup_address' => '123 Main St, Mumbai',
    'pickup_city' => 'Mumbai',
    'pickup_pincode' => '400001',
    'delivery_name' => 'Jane Smith',
    'delivery_address' => '456 Oak Ave, Delhi',
    'delivery_city' => 'Delhi',
    'delivery_pincode' => '110001',
    'service_type' => 'standard',
    'weight' => 1.5,
    'final_price' => 500,
    'estimated_delivery' => date('Y-m-d', strtotime('+3 days')),
    'created_at' => date('Y-m-d H:i:s'),
];

echo "Sending test emails...\n";

$result1 = $emailService->sendSenderConfirmation($customer, $shipment);
echo "Sender confirmation: " . ($result1 ? '✅ Sent' : '❌ Failed') . "\n";

$result2 = $emailService->sendAWBReceipt($customer, $shipment);
echo "AWB receipt: " . ($result2 ? '✅ Sent' : '❌ Failed') . "\n";

$receiver = ['name' => 'Jane Smith', 'phone' => '+91-9876543210'];
$result3 = $emailService->sendReceiverNotification($receiver, $shipment, $customer);
echo "Receiver notification: " . ($result3 ? '✅ Sent' : '❌ Failed') . "\n";

echo "\nCheck your email inbox (and spam folder)!\n";
?>
```

**Run it:**
```bash
php test-email.php
```

---

## Troubleshooting

### **Problem: "SMTP connection failed"**

**Solutions:**
1. Verify SMTP credentials are correct
2. Check SMTP_PORT is correct (usually 587 or 465)
3. Check SMTP_HOST is correct
4. Verify credentials in original service
5. Check firewall allows outbound on port 587/465

**Debug:**
```bash
# Test SMTP connection manually
telnet smtp.gmail.com 587
# Should show: 220 ...
```

---

### **Problem: "Authentication failed"**

**Solutions:**
1. Double-check SMTP_USER and SMTP_PASS
2. For Gmail: Use App Password, not regular password
3. For SendGrid: SMTP_USER must be "apikey" (literal)
4. For AWS SES: Get SMTP credentials from AWS console
5. Reset API key if recently changed

---

### **Problem: "Emails not received"**

**Check:**
1. Email logs: `/tmp/cgo_emails/`
2. Spam/junk folder
3. Verify sender email is correct
4. Check email address is correct
5. Look for bounce notifications

**Debug:**
```bash
# Check email logs
tail -f /tmp/cgo_emails/*.txt

# Check PHP error log
tail -f /var/log/php-fpm.log | grep -i email
```

---

### **Problem: "SMTP_ENABLED not working"**

**Solutions:**
1. Verify `.env` file exists at Careygo root
2. Check syntax: `SMTP_ENABLED=1` (no spaces)
3. Verify PHPMailer installed: `vendor/autoload.php`
4. Restart PHP-FPM: `systemctl restart php-fpm`
5. Check file permissions: `.env` must be readable

---

## Email Logs Location

### Development Logs

Emails are logged to temp directory:

**Linux/Mac:**
```bash
/tmp/cgo_emails/2026-04-16_14-30-45_abc123def.txt
```

**Windows:**
```
C:\Windows\Temp\cgo_emails\2026-04-16_14-30-45_abc123def.txt
```

**View logs:**
```bash
tail -f /tmp/cgo_emails/*.txt
```

---

## Security Best Practices

1. **Never commit `.env` to Git:**
   ```bash
   # Add to .gitignore
   echo ".env" >> .gitignore
   git rm --cached .env
   ```

2. **Use strong passwords/API keys:**
   - Generate new API keys periodically
   - Don't reuse passwords

3. **Restrict file permissions:**
   ```bash
   chmod 600 .env
   ```

4. **Use TLS (secure) connection:**
   ```env
   SMTP_SECURE=tls  # Recommended
   # Not: SMTP_SECURE=none
   ```

5. **Don't log passwords:**
   - `.env` is not logged to `/tmp/cgo_emails/`
   - Only body content is logged

---

## Production Deployment Checklist

- [ ] SMTP credentials verified (test send email)
- [ ] `.env` file in Careygo root
- [ ] PHPMailer installed (`composer install`)
- [ ] File permissions correct (`.env` readable)
- [ ] No `.env` committed to Git
- [ ] DNS records configured (SPF, DKIM)
- [ ] Error logging enabled
- [ ] Backup SMTP provider configured (optional)
- [ ] Email monitoring set up
- [ ] Support team trained

---

## FAQ

**Q: Do I need SMTP if my server has mail()?**
A: No, PHP mail() works fine. Use SMTP if mail() is not working or for better delivery.

**Q: Which provider is cheapest?**
A: SendGrid and AWS SES have good free tiers. Gmail free tier is limited.

**Q: Can I use multiple SMTP providers?**
A: Current system uses one. For failover, modify email.php to try alternate.

**Q: How many emails can I send?**
A: Depends on provider:
- Gmail: 100-500/day
- SendGrid: 100+/day free
- AWS SES: 62,000/month free
- Custom server: Unlimited (depends on rate limits)

**Q: What if SMTP fails?**
A: Falls back to PHP mail() automatically. Booking still succeeds.

**Q: Are emails encrypted?**
A: Yes, TLS encryption by default (SMTP_SECURE=tls)

---

## Support

**Need help?**
1. Check email logs: `/tmp/cgo_emails/`
2. Review this guide: SMTP_CONFIGURATION.md
3. Test with: `test-email.php`
4. Contact: support@careygo.in

---

**Last Updated:** April 16, 2026
**Version:** 1.0
