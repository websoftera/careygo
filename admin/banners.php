<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/banner.php';
require_once __DIR__ . '/includes/middleware.php';

banner_ensure_table($pdo);

$pageTitle = 'Home Banners';
$activePage = 'banners';
$message = '';
$error = '';
$bannerFormDefaults = [
    'id' => '',
    'eyebrow' => '',
    'title' => '',
    'button_text' => 'Connect With Us',
    'button_url' => '#enquiryModal',
    'hide_mobile_content' => 0,
    'status' => 'published',
    'sort_order' => 0,
    'image_path' => '',
];
$submittedBanner = null;

function banner_post_max_bytes(): int
{
    $value = trim((string) ini_get('post_max_size'));
    $unit = strtolower(substr($value, -1));
    $bytes = (int) $value;
    if ($unit === 'g') {
        return $bytes * 1024 * 1024 * 1024;
    }
    if ($unit === 'm') {
        return $bytes * 1024 * 1024;
    }
    if ($unit === 'k') {
        return $bytes * 1024;
    }
    return $bytes;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > banner_post_max_bytes()) {
            throw new RuntimeException('Upload is too large. Please choose an image under 5 MB and try again.');
        }

        $submittedBanner = [
            'id' => $_POST['id'] ?? '',
            'eyebrow' => $_POST['eyebrow'] ?? '',
            'title' => $_POST['title'] ?? '',
            'button_text' => $_POST['button_text'] ?? 'Connect With Us',
            'button_url' => $_POST['button_url'] ?? '#enquiryModal',
            'hide_mobile_content' => isset($_POST['hide_mobile_content']) ? 1 : 0,
            'status' => $_POST['status'] ?? 'published',
            'sort_order' => $_POST['sort_order'] ?? 0,
            'image_path' => '',
        ];

        if (!csrf_verify($_POST['csrf_token'] ?? '')) {
            throw new RuntimeException('Invalid request. Please refresh and try again.');
        }

        $action = $_POST['action'] ?? '';
        if ($action === 'reorder') {
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            if (!is_array($ids) || empty($ids)) {
                throw new RuntimeException('No banner order received.');
            }

            $stmt = $pdo->prepare('UPDATE home_banners SET sort_order = ? WHERE id = ?');
            foreach (array_values($ids) as $index => $id) {
                $stmt->execute([($index + 1) * 10, (int) $id]);
            }

            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                json_response(['success' => true, 'message' => 'Banner order updated.']);
            }
            $message = 'Banner order updated successfully.';
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT image_path FROM home_banners WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $oldImage = $stmt->fetchColumn();

            $pdo->prepare('DELETE FROM home_banners WHERE id = ?')->execute([$id]);
            banner_delete_image($oldImage ?: null);
            $message = 'Banner deleted successfully.';
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $eyebrow = trim($_POST['eyebrow'] ?? '');
            $buttonText = trim($_POST['button_text'] ?? '');
            $buttonUrl = banner_clean_url($_POST['button_url'] ?? '');
            $hideMobileContent = isset($_POST['hide_mobile_content']) ? 1 : 0;
            $status = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            $imagePath = null;

            if (!empty($_FILES['image']['name'])) {
                $imagePath = banner_save_uploaded_image($_FILES['image']);
            }

            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT image_path FROM home_banners WHERE id = ? LIMIT 1');
                $stmt->execute([$id]);
                $currentImage = $stmt->fetchColumn();

                $sql = "
                    UPDATE home_banners
                    SET eyebrow = ?, title = ?, button_text = ?, button_url = ?, hide_mobile_content = ?, status = ?, sort_order = ?" . ($imagePath ? ', image_path = ?' : '') . "
                    WHERE id = ?
                ";
                $params = [$eyebrow, $title, $buttonText, $buttonUrl, $hideMobileContent, $status, $sortOrder];
                if ($imagePath) {
                    $params[] = $imagePath;
                }
                $params[] = $id;
                $pdo->prepare($sql)->execute($params);

                if ($imagePath) {
                    banner_delete_image($currentImage ?: null);
                }
                $message = 'Banner updated successfully.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO home_banners (eyebrow, title, button_text, button_url, image_path, hide_mobile_content, status, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$eyebrow, $title, $buttonText, $buttonUrl, $imagePath, $hideMobileContent, $status, $sortOrder]);
                $message = 'Banner added successfully.';
            }
        }
    } catch (Exception $e) {
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
            json_response(['success' => false, 'message' => $e->getMessage()], 422);
        }
        $error = $e->getMessage();
    }
}

