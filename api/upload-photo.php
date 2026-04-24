<?php
/**
 * POST /api/upload-photo.php
 * Upload address or parcel photo for a booking.
 * Returns: { success, file_id, file_url }
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

header('Content-Type: application/json');

$user = auth_user();
if (!$user || $user['role'] !== 'customer') {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$type = $_POST['type'] ?? ''; // 'address' or 'parcel'
if (!in_array($type, ['address', 'parcel'], true)) {
    json_response(['success' => false, 'message' => 'Invalid photo type.'], 422);
}

if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $errMsg = 'No file uploaded.';
    if (!empty($_FILES['photo'])) {
        $errCodes = [
            UPLOAD_ERR_INI_SIZE   => 'File too large (server limit).',
            UPLOAD_ERR_FORM_SIZE  => 'File too large.',
            UPLOAD_ERR_PARTIAL    => 'File only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp directory.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file.',
        ];
        $errMsg = $errCodes[$_FILES['photo']['error']] ?? 'Upload error.';
    }
    json_response(['success' => false, 'message' => $errMsg], 422);
}

$file     = $_FILES['photo'];
$maxSize  = 5 * 1024 * 1024; // 5 MB
$allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// Validate size
if ($file['size'] > $maxSize) {
    json_response(['success' => false, 'message' => 'File size must be under 5 MB.'], 422);
}

// Validate MIME type
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, $allowed, true)) {
    json_response(['success' => false, 'message' => 'Only JPEG, PNG, GIF, or WebP images are allowed.'], 422);
}

// Create upload directory
$uploadDir = __DIR__ . '/../uploads/booking-photos/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Generate unique filename
$ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
$fileName = 'booking_' . $type . '_' . $user['sub'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
$destPath = $uploadDir . $fileName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    json_response(['success' => false, 'message' => 'Failed to save uploaded file.'], 500);
}

// Return the file reference
$fileUrl = (defined('SITE_URL') ? SITE_URL : '') . '/uploads/booking-photos/' . $fileName;
json_response([
    'success'  => true,
    'file_id'  => $fileName,
    'file_url' => $fileUrl,
    'type'     => $type,
]);
