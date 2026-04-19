<?php
/**
 * View PHP error logs for debugging
 * Access as: /view_logs.php
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    die('Access Denied. Admin only.');
}

$logFiles = [
    'Default PHP' => ini_get('error_log'),
    'Temp Dir' => sys_get_temp_dir() . '/php_errors.log',
    'Apache' => '/var/log/apache2/error.log',
    'Nginx' => '/var/log/nginx/error.log',
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>PHP Error Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f5f5f5; font-family: monospace; }
        .container { max-width: 1000px; }
        .log-block { background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 8px; margin: 20px 0; overflow-x: auto; max-height: 500px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; font-size: 12px; }
        .error-line { color: #ff6b6b; }
        .warning-line { color: #ffd43b; }
        .success-line { color: #51cf66; }
        h4 { color: #001A93; margin-top: 30px; }
    </style>
</head>
<body>

<div class="container">
    <h2>📋 PHP Error Logs</h2>
    <p class="text-muted">Shows recent PHP errors for debugging</p>

    <?php foreach ($logFiles as $name => $path): ?>
        <h4><?= htmlspecialchars($name) ?></h4>
        <p style="color: #666; font-size: 12px;">Path: <code><?= htmlspecialchars($path) ?></code></p>

        <?php
        if (!$path || !is_readable($path)) {
            echo '<div class="alert alert-warning">File not found or not readable: ' . htmlspecialchars($path) . '</div>';
            continue;
        }

        // Read last 100 lines
        $lines = file($path);
        if (!$lines || count($lines) === 0) {
            echo '<div class="alert alert-info">No log entries yet</div>';
            continue;
        }

        $lastLines = array_slice($lines, -100);
        ?>

        <div class="log-block">
        <?php foreach ($lastLines as $line): ?>
            <?php
                $line = rtrim($line);
                if (empty($line)) continue;

                // Color code based on content
                $class = '';
                if (stripos($line, 'error') !== false || stripos($line, 'failed') !== false) {
                    $class = 'error-line';
                } elseif (stripos($line, 'warning') !== false || stripos($line, 'deprecated') !== false) {
                    $class = 'warning-line';
                } elseif (stripos($line, 'success') !== false || stripos($line, 'ok') !== false) {
                    $class = 'success-line';
                }

                echo '<div class="' . $class . '">' . htmlspecialchars($line) . '</div>';
            ?>
        <?php endforeach; ?>
        </div>

    <?php endforeach; ?>

    <h4>💡 Quick Filter</h4>
    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" class="form-control" name="search" placeholder="Search logs for text (e.g., SHIPMENT_ERROR, Database)">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>

    <?php
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = strtolower($_GET['search']);
        echo '<h4>🔍 Search Results for: ' . htmlspecialchars($_GET['search']) . '</h4>';

        $found = false;
        foreach ($logFiles as $name => $path) {
            if (!is_readable($path)) continue;

            $lines = file($path);
            $matches = [];
            foreach ($lines as $line) {
                if (stripos($line, $search) !== false) {
                    $matches[] = $line;
                }
            }

            if (!empty($matches)) {
                echo '<p><strong>' . htmlspecialchars($name) . ':</strong></p>';
                echo '<div class="log-block">';
                foreach (array_slice($matches, -50) as $match) {
                    echo '<div>' . htmlspecialchars($match) . '</div>';
                }
                echo '</div>';
                $found = true;
            }
        }

        if (!$found) {
            echo '<div class="alert alert-info">No matches found</div>';
        }
    }
    ?>

    <hr class="my-5">
    <h4>📝 Common Error Codes</h4>
    <table class="table table-sm table-bordered">
        <tr style="background: #f0f0f0;">
            <th>Code</th>
            <th>Meaning</th>
            <th>Fix</th>
        </tr>
        <tr>
            <td>1054</td>
            <td>Unknown column in table</td>
            <td>Run /setup.php to add missing columns</td>
        </tr>
        <tr>
            <td>1146</td>
            <td>Table doesn't exist</td>
            <td>Run /setup.php to create missing tables</td>
        </tr>
        <tr>
            <td>1364</td>
            <td>Field doesn't have default value</td>
            <td>Check database schema, run /setup.php</td>
        </tr>
        <tr>
            <td>23000</td>
            <td>Integrity constraint violation</td>
            <td>Check for duplicate entries or FK issues</td>
        </tr>
        <tr>
            <td>Fatal error</td>
            <td>PHP code error</td>
            <td>Check syntax, missing includes, undefined functions</td>
        </tr>
    </table>

    <p class="text-muted mt-5">
        <i class="bi bi-info-circle"></i>
        Logs are refreshed automatically. Reload this page to see updated logs.
    </p>
</div>

</body>
</html>
