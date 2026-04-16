# SMTP Setup - Quick Start Guide

## 🚀 5-Minute Setup

### **Where to Add SMTP Settings?**

Everything goes in **ONE FILE**: `.env` at your Careygo root folder

```
careygo/
├── .env  ← ADD SETTINGS HERE
├── api/
├── config/
├── customer/
├── lib/
└── ...
```

---

## 📋 Step-by-Step Setup

### **Step 1: Create/Edit `.env` File**

**Location:**
```
Windows: C:\xampp\htdocs\careygo\.env
Linux:   /var/www/html/careygo/.env
Mac:     /Users/you/careygo/.env
```

**Open with:** Notepad, VS Code, or any text editor

### **Step 2: Copy This (Choose Your Provider)**

#### **Option A: Gmail (Most Common)**
```env
SMTP_ENABLED=1
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-16-char-app-password
```

Get Gmail app password:
1. Go: https://myaccount.google.com/security
2. Enable 2-Step Verification
3. Go to "App passwords" → Mail → Device → Copy password
4. Paste as `SMTP_PASS`

---

#### **Option B: SendGrid**
```env
SMTP_ENABLED=1
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=apikey
SMTP_PASS=SG.xxxxxxxxxxxxx
```

Get SendGrid API key:
1. Sign up: https://sendgrid.com/
2. Settings → API Keys → Create Key
3. Copy & paste as `SMTP_PASS`

---

#### **Option C: AWS SES**
```env
SMTP_ENABLED=1
SMTP_HOST=email-smtp.us-east-1.amazonaws.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-smtp-username
SMTP_PASS=your-smtp-password
```

Get AWS SES credentials:
1. AWS Console → SES → SMTP Settings
2. Copy username & password

---

#### **Option D: Outlook/Office365**
```env
SMTP_ENABLED=1
SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=your-email@outlook.com
SMTP_PASS=your-password
```

---

#### **Option E: Mailgun**
```env
SMTP_ENABLED=1
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USER=postmaster@yourdomain.mailgun.org
SMTP_PASS=your-mailgun-password
```

---

### **Step 3: Install PHPMailer**

Run in your terminal:

```bash
cd /path/to/careygo
composer require phpmailer/phpmailer
```

**Don't have Composer?**
1. Download: https://getcomposer.org/download/
2. Run installer
3. Then run the command above

---

### **Step 4: Save & Test**

1. **Save** the `.env` file
2. **Create a booking** in Careygo
3. **Check email** (wait 30 seconds)
4. **Check spam folder** if not in inbox

---

## ✅ Verify It's Working

### **Check Email Logs**

```bash
# On Windows (PowerShell)
Get-ChildItem C:\Windows\Temp\cgo_emails\

# On Linux/Mac
ls -la /tmp/cgo_emails/

# View content
tail -f /tmp/cgo_emails/*.txt
```

### **Look for Success Messages**

Log file should show:
```
Method: SMTP
To: recipient@example.com
Subject: ✅ Your Booking Confirmed
Status: SUCCESS
```

If you see `FAILED`, check:
- SMTP credentials are correct
- PHPMailer is installed
- SMTP_ENABLED=1

---

## 🎨 Email Header Customization

Edit sender name/email in `.env`:

```env
CGO_EMAIL_FROM=noreply@careygo.in
CGO_EMAIL_REPLY=support@careygo.in
CGO_ADMIN_EMAIL=admin@careygo.in
```

Or edit in **`lib/email.php`**:
```php
$this->fromName = 'Careygo Logistics';  // Change this
$this->from     = 'noreply@careygo.in'; // Change this
```

---

## 🔧 Configuration Reference

### Full `.env` Template

```env
# ═══════════════════════════════════
# CAREYGO EMAIL CONFIGURATION
# ═══════════════════════════════════

# Email sender info
CGO_EMAIL_FROM=noreply@careygo.in
CGO_EMAIL_REPLY=support@careygo.in
CGO_ADMIN_EMAIL=admin@careygo.in

# ═══════════════════════════════════
# SMTP CONFIGURATION
# ═══════════════════════════════════

# Enable SMTP (1=yes, 0=no)
SMTP_ENABLED=1

# SMTP Server
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls

# SMTP Login
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password

# ═══════════════════════════════════
# (Optional) Other Settings
# ═══════════════════════════════════

# Database (if not working, uncomment)
#CGO_DB_HOST=localhost
#CGO_DB_USER=careygo
#CGO_DB_PASS=password
#CGO_DB_NAME=careygo

# JWT Secret
#CGO_JWT_SECRET=your-secret-key

# Site URL
#CGO_SITE_URL=https://careygo.everythingb2c.in
```

