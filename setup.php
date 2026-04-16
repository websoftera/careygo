<?php
/**
 * Careygo — One-time setup / database migration runner
 * Visit: http://localhost/careygo/setup.php
 * DELETE this file after setup is complete!
 */

// Simple access guard
define('SETUP_KEY', 'careygo_setup_2026');
if (($_GET['key'] ?? '') !== SETUP_KEY) {
    http_response_code(403);
    die('<h2 style="font-family:sans-serif;color:#c00">Access Denied. Append ?key=careygo_setup_2026 to the URL.</h2>');
}

require_once __DIR__ . '/config/database.php';

$results = [];

function runSql(PDO $pdo, string $label, string $sql): array {
    try {
        $pdo->exec($sql);
        return ['status' => 'ok', 'label' => $label, 'msg' => 'Success'];
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        $isWarning = strpos($msg,'already exists') !== false || strpos($msg,'Duplicate entry') !== false;
        return ['status' => $isWarning ? 'warn' : 'error', 'label' => $label, 'msg' => $msg];
    }
}

// ── Run both schema files in order ──
$schemaFiles = [
    'schema.sql'    => __DIR__ . '/database/schema.sql',
    'schema_v2.sql' => __DIR__ . '/database/schema_v2.sql',
];

// Optionally import 15k+ pincodes (pass &pincodes=1 to enable — takes ~10–30 s)
if (($_GET['pincodes'] ?? '') === '1') {
    $schemaFiles['pincodes.sql'] = __DIR__ . '/database/pincodes.sql';
}

foreach ($schemaFiles as $fileLabel => $filePath) {
    $raw = file_get_contents($filePath);
    if ($raw === false) {
        $results[] = ['status' => 'error', 'label' => $fileLabel, 'msg' => 'File not found: ' . $filePath];
        continue;
    }

    // Strip single-line comments (-- ...) and blank lines, then split on ;
    $sql = preg_replace('/^\s*--.*$/m', '', $raw);   // remove -- comment lines
    $sql = preg_replace('/\n{3,}/', "\n\n", $sql);   // collapse excess blank lines

    $statements = preg_split('/;\s*(\r\n|\n|\r)/s', $sql);

    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '' || strtoupper(preg_replace('/\s+/', ' ', $stmt)) === 'USE `CAREYGO`') continue;

        $label = '[' . $fileLabel . '] ' . preg_replace('/\s+/', ' ', $stmt);
        $label = strlen($label) > 100 ? substr($label, 0, 100) . '…' : $label;
        $results[] = runSql($pdo, $label, $stmt);
    }
}

// ── Seed / reset admin account with a PHP-generated hash ──────
$adminResult = ['status' => 'ok', 'label' => 'Admin account seed', 'msg' => ''];
try {
    $adminPassword = 'Admin@123';
    $adminHash     = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    $pdo->prepare("
        INSERT INTO users (full_name, email, phone, company_name, password_hash, role, status)
        VALUES ('Super Admin', 'admin@careygo.in', '9850000000', 'Careygo Logistics', ?, 'admin', 'approved')
        ON DUPLICATE KEY UPDATE password_hash = ?, role = 'admin', status = 'approved'
    ")->execute([$adminHash, $adminHash]);

    $adminResult['msg'] = 'Admin password set to Admin@123';
} catch (PDOException $e) {
    $adminResult = ['status' => 'error', 'label' => 'Admin account seed', 'msg' => $e->getMessage()];
}
$results[] = $adminResult;

// Verify tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Careygo Setup</title>
<style>
* { box-sizing: border-box; }
body { font-family: 'Segoe UI', sans-serif; background: #f0f2f9; margin: 0; padding: 24px; }
.wrap { max-width: 760px; margin: 0 auto; }
h1 { color: #001A93; font-size: 22px; margin-bottom: 4px; }
p { color: #6b7280; font-size: 13px; margin-bottom: 20px; }
.card { background: #fff; border-radius: 14px; padding: 20px 24px; margin-bottom: 16px; border: 1px solid #e4e7f0; }
.card h2 { font-size: 14px; font-weight: 700; margin: 0 0 12px; color: #1a1a2e; }
.row { display: flex; gap: 10px; align-items: flex-start; padding: 7px 0; border-bottom: 1px solid #f0f2f9; font-size: 12px; }
.row:last-child { border-bottom: none; }
.status { width: 60px; font-weight: 700; flex-shrink: 0; }
.ok    { color: #16a34a; }
.warn  { color: #b45309; }
.error { color: #b91c1c; }
.label { color: #374151; flex: 1; font-family: monospace; font-size: 11px; }
.msg   { color: #6b7280; font-size: 11px; max-width: 280px; }
.tables { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
.table-tag { background: rgba(0,26,147,0.08); color: #001A93; border-radius: 6px; padding: 3px 10px; font-size: 12px; font-weight: 600; }
.alert { border-radius: 10px; padding: 12px 16px; font-size: 13px; margin-bottom: 16px; }
.alert-success { background: rgba(34,197,94,0.1); color: #15803d; border: 1px solid rgba(34,197,94,0.2); }
.alert-warning { background: rgba(245,158,11,0.1); color: #92400e; border: 1px solid rgba(245,158,11,0.2); }
.btn { display: inline-block; background: #001A93; color: #fff; text-decoration: none; border-radius: 10px; padding: 10px 22px; font-size: 13px; font-weight: 600; margin-top: 12px; }
</style>
</head>
<body>
<div class="wrap">
    <h1>⚙️ Careygo Database Setup</h1>
    <p>Running schema.sql + schema_v2.sql migrations…
        <?php if (($_GET['pincodes'] ?? '') === '1'): ?>
        <strong style="color:#001A93;">+ pincodes.sql (15,453 rows)</strong>
        <?php endif; ?>
    </p>

    <?php
    $errors = array_filter($results, fn($r) => $r['status'] === 'error');
    if (empty($errors)):
    ?>
    <div class="alert alert-success">✅ All migrations completed successfully! You can now delete this file.</div>
    <?php else: ?>
    <div class="alert alert-warning">⚠️ Some statements had errors. Review below — if these are "already exists" warnings they can be ignored.</div>
    <?php endif; ?>

    <div class="card">
        <h2>Migration Results (<?= count($results) ?> statements)</h2>
        <?php foreach ($results as $r): ?>
        <div class="row">
            <span class="status <?= $r['status'] ?>"><?= strtoupper($r['status']) ?></span>
            <span class="label"><?= htmlspecialchars($r['label']) ?></span>
            <?php if ($r['status'] !== 'ok'): ?><span class="msg"><?= htmlspecialchars($r['msg']) ?></span><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2>Database Tables</h2>
        <div class="tables">
            <?php foreach ($tables as $t): ?>
            <span class="table-tag"><?= htmlspecialchars($t) ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h2>Next Steps</h2>
        <ol style="font-size:13px;color:#374151;margin:0;padding-left:18px;line-height:2;">
            <li>✅ Verify all tables are listed above</li>
            <li>Login as admin: <code>admin@careygo.in</code> / <code>Admin@123</code></li>
            <li>Import 15,453 pincodes: <a href="setup.php?key=careygo_setup_2026&amp;pincodes=1" style="color:#001A93;font-weight:600;">Run setup with pincodes</a> (takes ~15 sec)</li>
            <li>Go to <strong>Admin → Pricing</strong> to review/edit pricing slabs</li>
            <li><strong>Delete this setup.php file</strong> from the server!</li>
        </ol>
        <a href="admin/dashboard.php" class="btn">Go to Admin Dashboard →</a>
    </div>
</div>
</body>
</html>
