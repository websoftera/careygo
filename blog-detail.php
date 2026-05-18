<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/blog.php';

blog_ensure_table($pdo);

$slug = trim($_GET['slug'] ?? '');
if ($slug === '' && preg_match('~/blog/([^/?#]+)~', $_SERVER['REQUEST_URI'] ?? '', $matches)) {
    $slug = rawurldecode($matches[1]);
}
if ($slug === '') {
    header('Location: blog');
    exit;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM blogs
    WHERE slug = ? AND status = 'published' AND (published_at IS NULL OR published_at <= NOW())
    LIMIT 1
");
$stmt->execute([$slug]);
$blog = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$blog) {
    http_response_code(404);
    $pageTitle = 'Blog Not Found - Careygo Logistics';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <section class="blog-list-section">
        <div class="container">
            <div class="blog-empty">
                <i class="bi bi-exclamation-circle"></i>
                <h1>Blog not found</h1>
                <p>The blog you are looking for is not available.</p>
                <a class="blog-read-more" href="blog">Back to Blog</a>
            </div>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = ($blog['meta_title'] ?: $blog['title']) . ' - Careygo Logistics';
$metaDescription = $blog['meta_description'] ?: blog_excerpt($blog['excerpt'], $blog['content']);
$metaKeywords = $blog['meta_keywords'] ?: 'Careygo, logistics, courier, shipping';
$canonicalUrl = SITE_URL . '/' . blog_url($blog['slug']);
$metaImage = SITE_URL . '/' . blog_image_url($blog['featured_image']);

require_once __DIR__ . '/includes/header.php';
?>

<section class="blog-banner small-page-banner">
    <div class="container">
        <div class="small-page-banner-content">
            <h1><?= htmlspecialchars($blog['title']) ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="blog">Blog</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($blog['title']) ?></li>
                </ol>
            </nav>
        </div>
    </div>
</section>

<article class="blog-detail-section">
    <div class="container">
        <div class="blog-detail-wrap">
            <img class="blog-detail-image" src="<?= htmlspecialchars(blog_image_url($blog['featured_image'])) ?>" alt="<?= htmlspecialchars($blog['title']) ?>">
            <div class="blog-detail-meta">
                <?php if (!empty($blog['published_at'])): ?>
                <span><i class="bi bi-calendar3"></i> Published on <?= date('d M Y', strtotime($blog['published_at'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($blog['author_name'])): ?>
                <span><i class="bi bi-person"></i> <?= htmlspecialchars($blog['author_name']) ?></span>
                <?php endif; ?>
            </div>
            <h2><?= htmlspecialchars($blog['title']) ?></h2>
            <?php if (!empty($blog['excerpt'])): ?>
            <p class="blog-detail-intro"><?= htmlspecialchars($blog['excerpt']) ?></p>
            <?php endif; ?>
            <div class="blog-detail-content">
                <?= blog_render_content($blog['content']) ?>
            </div>

        </div>
    </div>
</article>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
