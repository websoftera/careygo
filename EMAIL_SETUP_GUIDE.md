# Email Notification System Setup Guide

## Overview

The Careygo platform now sends automated email notifications for shipments:

1. **Booking Confirmation Email** → Sent to the customer (sender)
2. **AWB Receipt** → Sent to the customer with detailed invoice
3. **Package Notification** → Sent to the receiver with tracking details

---

## Features Implemented

### 1. Sender Notification (Booking Confirmation)
**Recipient:** Customer who made the booking
**Trigger:** Immediately after successful booking
**Contains:**
- Booking confirmation message
- Tracking number (unique identifier)
- Shipment details (weight, service type, price, ETA)
- Delivery address
- Direct link to track shipment
- Contact information for support

### 2. AWB Receipt (Invoice/Receipt)
**Recipient:** Customer (sender)
**Trigger:** Immediately after booking (same as confirmation)
**Contains:**
- Professional invoice-style document with Careygo branding
- Full sender and receiver details
- Shipment specifications (weight, pieces, description, declared value)
- Pricing breakdown (base price, discount, final amount)
- QR code for tracking
- Terms & Conditions
- Print/Save as PDF functionality

### 3. Receiver Notification
**Recipient:** Delivery contact person (optional)
**Trigger:** Immediately after booking (if email provided)
**Contains:**
- Notification that a package is coming
- Sender's name
- Tracking number
- Route information (from → to cities)
- Weight and contents
- Expected delivery date
- Link to public tracking page

---

## Configuration

### Email Service Settings

**File:** `config/jwt.php` or Environment Variables

The email system uses PHP's native `mail()` function. For production, configure these:

```bash
# Optional environment variables (if using external SMTP)
CGO_EMAIL_FROM=noreply@careygo.in
CGO_EMAIL_REPLY=support@careygo.in
```

### Default Settings

```php
From Email: noreply@careygo.in
Reply-To:   support@careygo.in
Admin Email: admin@careygo.in (from config/jwt.php)
```

---

## Implementation Details

### Email Library
**File:** `lib/email.php`

**Class:** `EmailService`

**Methods:**
```php
// Send booking confirmation to sender
sendSenderConfirmation(array $customer, array $shipment): bool

// Send AWB receipt to sender
sendAWBReceipt(array $customer, array $shipment): bool

// Send notification to receiver
sendReceiverNotification(array $receiver, array $shipment, array $customer): bool
```

### Integration Point
**File:** `api/shipments.php` (lines ~155-185)

After successful shipment creation, the system:
1. Fetches customer details from database
2. Initializes EmailService
3. Sends three emails with error handling
4. Continues processing even if emails fail (graceful degradation)

### Receiver Email Field
**New form field in:** `customer/new-booking.php`

The delivery address form now includes an optional email field:
- Label: "Email Address (for AWB notification)"
- Placeholder: "receiver@example.com"
- Optional (not required)
- Help text: "Receiver will get a notification with tracking details"

The email is captured and passed to the API:
```javascript
delivery_email: state.delivery.email || ''
```

---

## Testing Email Functionality

### Local/Development Environment

Emails are logged to a temporary directory for testing:

**Location:** `{sys_temp_dir}/cgo_emails/`

**Format:** Text files with timestamp and recipient

**Example log file:** `2026-04-16_14-30-45_a1b2c3d4e5f6g7h8.txt`

This allows testing without sending real emails.

### Production Environment

Emails are sent via PHP's `mail()` function. Ensure:

1. **PHP mail() is configured** in `php.ini`:
   ```ini
   sendmail_path = "/usr/sbin/sendmail -t -i"
   ; or for SMTP:
   SMTP = "smtp.gmail.com"
   smtp_port = 587
   ```

2. **Server allows outbound mail** (firewall/hosting restrictions)

3. **Sender domain is configured** in DNS:
   - SPF records
   - DKIM records
   - DMARC policy

### Enhanced Email Setup (Optional)

For better reliability, consider using a dedicated email library:

**PHPMailer** (Recommended)
```bash
composer require phpmailer/phpmailer
```

