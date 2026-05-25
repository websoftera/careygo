<?php
require_once __DIR__ . '/config/jwt.php';
require_once __DIR__ . '/lib/blog.php';

header('Content-Type: application/xml; charset=UTF-8');

$baseUrl = rtrim(SITE_URL, '/');
$today = date('Y-m-d');
$urls = [
    ['loc' => $baseUrl . '/', 'lastmod' => $today, 'priority' => '1.0', 'changefreq' => 'weekly'],
    ['loc' => $baseUrl . '/public-tracking.php', 'lastmod' => $today, 'priority' => '0.8', 'changefreq' => 'weekly'],
    ['loc' => $baseUrl . '/rate-calculator.php', 'lastmod' => $today, 'priority' => '0.8', 'changefreq' => 'weekly'],
    ['loc' => $baseUrl . '/blog', 'lastmod' => $today, 'priority' => '0.7', 'changefreq' => 'weekly'],
];

try {
    $pdo = new PDO(
        "mysql:host=" . _cfg('CGO_DB_HOST', '127.0.0.1') . ";dbname=" . _cfg('CGO_DB_NAME', 'u728317772_caryego') . ";charset=utf8mb4",
        _cfg('CGO_DB_USER', 'u728317772_caryego'),
        _cfg('CGO_DB_PASS', '*IdjJpb9'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    blog_ensure_table($pdo);
    $stmt = $pdo->query("
        SELECT slug, COALESCE(updated_at, published_at, created_at) AS lastmod
        FROM blogs
        WHERE status = 'published' AND (published_at IS NULL OR published_at <= NOW())
        ORDER BY COALESCE(published_at, created_at) DESC
        LIMIT 500
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $blog) {
        $urls[] = [
            'loc' => $baseUrl . '/' . blog_url($blog['slug']),
            'lastmod' => date('Y-m-d', strtotime($blog['lastmod'] ?: $today)),
            'priority' => '0.6',
            'changefreq' => 'monthly',
        ];
    }
} catch (Exception $e) {}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= htmlspecialchars($url['loc'], ENT_XML1) ?></loc>
        <lastmod><?= htmlspecialchars($url['lastmod'], ENT_XML1) ?></lastmod>
        <changefreq><?= htmlspecialchars($url['changefreq'], ENT_XML1) ?></changefreq>
        <priority><?= htmlspecialchars($url['priority'], ENT_XML1) ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