---

## ❓ Common Issues & Fixes

### **Issue: "Authentication failed"**
```
✓ Check SMTP_USER is correct
✓ Check SMTP_PASS is correct
✓ For Gmail: Use App Password, NOT regular password
✓ For SendGrid: SMTP_USER must be "apikey"
```

### **Issue: "Connection timeout"**
```
✓ Check SMTP_HOST is correct
✓ Check SMTP_PORT is correct (usually 587)
✓ Check firewall allows outbound on that port
✓ Try different port: 465 (SSL)
```

### **Issue: "PHPMailer not found"**
```
✓ Run: composer require phpmailer/phpmailer
✓ Check vendor/autoload.php exists
✓ Check .env SMTP_ENABLED=1
```

### **Issue: "Emails still not received"**
```
✓ Check /tmp/cgo_emails/ logs
✓ Check spam folder
✓ Wait 30-60 seconds (email propagation)
✓ Try different test email address
✓ Check PHP error log
```

---

## 📝 File Locations Reference

| What | Where |
|------|-------|
| SMTP Settings | `careygo/.env` |
| Email Code | `careygo/lib/email.php` |
| Integration | `careygo/api/shipments.php` |
| Email Logs | `/tmp/cgo_emails/` (dev) |
| Error Logs | `/var/log/php-fpm.log` |

---

## 🧪 Test Email Script

Create `test-email.php` in Careygo root:

```php
<?php
require_once 'config/database.php';
require_once 'config/jwt.php';  // Loads .env
require_once 'lib/email.php';

$emailService = new EmailService();

echo "🧪 Testing Email Configuration\n";
echo "════════════════════════════════\n\n";

echo "SMTP_ENABLED: " . (getenv('SMTP_ENABLED') ? 'YES' : 'NO') . "\n";
echo "SMTP_HOST: " . getenv('SMTP_HOST') . "\n";
echo "SMTP_PORT: " . getenv('SMTP_PORT') . "\n";
echo "SMTP_USER: " . getenv('SMTP_USER') . "\n";
echo "\n";

// Test with YOUR email
$testEmail = 'your-email@gmail.com'; // CHANGE THIS
echo "Sending test email to: $testEmail\n\n";

$customer = [
    'full_name' => 'Test User',
    'email' => $testEmail,
];

$shipment = [
    'id' => 1,
    'tracking_no' => 'TEST20260416001',
    'pickup_name' => 'Sender Name',
    'pickup_address' => '123 Main St, Mumbai',
    'pickup_city' => 'Mumbai',
    'pickup_pincode' => '400001',
    'delivery_name' => 'Receiver Name',
    'delivery_address' => '456 Oak Ave, Delhi',
    'delivery_city' => 'Delhi',
    'delivery_pincode' => '110001',
    'service_type' => 'standard',
    'weight' => 1.5,
    'final_price' => 500,
    'estimated_delivery' => date('Y-m-d', strtotime('+3 days')),
    'created_at' => date('Y-m-d H:i:s'),
];

$r1 = $emailService->sendSenderConfirmation($customer, $shipment);
echo "1. Sender Confirmation: " . ($r1 ? '✅ SENT' : '❌ FAILED') . "\n";

$r2 = $emailService->sendAWBReceipt($customer, $shipment);
echo "2. AWB Receipt: " . ($r2 ? '✅ SENT' : '❌ FAILED') . "\n";

$receiver = ['name' => 'Receiver', 'phone' => '+91-9876543210'];
$r3 = $emailService->sendReceiverNotification($receiver, $shipment, $customer);
echo "3. Receiver Notification: " . ($r3 ? '✅ SENT' : '❌ FAILED') . "\n";

echo "\n✓ Check your inbox (and spam folder)!\n";
echo "✓ Check logs: /tmp/cgo_emails/\n";
?>
```

**Run it:**
```bash
php test-email.php
```

---

## 🚨 Before Going Live

- [ ] `.env` file created with SMTP settings
- [ ] PHPMailer installed (`vendor/` folder exists)
- [ ] Test email sent successfully
- [ ] Received in inbox (or spam folder)
- [ ] All 3 emails received (confirmation, receipt, receiver)
- [ ] Links in emails working
- [ ] `.env` not committed to Git
- [ ] File permissions correct (`chmod 600 .env`)

---

## 📞 Support

**If SMTP isn't working:**

1. Check logs: `/tmp/cgo_emails/`
2. Verify `.env` syntax (no spaces around `=`)
3. Verify credentials are correct
4. Check PHPMailer installed
5. Read: `SMTP_CONFIGURATION.md`
6. Email: support@careygo.in

---

**That's it! SMTP should now be working! 🎉**