**Update in `lib/email.php`:**
```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

private function send(string $to, string $toName, string $subject, string $htmlBody): bool
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USER'] ?? 'your-email@gmail.com';
    $mail->Password = $_ENV['SMTP_PASS'] ?? 'your-app-password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom($this->from, $this->fromName);
    $mail->addAddress($to, $toName);
    $mail->addReplyTo($this->replyTo);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $htmlBody;

    return $mail->send();
}
```

---

## Email Templates

### Template Structure

Each email template includes:
- **Header:** Careygo branding with gradient background
- **Content:** Organized sections with icons
- **Status indicators:** Color-coded status badges
- **Call-to-actions:** Buttons linking to tracking/actions
- **Footer:** Support contact and legal info
- **Mobile responsive:** Optimized for all screen sizes

### Customization

Edit email templates in `lib/email.php`:

**Sender Confirmation:** `buildSenderEmail()` method (lines ~40-115)
**Receiver Notification:** `buildReceiverNotification()` method (lines ~122-196)
**AWB Receipt:** `buildReceiptEmail()` method (lines ~203-450)

#### Example: Change email sender name
```php
private $fromName = 'Careygo Logistics'; // Change this
```

#### Example: Change colors/branding
```html
/* In email templates, replace these values: */
--primary: #001A93;        /* Careygo blue */
--bg: #f0f2f9;             /* Light background */
--border: #e4e7f0;         /* Border color */
```

---

## Email Content Examples

### Booking Confirmation Email
- Tracking number prominently displayed
- 4-hour TAT (Turn Around Time)
- Delivery details
- Price breakdown
- Track now button
- Support contact info

### AWB Receipt
- Professional invoice layout
- Sender/receiver addresses
- Service type and weight
- Price details with discount
- QR code (auto-generated)
- Print functionality
- Terms & conditions

### Receiver Notification
- Package incoming notification
- Sender identification
- Route information
- Expected delivery date
- Public tracking link
- No sensitive financial details

---

## Public Tracking Page

**URL:** `{domain}/public-tracking.php?tracking=CGO20260416XXXXXXXX`

Features:
- ✅ No authentication required
- ✅ Shows shipment status
- ✅ Live tracking timeline
- ✅ DTDC integration (if AWB linked)
- ✅ Mobile responsive
- ✅ Status badges with color coding

This page is referenced in receiver notifications.

---

## Error Handling

### Email Sending Failures

The email system includes graceful error handling:

```php
try {
    $emailService->sendSenderConfirmation($customer, $shipment);
    // ... other emails ...
} catch (Exception $e) {
    // Logs error but doesn't fail the booking
    @error_log('Email sending failed: ' . $e->getMessage());
}
```

**Result:** Shipment is still created successfully even if email fails

**Logs:** Errors logged to PHP error log for monitoring

### Missing Receiver Email

If receiver email is not provided:
- Receiver notification is skipped silently
- Sender confirmation & AWB receipt are still sent
- No errors are raised

---

## Monitoring & Debugging

### Check Email Logs

In development:
```bash
# View email logs (local testing)
ls -la /tmp/cgo_emails/
cat /tmp/cgo_emails/2026-04-16_*.txt
```

### Check PHP Error Log

In production:
```bash
# Monitor for email errors
tail -f /var/log/php-fpm.log | grep "Email sending failed"
```

### Test Email Sending

Create a test script:

```php
<?php
require_once 'config/database.php';
require_once 'lib/email.php';

$emailService = new EmailService();

$testCustomer = [
    'full_name' => 'Test User',
    'email' => 'your-email@example.com',
];

$testShipment = [
    'id' => 1,
    'tracking_no' => 'CGO20260416TEST001',
    'pickup_name' => 'Sender Name',
    'delivery_name' => 'Receiver Name',
    'delivery_address' => '123 Main St',
    'delivery_city' => 'Mumbai',
    'delivery_pincode' => '400001',
    'service_type' => 'standard',
    'weight' => 1.5,
    'final_price' => 500,
    'estimated_delivery' => date('Y-m-d', strtotime('+3 days')),
    'created_at' => date('Y-m-d H:i:s'),
];

$result = $emailService->sendSenderConfirmation($testCustomer, $testShipment);
echo $result ? 'Email sent!' : 'Email failed!';
?>
```