$editBanner = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM home_banners WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_GET['edit']]);
    $editBanner = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$bannerForm = array_merge($bannerFormDefaults, $editBanner ?: []);
if ($submittedBanner && $error) {
    $bannerForm = array_merge($bannerForm, $submittedBanner);
}

$csrfToken = csrf_token();

$banners = $pdo->query("
    SELECT id, eyebrow, title, button_text, button_url, image_path, hide_mobile_content, status, sort_order, created_at
    FROM home_banners
    ORDER BY sort_order ASC, id DESC
")->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Home Banners</h4>
        <p>Create, edit, publish, and delete the banner shown at the top of the home page.</p>
    </div>
    <?php if ($editBanner): ?>
    <a href="banners.php" class="btn-outline-admin"><i class="bi bi-plus-lg"></i> Add New Banner</a>
    <?php endif; ?>
</div>

<?php if ($message): ?><div class="alert alert-success"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title"><i class="bi bi-image me-2 text-primary"></i><?= $editBanner ? 'Edit Banner' : 'Add Banner' ?></h6>
            </div>
            <div class="admin-card-body">
                <form method="POST" enctype="multipart/form-data" class="banner-admin-form">
                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= h((string) ($bannerForm['id'] ?? '')) ?>">
                    <input type="hidden" name="action" value="save">

                    <div class="mb-3">
                        <label class="admin-form-label">Small Text</label>
                        <input type="text" class="admin-form-control" name="eyebrow" maxlength="120" value="<?= h((string) ($bannerForm['eyebrow'] ?? '')) ?>" placeholder="Plan, Transport and Focus">
                    </div>
                    <div class="mb-3">
                        <label class="admin-form-label">Main Title</label>
                        <input type="text" class="admin-form-control" name="title" maxlength="180" value="<?= h((string) ($bannerForm['title'] ?? '')) ?>" placeholder="Leave blank for image-only banner">
                    </div>
                    <div class="mt-3">
                        <label class="d-flex align-items-center gap-2" style="font-size:13px;font-weight:600;color:var(--text);">
                            <input type="checkbox" name="hide_mobile_content" value="1" <?= !empty($bannerForm['hide_mobile_content']) ? 'checked' : '' ?>>
                            Hide banner text/button on mobile view
                        </label>
                        <small class="d-block mt-1 text-muted">Desktop will still show the text. Mobile will show only the banner image.</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="admin-form-label">Button Text</label>
                            <input type="text" class="admin-form-control" name="button_text" maxlength="80" value="<?= h((string) ($bannerForm['button_text'] ?? 'Connect With Us')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="admin-form-label">Button Link</label>
                            <input type="text" class="admin-form-control" name="button_url" maxlength="255" value="<?= h((string) ($bannerForm['button_url'] ?? '#enquiryModal')) ?>">
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="admin-form-label">Status</label>
                            <?php $status = $bannerForm['status'] ?? 'published'; ?>
                            <select class="admin-select" name="status">
                                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="admin-form-label">Sort Order</label>
                            <input type="number" class="admin-form-control" name="sort_order" value="<?= h((string) ($bannerForm['sort_order'] ?? 0)) ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="admin-form-label">Banner Image</label>
                        <input type="file" class="admin-form-control" name="image" accept="image/jpeg,image/png,image/webp">
                        <small class="d-block mt-2 text-muted">Recommended wide image: 1600 x 600px. JPG, PNG, or WEBP up to 5 MB.</small>
                        <?php if (!empty($bannerForm['image_path'])): ?>
                        <img src="../<?= h($bannerForm['image_path']) ?>" alt="" class="banner-admin-preview">
                        <?php endif; ?>
                    </div>

                    <button class="btn-primary-admin mt-4" type="submit"><i class="bi bi-check-lg"></i> <?= $editBanner ? 'Update Banner' : 'Save Banner' ?></button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title"><i class="bi bi-images me-2 text-primary"></i>All Banners</h6>
                <?php if (!empty($banners)): ?>
                <span class="banner-sort-hint"><i class="bi bi-grip-vertical"></i> Drag rows to reorder</span>
                <?php endif; ?>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table banner-sort-table">
                    <thead>
                        <tr>
                            <th style="width:44px;"></th>
                            <th>Banner</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($banners)): ?>
                        <tr><td colspan="5"><div class="empty-state"><i class="bi bi-image"></i><p>No banners added yet</p></div></td></tr>
                        <?php else: foreach ($banners as $banner): ?>
                        <tr draggable="true" data-banner-id="<?= (int) $banner['id'] ?>">
                            <td class="banner-drag-cell"><button type="button" class="banner-drag-handle" title="Drag to reorder" aria-label="Drag to reorder"><i class="bi bi-grip-vertical"></i></button></td>
                            <td>
                                <div class="banner-admin-cell">
                                    <img src="../<?= h(banner_image_url($banner['image_path'])) ?>" alt="">
                                    <div>
                                        <div class="user-cell-name"><?= h((string) ($banner['title'] ?: 'Image-only banner')) ?></div>
                                        <div class="user-cell-sub"><?= h((string) ($banner['eyebrow'] ?: $banner['button_text'] ?: (!empty($banner['hide_mobile_content']) ? 'Mobile text hidden' : 'Home banner'))) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge-status badge-<?= $banner['status'] === 'published' ? 'approved' : 'pending' ?>"><?= h(ucfirst($banner['status'])) ?></span></td>
                            <td style="font-size:12px;color:var(--muted)" data-sort-order-cell><?= (int) $banner['sort_order'] ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a class="btn-action" href="../index.php" target="_blank" title="View Home"><i class="bi bi-eye"></i></a>
                                    <a class="btn-action" href="banners.php?edit=<?= (int) $banner['id'] ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" onsubmit="return confirm('Delete this banner?');">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $banner['id'] ?>">
                                        <button class="btn-action danger" type="submit" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.banner-sort-hint {
    color: var(--muted);
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
}
.banner-sort-table tbody tr {
    transition: background-color 0.15s ease, opacity 0.15s ease;
}
.banner-sort-table tbody tr.banner-row-dragging {
    opacity: 0.45;
}
.banner-sort-table tbody tr.banner-row-over {
    background: #eef3ff;
}
.banner-drag-cell {
    text-align: center;
    width: 44px;
}
.banner-drag-handle {
    align-items: center;
    background: #f1f4fb;
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--muted);
    cursor: grab;
    display: inline-flex;
    height: 30px;
    justify-content: center;
    width: 30px;
}
.banner-drag-handle:active {
    cursor: grabbing;
}
.banner-admin-preview {
    width: 220px;
    height: 92px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--border);
    margin-top: 10px;
    display: block;
}
.banner-admin-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 260px;
}
.banner-admin-cell img {
    width: 78px;
    height: 44px;
    object-fit: cover;
    border-radius: 8px;
    background: #eef2f7;
    flex-shrink: 0;
}
</style>

