<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/includes/middleware.php';

$pageTitle  = 'Customers';
$activePage = 'customers';

$filter = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$where  = "WHERE role = 'customer'";
$params = [];
if ($filter && in_array($filter, ['pending','approved','rejected'])) {
    $where .= " AND status = ?";
    $params[] = $filter;
}
if ($search) {
    $where .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR company_name LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC");
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts
$counts = $pdo->query("SELECT status, COUNT(*) as cnt FROM users WHERE role='customer' GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$total  = array_sum($counts);

require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h4>Customers</h4>
        <p>Manage customer accounts and approvals</p>
    </div>
</div>

<!-- Stats pills -->
<div class="d-flex gap-2 flex-wrap mb-3">
    <a href="customers.php" class="btn <?= !$filter ? 'btn-primary-admin' : 'btn-outline-admin' ?>" style="font-size:12px;padding:6px 14px;">
        All <span class="ms-1 badge bg-secondary" style="font-size:10px;"><?= $total ?></span>
    </a>
    <a href="customers.php?status=pending" class="btn <?= $filter==='pending' ? 'btn-primary-admin' : 'btn-outline-admin' ?>" style="font-size:12px;padding:6px 14px;">
        Pending <span class="ms-1 badge bg-warning text-dark" style="font-size:10px;"><?= $counts['pending'] ?? 0 ?></span>
    </a>
    <a href="customers.php?status=approved" class="btn <?= $filter==='approved' ? 'btn-primary-admin' : 'btn-outline-admin' ?>" style="font-size:12px;padding:6px 14px;">
        Approved <span class="ms-1 badge bg-success" style="font-size:10px;"><?= $counts['approved'] ?? 0 ?></span>
    </a>
    <a href="customers.php?status=rejected" class="btn <?= $filter==='rejected' ? 'btn-primary-admin' : 'btn-outline-admin' ?>" style="font-size:12px;padding:6px 14px;">
        Rejected <span class="ms-1 badge bg-danger" style="font-size:10px;"><?= $counts['rejected'] ?? 0 ?></span>
    </a>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h6 class="admin-card-title"><i class="bi bi-people me-2"></i>Customer List</h6>
        <div class="filter-bar">
            <div class="filter-search">
                <i class="bi bi-search"></i>
                <input type="text" id="customerSearch" placeholder="Search name, email, phone…"
                       data-search-table="customersTable" value="<?= htmlspecialchars($search) ?>">
            </div>
        </div>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table" id="customersTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Company</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-people"></i><p>No customers found</p></div></td></tr>
                <?php else: ?>
                <?php foreach ($customers as $c): ?>
                <tr id="row_<?= $c['id'] ?>">
                    <td style="color:var(--muted);font-size:12px;"><?= $c['id'] ?></td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar-sm"><?= strtoupper(substr($c['full_name'], 0, 1)) ?></div>
                            <div class="user-cell-info">
                                <div class="user-cell-name"><?= htmlspecialchars($c['full_name']) ?></div>
                                <div class="user-cell-sub"><?= htmlspecialchars($c['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:13px;"><?= htmlspecialchars($c['phone']) ?></td>
                    <td style="font-size:13px;"><?= htmlspecialchars($c['company_name'] ?: '—') ?></td>
                    <td><span class="badge-status badge-<?= $c['status'] ?>" id="badge_<?= $c['id'] ?>"><?= ucfirst($c['status']) ?></span></td>
                    <td style="font-size:12px;color:var(--muted);"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                    <td>
                        <div class="d-flex gap-1 align-items-center">
                            <?php if ($c['status'] !== 'approved'): ?>
                            <button class="btn-success-admin" onclick="updateStatus(<?= $c['id'] ?>, 'approved')" title="Approve">
                                <i class="bi bi-check-lg"></i> Approve
                            </button>
                            <?php endif; ?>
                            <?php if ($c['status'] !== 'rejected'): ?>
                            <button class="btn-danger-admin" onclick="confirmAction('Reject this customer?', ()=>updateStatus(<?= $c['id'] ?>, 'rejected'))" title="Reject">
                                <i class="bi bi-x-lg"></i> Reject
                            </button>
                            <?php endif; ?>
                            <button class="btn-action" onclick="viewCustomer(<?= $c['id'] ?>)" title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Customer Detail Modal -->
<div class="modal fade admin-modal" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-person-circle me-2"></i>Customer Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="customerModalBody">
                <div class="text-center py-4"><span class="spinner-border text-primary"></span></div>
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(id, status) {
    fetch('<?= SITE_URL ?>/api/admin/customers.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id, status}),
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(`Customer ${status === 'approved' ? 'approved' : 'rejected'} successfully`, 'success');
            const badge = document.getElementById(`badge_${id}`);
            if (badge) { badge.className = `badge-status badge-${status}`; badge.textContent = status.charAt(0).toUpperCase() + status.slice(1); }
            // Refresh action buttons
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(data.message || 'Action failed', 'error');
        }
    })
    .catch(() => showToast('Network error', 'error'));
}

function viewCustomer(id) {
    const modal = new bootstrap.Modal(document.getElementById('customerModal'));
    document.getElementById('customerModalBody').innerHTML = '<div class="text-center py-4"><span class="spinner-border text-primary"></span></div>';
    modal.show();
    fetch(`<?= SITE_URL ?>/api/admin/customers.php?id=${id}`, {credentials: 'same-origin'})
    .then(r => r.json())
    .then(data => {
        if (!data.success) { document.getElementById('customerModalBody').innerHTML = '<p class="text-danger">Failed to load</p>'; return; }
        const c = data.customer;
        document.getElementById('customerModalBody').innerHTML = `
            <div class="text-center mb-3">
                <div class="user-avatar-sm mx-auto mb-2" style="width:52px;height:52px;font-size:20px;">${c.full_name.charAt(0).toUpperCase()}</div>
                <h6 class="mb-0">${escH(c.full_name)}</h6>
                <small class="text-muted">${escH(c.email)}</small>
            </div>
            <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-value">${escH(c.phone)}</span></div>
            <div class="detail-row"><span class="detail-label">Company</span><span class="detail-value">${escH(c.company_name||'—')}</span></div>
            <div class="detail-row"><span class="detail-label">Status</span><span class="detail-value"><span class="badge-status badge-${c.status}">${c.status}</span></span></div>
            <div class="detail-row"><span class="detail-label">Total Shipments</span><span class="detail-value"><strong>${c.total_shipments||0}</strong></span></div>
            <div class="detail-row"><span class="detail-label">Registered</span><span class="detail-value">${new Date(c.created_at).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'})}</span></div>
        `;
    });
}
function escH(s) { return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') : ''; }
</script>

<?php require_once 'includes/footer.php'; ?>
