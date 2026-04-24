<?php
/**
 * Admin API — Pincode TAT Management
 * GET                    — paginated / searchable list
 * GET ?action=states     — distinct states list
 * POST                   — create/update pincode
 * DELETE                 — delete pincode
 * POST ?action=import    — bulk import from CSV
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../lib/auth.php';

header('Content-Type: application/json');

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── GET: list pincodes (paginated + searchable) ──────────────────────────────
if ($method === 'GET') {

    // Distinct states list
    if ($action === 'states') {
        try {
            $states = $pdo->query(
                "SELECT DISTINCT state, COUNT(*) as cnt
                 FROM pincode_tat
                 GROUP BY state
                 ORDER BY state ASC"
            )->fetchAll(PDO::FETCH_ASSOC);
            json_response(['success' => true, 'states' => $states]);
        } catch (Exception $e) {
            json_response(['success' => false, 'message' => 'DB error.'], 500);
        }
    }

    // Paginated list
    $q      = trim($_GET['q']     ?? '');
    $stateF = trim($_GET['state'] ?? '');
    $page   = max(1, (int)($_GET['page']     ?? 1));
    $limit  = 40;
    $offset = ($page - 1) * $limit;

    $where  = 'WHERE 1=1';
    $params = [];

    if ($q !== '') {
        $where   .= ' AND (pincode LIKE ? OR city LIKE ? OR state LIKE ? OR zone LIKE ?)';
        $like     = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    if ($stateF !== '') {
        $where   .= ' AND state = ?';
        $params[] = $stateF;
    }

    try {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM pincode_tat $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $dataStmt = $pdo->prepare(
            "SELECT * FROM pincode_tat $where
             ORDER BY state ASC, city ASC, pincode ASC
             LIMIT $limit OFFSET $offset"
        );
        $dataStmt->execute($params);
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        json_response([
            'success'  => true,
            'data'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'pages'    => (int) ceil($total / $limit),
            'per_page' => $limit,
            'q'        => $q,
            'state'    => $stateF,
        ]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'DB error: ' . $e->getMessage()], 500);
    }
}

// ── CSV Import ──
if ($method === 'POST' && $action === 'import') {
    if (empty($_FILES['csv'])) {
        json_response(['success' => false, 'message' => 'No CSV file uploaded.'], 422);
    }

    $file = $_FILES['csv']['tmp_name'];
    if (!is_readable($file)) json_response(['success' => false, 'message' => 'File not readable.'], 422);

    $handle = fopen($file, 'r');
    $headers = array_map('trim', fgetcsv($handle));

    // Map expected columns (case-insensitive)
    $headers = array_map('strtolower', $headers);
    $required = ['pincode', 'city', 'state'];
    foreach ($required as $r) {
        if (!in_array($r, $headers)) {
            fclose($handle);
            json_response(['success' => false, 'message' => "Missing column: $r"], 422);
        }
    }

    $colMap = array_flip($headers);
    $count  = 0;

    $stmt = $pdo->prepare("
        INSERT INTO pincode_tat (pincode, city, state, zone, tat_standard, tat_premium, tat_air, tat_surface, serviceable)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE city=VALUES(city), state=VALUES(state), zone=VALUES(zone),
            tat_standard=VALUES(tat_standard), tat_premium=VALUES(tat_premium),
            tat_air=VALUES(tat_air), tat_surface=VALUES(tat_surface), serviceable=VALUES(serviceable)
    ");

    $pdo->beginTransaction();
    try {
        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row)) continue;
            $pincode    = trim($row[$colMap['pincode']] ?? '');
            $city       = trim($row[$colMap['city']]    ?? '');
            $state      = trim($row[$colMap['state']]   ?? '');
            if (!$pincode || !$city || !$state) continue;

            $zone       = trim($row[$colMap['zone']         ?? -1] ?? '') ?: null;
            $tatStd     = (int) ($row[$colMap['tat_standard']  ?? -1] ?? 3);
            $tatPrm     = (int) ($row[$colMap['tat_premium']   ?? -1] ?? 1);
            $tatAir     = (int) ($row[$colMap['tat_air']       ?? -1] ?? 2);
            $tatSrf     = (int) ($row[$colMap['tat_surface']   ?? -1] ?? 5);
            $svc        = (int) ($row[$colMap['serviceable']   ?? -1] ?? 1);

            $stmt->execute([$pincode, $city, $state, $zone,
                $tatStd ?: 3, $tatPrm ?: 1, $tatAir ?: 2, $tatSrf ?: 5, $svc]);
            $count++;
        }
        $pdo->commit();
        fclose($handle);
        json_response(['success' => true, 'count' => $count]);
    } catch (Exception $e) {
        $pdo->rollBack();
        fclose($handle);
        json_response(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
    }
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $id         = !empty($body['id']) ? (int)$body['id'] : null;
    $pincode    = trim($body['pincode']      ?? '');
    $city       = trim($body['city']         ?? '');
    $state      = trim($body['state']        ?? '');
    $zone       = trim($body['zone']         ?? '') ?: null;
    $tatStd     = (int)  ($body['tat_standard'] ?? 3);
    $tatPrm     = (int)  ($body['tat_premium']  ?? 1);
    $tatAir     = (int)  ($body['tat_air']       ?? 2);
    $tatSrf     = (int)  ($body['tat_surface']   ?? 5);
    $svc        = (int)  ($body['serviceable']   ?? 1);

    if (!$pincode || !$city || !$state) {
        json_response(['success' => false, 'message' => 'Pincode, city and state are required.'], 422);
    }

    try {
        if ($id) {
            $pdo->prepare("UPDATE pincode_tat SET pincode=?,city=?,state=?,zone=?,tat_standard=?,tat_premium=?,tat_air=?,tat_surface=?,serviceable=? WHERE id=?")
                ->execute([$pincode,$city,$state,$zone,$tatStd,$tatPrm,$tatAir,$tatSrf,$svc,$id]);
        } else {
            $pdo->prepare("INSERT INTO pincode_tat (pincode,city,state,zone,tat_standard,tat_premium,tat_air,tat_surface,serviceable) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$pincode,$city,$state,$zone,$tatStd,$tatPrm,$tatAir,$tatSrf,$svc]);
            $id = (int) $pdo->lastInsertId();
        }
        json_response(['success' => true, 'id' => $id]);
    } catch (Exception $e) {
        $code = $e->getCode();
        if ($code == 23000) json_response(['success' => false, 'message' => 'Pincode already exists.'], 422);
        json_response(['success' => false, 'message' => 'Database error.'], 500);
    }
}

if ($method === 'DELETE') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $ids = [];
    if (!empty($body['ids']) && is_array($body['ids'])) {
        $ids = array_map('intval', $body['ids']);
    } elseif (!empty($body['id'])) {
        $ids = [(int)$body['id']];
    }

    if (empty($ids)) json_response(['success' => false, 'message' => 'Invalid ID(s).'], 422);

    try {
        $in = str_repeat('?,', count($ids) - 1) . '?';
        $pdo->prepare("DELETE FROM pincode_tat WHERE id IN ($in)")->execute($ids);
        json_response(['success' => true]);
    } catch (Exception $e) {
        json_response(['success' => false, 'message' => 'Delete failed.'], 500);
    }
}

json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