<script>
(function () {
    const form = document.querySelector('.banner-admin-form');
    if (!form) return;

    const storageKey = 'careygo_admin_banner_draft_' + (form.querySelector('[name="id"]')?.value || 'new');
    const fields = ['eyebrow', 'title', 'button_text', 'button_url', 'hide_mobile_content', 'status', 'sort_order'];

    function readDraft() {
        try {
            return JSON.parse(localStorage.getItem(storageKey) || '{}');
        } catch (e) {
            return {};
        }
    }

    function writeDraft() {
        const draft = {};
        fields.forEach((name) => {
            const field = form.querySelector(`[name="${name}"]`);
            if (!field) return;
            draft[name] = field.type === 'checkbox' ? field.checked : field.value;
        });
        try {
            localStorage.setItem(storageKey, JSON.stringify(draft));
        } catch (e) {}
    }

    function restoreDraft() {
        const draft = readDraft();
        fields.forEach((name) => {
            const field = form.querySelector(`[name="${name}"]`);
            if (!field || draft[name] === undefined) return;
            if (field.type === 'checkbox') {
                field.checked = !!draft[name];
                return;
            }
            if (name === 'title' || field.value === '' || field.value === 'Connect With Us' || field.value === '#enquiryModal') {
                field.value = draft[name];
            }
        });
    }

    restoreDraft();
    fields.forEach((name) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (!field) return;
        field.addEventListener('input', writeDraft);
        field.addEventListener('change', writeDraft);
    });
    form.addEventListener('submit', writeDraft);

    <?php if ($message): ?>
    try { localStorage.removeItem(storageKey); } catch (e) {}
    <?php endif; ?>
})();

