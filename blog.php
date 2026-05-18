<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/blog.php';

blog_ensure_table($pdo);

$pageTitle = 'Blog - Careygo Logistics';
$metaDescription = 'Read Careygo logistics insights, courier tips, ecommerce delivery guidance, and shipping updates.';
$metaKeywords = 'Careygo blog, courier tips, logistics, ecommerce shipping, delivery services';
$canonicalUrl = SITE_URL . '/blog';

$stmt = $pdo->query("
    SELECT id, title, slug, excerpt, content, featured_image, published_at
    FROM blogs
    WHERE status = 'published' AND (published_at IS NULL OR published_at <= NOW())
    ORDER BY COALESCE(published_at, created_at) DESC, id DESC
");
$blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<section class="blog-banner small-page-banner">
    <div class="container">
        <div class="small-page-banner-content">
            <h1>Blog</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Blog</li>
                </ol>
            </nav>
        </div>
    </div>
</section>

<section class="blog-list-section">
    <div class="container">
        <?php if (empty($blogs)): ?>
        <div class="blog-empty">
            <i class="bi bi-journal-text"></i>
            <h2>No blogs published yet</h2>
            <p>Fresh Careygo updates will appear here soon.</p>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($blogs as $blog): ?>
            <div class="col-md-6 col-lg-4">
                <article class="blog-card">
                    <a class="blog-card-image" href="<?= htmlspecialchars(blog_url($blog['slug'])) ?>">
                        <img src="<?= htmlspecialchars(blog_image_url($blog['featured_image'])) ?>" alt="<?= htmlspecialchars($blog['title']) ?>">
                    </a>
                    <div class="blog-card-body">
                        <?php if (!empty($blog['published_at'])): ?>
                        <div class="blog-card-date"><i class="bi bi-calendar3"></i><?= date('d M Y', strtotime($blog['published_at'])) ?></div>
                        <?php endif; ?>
                        <h2><a href="<?= htmlspecialchars(blog_url($blog['slug'])) ?>"><?= htmlspecialchars($blog['title']) ?></a></h2>
                        <p><?= htmlspecialchars(blog_excerpt($blog['excerpt'], $blog['content'], 130)) ?></p>
                        <a class="blog-read-more" href="<?= htmlspecialchars(blog_url($blog['slug'])) ?>">
                            Read More
                            <span><i class="bi bi-arrow-up-right"></i></span>
                        </a>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
