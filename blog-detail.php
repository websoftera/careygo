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

$relatedStmt = $pdo->prepare("
    SELECT title, slug, excerpt, content, featured_image, published_at
    FROM blogs
    WHERE id != ? AND status = 'published' AND (published_at IS NULL OR published_at <= NOW())
    ORDER BY COALESCE(published_at, created_at) DESC, id DESC
    LIMIT 6
");
$relatedStmt->execute([(int) $blog['id']]);
$relatedBlogs = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);

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
            <img class="blog-detail-image" src="<?= htmlspecialchars(blog_image_url($blog['featured_image'])) ?>" alt="<?= htmlspecialchars($blog['title']) ?>" decoding="async" fetchpriority="high">
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

<?php if (!empty($relatedBlogs)): ?>
<section class="related-blog-section">
    <div class="container">
        <div class="related-blog-header">
            <h2>Our Blogs</h2>
            <div class="related-blog-controls">
                <button type="button" data-related-slide="prev" onclick="return relatedBlogMove(-1)" aria-label="Previous blogs">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button type="button" data-related-slide="next" onclick="return relatedBlogMove(1)" aria-label="Next blogs">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="related-blog-viewport" id="relatedBlogSlider">
            <div class="related-blog-track">
                <?php foreach ($relatedBlogs as $related): ?>
                <div class="related-blog-slide">
                    <article class="related-blog-card">
                        <a class="related-blog-image" href="<?= htmlspecialchars(blog_url($related['slug'])) ?>">
                            <img src="<?= htmlspecialchars(blog_image_url($related['featured_image'])) ?>" alt="<?= htmlspecialchars($related['title']) ?>" loading="lazy" decoding="async">
                        </a>
                        <div class="related-blog-body">
                            <?php if (!empty($related['published_at'])): ?>
                            <div class="blog-card-date"><i class="bi bi-calendar3"></i><?= date('d M Y', strtotime($related['published_at'])) ?></div>
                            <?php endif; ?>
                            <h3><a href="<?= htmlspecialchars(blog_url($related['slug'])) ?>"><?= htmlspecialchars($related['title']) ?></a></h3>
                            <p><?= htmlspecialchars(blog_excerpt($related['excerpt'], $related['content'], 95)) ?></p>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<script>
function relatedBlogMove(direction) {
    const slider = document.getElementById('relatedBlogSlider');
    if (!slider) return false;
    const track = slider.querySelector('.related-blog-track');
    const slide = track ? track.querySelector('.related-blog-slide') : null;
    if (!slide) return false;
    const styles = window.getComputedStyle(track);
    let gap = parseFloat(styles.columnGap);
    if (Number.isNaN(gap)) gap = parseFloat(styles.gap);
    if (Number.isNaN(gap)) gap = 0;
    const step = slide.getBoundingClientRect().width + gap;
    slider.scrollTo({
        left: slider.scrollLeft + (step * direction),
        behavior: 'smooth'
    });
    return false;
}

function initRelatedBlogSlider() {
    const slider = document.getElementById('relatedBlogSlider');
    if (!slider) return;
    if (slider.dataset.sliderReady === '1') return;
    slider.dataset.sliderReady = '1';
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRelatedBlogSlider);
} else {
    initRelatedBlogSlider();
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
