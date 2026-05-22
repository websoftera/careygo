<?php
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/email.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

function form_value(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function form_response(bool $success, string $message, int $status = 200): void
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

$formType = form_value('form_type');
$name = form_value('name');
$email = form_value('email');
$phone = form_value('phone');
$service = form_value('service');
$message = form_value('message');

if (!in_array($formType, ['contact', 'enquiry'], true)) {
    form_response(false, 'Invalid form type.', 422);
}

if (!preg_match("/^[A-Za-z][A-Za-z\s.'-]{1,59}$/", $name)) {
    form_response(false, 'Please enter a valid name.', 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    form_response(false, 'Please enter a valid email address.', 422);
}

$phonePattern = $formType === 'contact'
    ? '/^(?:\+91[\s-]?)?[6-9][0-9]{9}$/'
    : '/^[6-9][0-9]{9}$/';

if (!preg_match($phonePattern, $phone)) {
    form_response(false, 'Please enter a valid phone number.', 422);
}

if ($service === '') {
    form_response(false, 'Please select a service type.', 422);
}

if (strlen($message) < 10) {
    form_response(false, 'Please add at least 10 characters.', 422);
}

$formLabel = $formType === 'contact' ? 'Contact Us' : 'Connect With Us';
$subject = $formType === 'contact'
    ? 'Careygo Contact Us Form Submission - ' . $name
    : 'Careygo Connect With Us Enquiry - ' . $name;

$submittedAt = date('d M Y, h:i A');
$safeName = h($name);
$safeEmail = h($email);
$safePhone = h($phone);
$safeService = h($service);
$safeMessage = nl2br(h($message));

$body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{$subject}</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,sans-serif;color:#172033;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6fb;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e3e8f2;">
                    <tr>
                        <td style="background:#001A93;color:#ffffff;padding:18px 22px;">
                            <h2 style="margin:0;font-size:20px;line-height:1.3;">{$formLabel} Form Submission</h2>
                            <p style="margin:6px 0 0;font-size:13px;">Submitted on {$submittedAt}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px;line-height:1.5;">
                                <tr><td style="padding:8px 0;color:#647084;width:140px;">Name</td><td style="padding:8px 0;font-weight:700;">{$safeName}</td></tr>
                                <tr><td style="padding:8px 0;color:#647084;">Email</td><td style="padding:8px 0;"><a href="mailto:{$safeEmail}" style="color:#001A93;">{$safeEmail}</a></td></tr>
                                <tr><td style="padding:8px 0;color:#647084;">Phone</td><td style="padding:8px 0;"><a href="tel:{$safePhone}" style="color:#001A93;">{$safePhone}</a></td></tr>
                                <tr><td style="padding:8px 0;color:#647084;">Service</td><td style="padding:8px 0;">{$safeService}</td></tr>
                            </table>
                            <div style="margin-top:18px;padding:16px;background:#f7f9fd;border-radius:10px;border:1px solid #e6eaf2;">
                                <div style="font-size:13px;color:#647084;margin-bottom:6px;">Message</div>
                                <div style="font-size:15px;line-height:1.55;">{$safeMessage}</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

$recipients = [
    'info@careygo.in' => 'Careygo Team',
    'gaikwadkomalgaikwad2000@gmail.com' => 'Careygo Team',
];

$emailService = new EmailService();
$sent = false;

foreach ($recipients as $recipientEmail => $recipientName) {
    $sent = $emailService->sendFormNotification($recipientEmail, $recipientName, $subject, $body) || $sent;
}

if (!$sent) {
    form_response(false, 'Unable to send email right now. Please try again later.', 500);
}

form_response(true, 'Thank you. Our team will contact you shortly.');