(function () {
    const table = document.querySelector('.banner-sort-table');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    const csrf = document.querySelector('.banner-admin-form [name="csrf_token"]')?.value || '';
    let draggedRow = null;
    let saveTimer = null;

    function rows() {
        return Array.from(tbody.querySelectorAll('tr[data-banner-id]'));
    }

    function rowAfterPointer(y) {
        return rows()
            .filter((row) => row !== draggedRow)
            .reduce((closest, row) => {
                const box = row.getBoundingClientRect();
                const offset = y - box.top - (box.height / 2);
                if (offset < 0 && offset > closest.offset) {
                    return { offset, row };
                }
                return closest;
            }, { offset: Number.NEGATIVE_INFINITY, row: null }).row;
    }

    function syncOrderNumbers() {
        rows().forEach((row, index) => {
            const orderCell = row.querySelector('[data-sort-order-cell]');
            if (orderCell) orderCell.textContent = String((index + 1) * 10);
        });
    }

    function saveOrder() {
        const ids = rows().map((row) => row.dataset.bannerId);
        if (ids.length < 2) return;

        const body = new URLSearchParams();
        body.set('csrf_token', csrf);
        body.set('action', 'reorder');
        body.set('ids', JSON.stringify(ids));

        fetch('banners.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body,
            credentials: 'same-origin'
        })
        .then((response) => response.json())
        .then((data) => {
            if (data && data.success && window.showToast) {
                window.showToast('Banner order updated.', 'success');
            }
            if (!data || !data.success) {
                throw new Error(data?.message || 'Unable to update banner order.');
            }
        })
        .catch((error) => {
            if (window.showToast) {
                window.showToast(error.message || 'Unable to update banner order.', 'error');
            } else {
                alert(error.message || 'Unable to update banner order.');
            }
        });
    }

    rows().forEach((row) => {
        row.addEventListener('dragstart', (event) => {
            draggedRow = row;
            row.classList.add('banner-row-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', row.dataset.bannerId || '');
        });

        row.addEventListener('dragover', (event) => {
            event.preventDefault();
            const after = rowAfterPointer(event.clientY);
            rows().forEach((candidate) => candidate.classList.remove('banner-row-over'));
            if (after) after.classList.add('banner-row-over');
            if (!draggedRow) return;
            if (after) {
                tbody.insertBefore(draggedRow, after);
            } else {
                tbody.appendChild(draggedRow);
            }
        });

        row.addEventListener('dragend', () => {
            rows().forEach((candidate) => candidate.classList.remove('banner-row-over'));
            row.classList.remove('banner-row-dragging');
            draggedRow = null;
            syncOrderNumbers();
            clearTimeout(saveTimer);
            saveTimer = setTimeout(saveOrder, 200);
        });
    });
})();
</script>

<?php require_once 'includes/footer.php'; ?>