---

## Troubleshooting

### Issue: Emails not sending in production

**Solution:**
1. Check `php.ini` sendmail configuration
2. Verify server allows outbound connections
3. Check mail server logs: `maillog`, `mail.log`
4. Ensure DNS records (SPF, DKIM) are configured
5. Test with:
   ```bash
   echo "Test email" | mail -s "Test" user@example.com
   ```

### Issue: Emails marked as spam

**Solution:**
1. Configure SPF record:
   ```dns
   v=spf1 mx -all
   ```
2. Add DKIM signature to outgoing emails
3. Use proper Reply-To header
4. Include unsubscribe information (for bulk emails)

### Issue: Receiver email field not capturing

**Solution:**
1. Verify JavaScript updates in `delivery.js` are loaded
2. Check browser console for JavaScript errors
3. Verify form field ID is `delivery_email`
4. Test with:
   ```javascript
   console.log(state.delivery.email);
   ```

### Issue: Email template looks broken

**Solution:**
1. Test in Gmail first (most compatible)
2. Add fallback fonts in CSS
3. Ensure all images are HTTPS
4. Use inline CSS (avoid `<style>` tags in email HTML)
5. Test with Litmus or Email on Acid

---

## GDPR & Privacy Compliance

### Data Retention

Implement a retention policy:

```php
// Delete old email logs (optional)
// In a scheduled cron job:
$logsDir = sys_get_temp_dir() . '/cgo_emails/';
foreach (glob($logsDir . '*.txt') as $file) {
    if (time() - filemtime($file) > 90 * 24 * 3600) { // 90 days
        unlink($file);
    }
}
```

### Privacy Notice

Add to receiver notification email:
> "Your email was obtained from the shipment sender. If you didn't expect this email, please contact support."

### Unsubscribe Options

For future enhancements, consider adding:
- Opt-out link in notification emails
- Email preferences in customer account
- "Do not email" flag in addresses table

---

## Performance Considerations

### Email Queue (Future Enhancement)

For high volume, implement a queue system:

```php
// Save to queue table instead of sending immediately
INSERT INTO email_queue (recipient, subject, body, status)
VALUES (?, ?, ?, 'pending');

// Use cron job to process queue:
php artisan queue:work
```

### Current Implementation

- ✅ Synchronous (immediate sending)
- ✅ Non-blocking (errors don't fail bookings)
- ⚠️ May slow down booking if SMTP is slow
- ✓ Suitable for < 1000 bookings/day

### Optimization Tips

1. Use local mail server (faster than SMTP)
2. Implement background job queue for > 1000/day
3. Cache DNS lookups: `resolv.conf`
4. Monitor mail queue size

---

## Support & Maintenance

### Update Email Content

To change email template colors, text, or layout:

1. Edit the respective method in `lib/email.php`
2. Update inline CSS and HTML
3. Test with sample data
4. Verify in multiple email clients

### Add New Email Type

To add a new email notification:

```php
public function sendCustomNotification(array $recipient, array $data): bool {
    $to = $recipient['email'];
    $subject = "Your notification";
    $body = $this->buildCustomEmail($data);
    return $this->send($to, $recipient['name'], $subject, $body);
}

private function buildCustomEmail(array $data): string {
    // Return HTML email template
}
```

### Contact Information

For email setup support:
- Email: support@careygo.in
- Phone: +91-98502-96178
- Hours: 9 AM - 6 PM IST

---

## Checklist for Production Deployment

- [ ] Configure `CGO_EMAIL_FROM` and `CGO_EMAIL_REPLY` in environment
- [ ] Update admin email in `config/jwt.php`
- [ ] Test email sending with real addresses
- [ ] Verify SPF/DKIM DNS records are configured
- [ ] Monitor `/var/log/mail.log` for issues
- [ ] Set up monitoring for failed bookings without emails
- [ ] Train support team on troubleshooting
- [ ] Document contact/support email in booking confirmations
- [ ] Create backup email account for sender
- [ ] Set up rate limiting to prevent spam

---

**Last Updated:** April 16, 2026
**Version:** 1.0
