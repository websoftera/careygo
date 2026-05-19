<?php
/**
 * Home banner helpers for public display and admin management.
 */

function banner_ensure_table(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS home_banners (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            eyebrow VARCHAR(120) DEFAULT NULL,
            title VARCHAR(180) NOT NULL,
            button_text VARCHAR(80) DEFAULT NULL,
            button_url VARCHAR(255) DEFAULT NULL,
            image_path VARCHAR(255) DEFAULT NULL,
            status ENUM('draft','published') NOT NULL DEFAULT 'published',
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_home_banners_status_sort (status, sort_order, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $done = true;
}

function banner_default(): array
{
    return [
        'eyebrow' => 'Plan, Transport and Focus',
        'title' => 'Logistics Solutions to Help Business',
        'button_text' => 'Connect With Us',
        'button_url' => '#enquiryModal',
        'image_path' => 'assets/images/Main-banner-1.jpg',
    ];
}

function banner_current(PDO $pdo): array
{
    banner_ensure_table($pdo);

    $stmt = $pdo->query("
        SELECT eyebrow, title, button_text, button_url, image_path
        FROM home_banners
        WHERE status = 'published'
        ORDER BY sort_order ASC, id DESC
        LIMIT 1
    ");

    $banner = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$banner) {
        return banner_default();
    }

    return array_merge(banner_default(), array_filter($banner, static fn($value) => $value !== null && $value !== ''));
}

function banner_published(PDO $pdo): array
{
    banner_ensure_table($pdo);

    $stmt = $pdo->query("
        SELECT eyebrow, title, button_text, button_url, image_path
        FROM home_banners
        WHERE status = 'published'
        ORDER BY sort_order ASC, id DESC
    ");

    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$banners) {
        return [banner_default()];
    }

    return array_map(
        static fn(array $banner): array => array_merge(banner_default(), array_filter($banner, static fn($value) => $value !== null && $value !== '')),
        $banners
    );
}

function banner_image_url(?string $path): string
{
    return $path ?: 'assets/images/Main-banner-1.jpg';
}

function banner_is_modal_url(string $url): bool
{
    return trim($url) === '#enquiryModal';
}

function banner_clean_url(string $url): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (str_starts_with($url, '#') || str_starts_with($url, '/') || preg_match('/^(https?:|mailto:|tel:)/i', $url)) {
        return $url;
    }
    if (preg_match('/^[a-z0-9][a-z0-9._\/?#=&%-]*$/i', $url)) {
        return $url;
    }
    return '';
}

function banner_save_uploaded_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Banner image upload failed. Please try again.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Banner image must be 5 MB or smaller.');
    }

    $tmp = $file['tmp_name'] ?? '';
    $info = $tmp ? @getimagesize($tmp) : false;
    if ($info === false) {
        throw new RuntimeException('Please upload a valid banner image.');
    }

    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];
    $ext = $allowed[$info[2]] ?? null;
    if (!$ext) {
        throw new RuntimeException('Only JPG, PNG, and WEBP banner images are allowed.');
    }

    $dir = __DIR__ . '/../assets/images/banners';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Unable to create banner image folder.');
    }

    $name = 'banner-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('Unable to save uploaded banner image.');
    }

    return 'assets/images/banners/' . $name;
}

function banner_delete_image(?string $path): void
{
    if (!$path || !str_starts_with($path, 'assets/images/banners/')) {
        return;
    }

    $fullPath = __DIR__ . '/../' . $path;
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}
