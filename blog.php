<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/blog.php';

blog_ensure_table($pdo);

$pageTitle = 'Blog - Careygo Logistics';
$metaDescription = 'Read Careygo logistics insights, courier tips, ecommerce delivery guidance, and shipping updates.';
$metaKeywords = 'Careygo blog, courier tips, logistics, ecommerce shipping, delivery services';
$canonicalUrl = SITE_URL . '/blog';

$perPage = 20;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalBlogs = (int) $pdo->query("
    SELECT COUNT(*)
    FROM blogs
    WHERE status = 'published' AND (published_at IS NULL OR published_at <= NOW())
")->fetchColumn();
$totalPages = max(1, (int) ceil($totalBlogs / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("
    SELECT id, title, slug, excerpt, content, featured_image, published_at
    FROM blogs
    WHERE status = 'published' AND (published_at IS NULL OR published_at <= NOW())
    ORDER BY COALESCE(published_at, created_at) DESC, id DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
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
        <?php if ($totalPages > 1): ?>
        <nav class="blog-pagination" aria-label="Blog pagination">
            <a class="blog-page-link <?= $page <= 1 ? 'disabled' : '' ?>" href="blog<?= $page > 2 ? '?page=' . ($page - 1) : '' ?>">Previous</a>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="blog-page-link <?= $i === $page ? 'active' : '' ?>" href="blog<?= $i > 1 ? '?page=' . $i : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a class="blog-page-link <?= $page >= $totalPages ? 'disabled' : '' ?>" href="blog?page=<?= $page + 1 ?>">Next</a>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
