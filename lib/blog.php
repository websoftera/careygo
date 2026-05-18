<?php
/**
 * Blog helpers for public pages and admin management.
 */

function blog_ensure_table(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blogs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(180) NOT NULL,
            slug VARCHAR(220) NOT NULL,
            excerpt TEXT DEFAULT NULL,
            content MEDIUMTEXT NOT NULL,
            featured_image VARCHAR(255) DEFAULT NULL,
            author_name VARCHAR(120) DEFAULT NULL,
            meta_title VARCHAR(180) DEFAULT NULL,
            meta_description VARCHAR(255) DEFAULT NULL,
            meta_keywords VARCHAR(255) DEFAULT NULL,
            status ENUM('draft','published') NOT NULL DEFAULT 'draft',
            published_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_blogs_slug (slug),
            KEY idx_blogs_status_published (status, published_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $done = true;
}

function blog_slugify(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim((string) $slug, '-');
    return $slug !== '' ? substr($slug, 0, 200) : 'blog-post';
}

function blog_unique_slug(PDO $pdo, string $title, ?int $ignoreId = null, ?string $preferred = null): string
{
    $base = blog_slugify($preferred ?: $title);
    $slug = $base;
    $i = 2;

    while (true) {
        $sql = 'SELECT id FROM blogs WHERE slug = ?';
        $params = [$slug];
        if ($ignoreId) {
            $sql .= ' AND id != ?';
            $params[] = $ignoreId;
        }
        $stmt = $pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        if (!$stmt->fetchColumn()) {
            return $slug;
        }
        $slug = substr($base, 0, 190) . '-' . $i++;
    }
}

function blog_excerpt(?string $excerpt, string $content, int $length = 155): string
{
    $text = trim((string) ($excerpt ?: strip_tags($content)));
    $text = preg_replace('/\s+/', ' ', $text);
    if (strlen($text) <= $length) {
        return $text;
    }
    return rtrim(substr($text, 0, $length - 3)) . '...';
}

function blog_clean_content(string $content): string
{
    $content = trim($content);
    $content = preg_replace('/<\s*(script|style|iframe|object|embed)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $content);
    $content = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $content);
    $content = preg_replace('/\s+href\s*=\s*([\'"])\s*javascript:[^\'"]*\1/i', '', $content);

    $allowed = '<p><br><strong><b><em><i><u><a><ul><ol><li><h2><h3><h4>';
    return strip_tags($content, $allowed);
}

function blog_render_content(string $content): string
{
    $clean = blog_clean_content($content);
    $hasHtml = preg_match('/<[^>]+>/', $clean);
    return $hasHtml ? $clean : nl2br(htmlspecialchars($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function blog_image_url(?string $path): string
{
    return $path ?: 'assets/images/Courier Services.jpg';
}

function blog_url(string $slug): string
{
    return 'blog/' . rawurlencode($slug);
}

function blog_save_uploaded_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed. Please try again.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Image must be 5 MB or smaller.');
    }

    $tmp = $file['tmp_name'] ?? '';
    $info = $tmp ? @getimagesize($tmp) : false;
    if ($info === false) {
        throw new RuntimeException('Please upload a valid image file.');
    }

    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];
    $ext = $allowed[$info[2]] ?? null;
    if (!$ext) {
        throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
    }

    $dir = __DIR__ . '/../assets/images/blogs';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('Unable to create blog image folder.');
    }

    $name = 'blog-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('Unable to save uploaded image.');
    }

    return 'assets/images/blogs/' . $name;
}
