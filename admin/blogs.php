<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/blog.php';
require_once __DIR__ . '/includes/middleware.php';

blog_ensure_table($pdo);

$pageTitle  = 'Blog Management';
$activePage = 'blogs';
$message = '';
$error = '';
$formData = [];
$csrfToken = csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action !== 'delete') {
        $formData = $_POST;
    }

    try {
        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            throw new RuntimeException('Invalid request. Please refresh and try again.');
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT featured_image FROM blogs WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $oldImage = $stmt->fetchColumn();
            $pdo->prepare('DELETE FROM blogs WHERE id = ?')->execute([$id]);
            if ($oldImage && str_starts_with($oldImage, 'assets/images/blogs/')) {
                $oldPath = __DIR__ . '/../' . $oldImage;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $message = 'Blog deleted successfully.';
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $content = blog_clean_content($_POST['content'] ?? '');
            if ($title === '' || $content === '') {
                throw new RuntimeException('Title and content are required.');
            }

            $publishedAt = trim($_POST['published_at'] ?? '');
            $publishedAt = $publishedAt !== '' ? date('Y-m-d H:i:s', strtotime($publishedAt)) : null;
            $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
            if ($status === 'published' && !$publishedAt) {
                $publishedAt = date('Y-m-d H:i:s');
            }

            $imagePath = null;
            if (!empty($_FILES['featured_image']['name'])) {
                $imagePath = blog_save_uploaded_image($_FILES['featured_image']);
            }

            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT featured_image FROM blogs WHERE id = ? LIMIT 1');
                $stmt->execute([$id]);
                $currentImage = $stmt->fetchColumn();
                $slug = blog_unique_slug($pdo, $title, $id);

                $sql = "
                    UPDATE blogs
                    SET title = ?, slug = ?, excerpt = ?, content = ?, author_name = ?,
                        meta_title = ?, meta_description = ?, meta_keywords = ?,
                        status = ?, published_at = ?" . ($imagePath ? ', featured_image = ?' : '') . "
                    WHERE id = ?
                ";
                $params = [
                    $title,
                    $slug,
                    trim($_POST['excerpt'] ?? ''),
                    $content,
                    trim($_POST['author_name'] ?? ''),
                    trim($_POST['meta_title'] ?? ''),
                    trim($_POST['meta_description'] ?? ''),
                    trim($_POST['meta_keywords'] ?? ''),
                    $status,
                    $publishedAt,
                ];
                if ($imagePath) {
                    $params[] = $imagePath;
                }
                $params[] = $id;
                $pdo->prepare($sql)->execute($params);

                if ($imagePath && $currentImage && str_starts_with($currentImage, 'assets/images/blogs/')) {
                    $oldPath = __DIR__ . '/../' . $currentImage;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $message = 'Blog updated successfully.';
            } else {
                $slug = blog_unique_slug($pdo, $title);
                $stmt = $pdo->prepare("
                    INSERT INTO blogs
                    (title, slug, excerpt, content, featured_image, author_name, meta_title, meta_description, meta_keywords, status, published_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $title,
                    $slug,
                    trim($_POST['excerpt'] ?? ''),
                    $content,
                    $imagePath,
                    trim($_POST['author_name'] ?? ''),
                    trim($_POST['meta_title'] ?? ''),
                    trim($_POST['meta_description'] ?? ''),
                    trim($_POST['meta_keywords'] ?? ''),
                    $status,
                    $publishedAt,
                ]);
                $message = 'Blog added successfully.';
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$editBlog = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM blogs WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editBlog = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($error && !empty($formData)) {
    $editBlog = array_merge($editBlog ?: [], [
        'id' => $formData['id'] ?? '',
        'title' => $formData['title'] ?? '',
        'slug' => '',
        'excerpt' => $formData['excerpt'] ?? '',
        'content' => blog_clean_content($formData['content'] ?? ''),
        'status' => $formData['status'] ?? 'draft',
        'published_at' => !empty($formData['published_at'])
            ? date('Y-m-d H:i:s', strtotime($formData['published_at']))
            : null,
        'meta_title' => $formData['meta_title'] ?? '',
        'meta_description' => $formData['meta_description'] ?? '',
        'meta_keywords' => $formData['meta_keywords'] ?? '',
        'author_name' => $formData['author_name'] ?? ($adminData['full_name'] ?? 'Careygo Team'),
    ]);
}

$isEditing = !empty($editBlog['id']);

$blogsPerPage = 25;
$blogPage = max(1, (int) ($_GET['blog_page'] ?? 1));
$totalBlogs = (int) $pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
$totalBlogPages = max(1, (int) ceil($totalBlogs / $blogsPerPage));
if ($blogPage > $totalBlogPages) {
    $blogPage = $totalBlogPages;
}
$blogOffset = ($blogPage - 1) * $blogsPerPage;

$blogsStmt = $pdo->prepare("
    SELECT id, title, slug, featured_image, status, published_at, created_at
    FROM blogs
    ORDER BY created_at DESC
    LIMIT :limit OFFSET :offset
");
$blogsStmt->bindValue(':limit', $blogsPerPage, PDO::PARAM_INT);
$blogsStmt->bindValue(':offset', $blogOffset, PDO::PARAM_INT);
$blogsStmt->execute();
$blogs = $blogsStmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Blog Management</h4>
        <p>Add, edit, publish, and optimize blog articles for the website.</p>
    </div>
    <?php if ($isEditing): ?>
    <a href="blogs.php" class="btn-outline-admin"><i class="bi bi-plus-lg"></i> Add New Blog</a>
    <?php endif; ?>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title"><i class="bi bi-pencil-square me-2 text-primary"></i><?= $isEditing ? 'Edit Blog' : 'Add Blog' ?></h6>
            </div>
            <div class="admin-card-body">
                <form method="POST" enctype="multipart/form-data" class="blog-admin-form">
                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= h((string) ($editBlog['id'] ?? '')) ?>">
                    <input type="hidden" name="action" value="save">

                    <div class="mb-3">
                        <label class="admin-form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="admin-form-control" name="title" maxlength="180" value="<?= h($editBlog['title'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="admin-form-label">Short Description</label>
                        <textarea class="admin-form-control" name="excerpt" rows="3" maxlength="500"><?= h($editBlog['excerpt'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="admin-form-label">Content <span class="text-danger">*</span></label>
                        <div class="blog-editor-toolbar" role="toolbar" aria-label="Blog formatting tools">
                            <button type="button" class="btn-action" data-editor-command="bold" title="Bold"><i class="bi bi-type-bold"></i></button>
                            <button type="button" class="btn-action" data-editor-command="insertUnorderedList" title="Bullet list"><i class="bi bi-list-ul"></i></button>
                            <button type="button" class="btn-action" data-editor-command="insertOrderedList" title="Numbered list"><i class="bi bi-list-ol"></i></button>
                            <button type="button" class="btn-action" data-editor-command="createLink" title="Add link"><i class="bi bi-link-45deg"></i></button>
                            <button type="button" class="btn-action" data-editor-command="unlink" title="Remove link"><i class="bi bi-link"></i></button>
                        </div>
                        <div class="blog-rich-editor" id="blogContentEditor" contenteditable="true" data-placeholder="Write blog content here..."><?= $editBlog['content'] ?? '' ?></div>
                        <textarea class="admin-form-control d-none" name="content" id="blogContentInput" required><?= h($editBlog['content'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="admin-form-label">Status</label>
                            <select class="admin-select" name="status">
                                <?php $status = $editBlog['status'] ?? 'draft'; ?>
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="admin-form-label">Published Date</label>
                            <input type="datetime-local" class="admin-form-control" name="published_at" value="<?= !empty($editBlog['published_at']) ? date('Y-m-d\TH:i', strtotime($editBlog['published_at'])) : '' ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="admin-form-label">Blog Image</label>
                        <input type="file" class="admin-form-control" name="featured_image" accept="image/jpeg,image/png,image/webp">
                        <?php if (!empty($editBlog['featured_image'])): ?>
                        <img src="../<?= h($editBlog['featured_image']) ?>" alt="" class="blog-admin-preview">
                        <?php endif; ?>
                    </div>

                    <hr>
                    <h6 class="admin-card-title mb-3">SEO Information</h6>
                    <div class="mb-3">
                        <label class="admin-form-label">Meta Title</label>
                        <input type="text" class="admin-form-control" name="meta_title" maxlength="180" value="<?= h($editBlog['meta_title'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="admin-form-label">Meta Description</label>
                        <textarea class="admin-form-control" name="meta_description" rows="2" maxlength="255"><?= h($editBlog['meta_description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="admin-form-label">Meta Keywords</label>
                        <input type="text" class="admin-form-control" name="meta_keywords" maxlength="255" value="<?= h($editBlog['meta_keywords'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="admin-form-label">Author Name</label>
                        <input type="text" class="admin-form-control" name="author_name" maxlength="120" value="<?= h($editBlog['author_name'] ?? ($adminData['full_name'] ?? 'Careygo Team')) ?>">
                    </div>

                    <button class="btn-primary-admin" type="submit"><i class="bi bi-check-lg"></i> <?= $isEditing ? 'Update Blog' : 'Save Blog' ?></button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title"><i class="bi bi-journal-text me-2 text-primary"></i>All Blogs</h6>
                <span class="blog-admin-count">Showing <?= count($blogs) ?> of <?= number_format($totalBlogs) ?></span>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Blog</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($blogs)): ?>
                        <tr><td colspan="4"><div class="empty-state"><i class="bi bi-journal-text"></i><p>No blogs added yet</p></div></td></tr>
                        <?php else: foreach ($blogs as $blog): ?>
                        <tr>
                            <td>
                                <div class="blog-admin-cell">
                                    <img src="../<?= h(blog_image_url($blog['featured_image'])) ?>" alt="">
                                    <div>
                                        <div class="user-cell-name"><?= h($blog['title']) ?></div>
                                        <div class="user-cell-sub"><?= h($blog['slug']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge-status badge-<?= $blog['status'] === 'published' ? 'approved' : 'pending' ?>"><?= h(ucfirst($blog['status'])) ?></span></td>
                            <td style="font-size:12px;color:var(--muted)"><?= $blog['published_at'] ? date('d M Y', strtotime($blog['published_at'])) : '-' ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a class="btn-action" href="../<?= h(blog_url($blog['slug'])) ?>" target="_blank" title="View"><i class="bi bi-eye"></i></a>
                                    <a class="btn-action" href="blogs.php?edit=<?= (int) $blog['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" onsubmit="return confirm('Delete this blog?');">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $blog['id'] ?>">
                                        <button class="btn-action danger" type="submit" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalBlogPages > 1): ?>
            <nav class="admin-blog-pagination" aria-label="Blog admin pagination">
                <a class="admin-blog-page-link <?= $blogPage <= 1 ? 'disabled' : '' ?>" href="blogs.php<?= $blogPage > 2 ? '?blog_page=' . ($blogPage - 1) : '' ?>">Previous</a>
                <?php for ($i = 1; $i <= $totalBlogPages; $i++): ?>
                <a class="admin-blog-page-link <?= $i === $blogPage ? 'active' : '' ?>" href="blogs.php<?= $i > 1 ? '?blog_page=' . $i : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a class="admin-blog-page-link <?= $blogPage >= $totalBlogPages ? 'disabled' : '' ?>" href="blogs.php?blog_page=<?= $blogPage + 1 ?>">Next</a>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.blog-admin-preview {
    width: 140px;
    height: 82px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--border);
    margin-top: 10px;
    display: block;
}
.blog-admin-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 220px;
}
.blog-admin-cell img {
    width: 58px;
    height: 44px;
    object-fit: cover;
    border-radius: 8px;
    background: #eef2f7;
    flex-shrink: 0;
}
.blog-admin-count {
    font-size: 12px;
    color: var(--muted);
    font-weight: 600;
}
.admin-blog-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 14px;
    border-top: 1px solid var(--border);
    flex-wrap: wrap;
}
.admin-blog-page-link {
    min-width: 34px;
    height: 34px;
    padding: 0 11px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: #fff;
    color: var(--primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
}
.admin-blog-page-link:hover,
.admin-blog-page-link.active {
    background: var(--primary);
    border-color: var(--primary);
    color: #fff;
}
.admin-blog-page-link.disabled {
    opacity: 0.45;
    pointer-events: none;
}
.blog-admin-form textarea {
    resize: vertical;
}
.blog-editor-toolbar {
    display: flex;
    gap: 6px;
    padding: 8px;
    border: 1.5px solid var(--border);
    border-bottom: 0;
    border-radius: 10px 10px 0 0;
    background: #f8f9fe;
}
.blog-rich-editor {
    min-height: 230px;
    padding: 12px;
    border: 1.5px solid var(--border);
    border-radius: 0 0 10px 10px;
    background: #fff;
    font-size: 13px;
    line-height: 1.7;
    outline: none;
}
.blog-rich-editor:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(0,26,147,0.08);
}
.blog-rich-editor:empty::before {
    content: attr(data-placeholder);
    color: var(--muted);
}
.blog-rich-editor ul,
.blog-rich-editor ol {
    padding-left: 22px;
    margin: 8px 0;
}
.blog-rich-editor a {
    color: var(--primary);
    text-decoration: underline;
}
</style>

<script>
const blogEditor = document.getElementById('blogContentEditor');
const blogContentInput = document.getElementById('blogContentInput');

function syncBlogEditor() {
    if (blogEditor && blogContentInput) {
        blogContentInput.value = blogEditor.innerHTML.trim();
    }
}

if (blogEditor && blogContentInput) {
    document.querySelectorAll('[data-editor-command]').forEach((button) => {
        button.addEventListener('click', () => {
            blogEditor.focus();
            const command = button.dataset.editorCommand;
            if (command === 'createLink') {
                const url = prompt('Enter link URL');
                if (!url) return;
                const safeUrl = /^(https?:\/\/|mailto:|tel:)/i.test(url) ? url : `https://${url}`;
                document.execCommand(command, false, safeUrl);
            } else {
                document.execCommand(command, false, null);
            }
            syncBlogEditor();
        });
    });

    blogEditor.addEventListener('input', syncBlogEditor);
    blogEditor.closest('form').addEventListener('submit', syncBlogEditor);
}
</script>

<?php require_once 'includes/footer.php'; ?>
