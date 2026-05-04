<?php
declare(strict_types=1);
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = db();
} catch (\Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$type = $_GET['type'] ?? 'all';

$out = [];

// Profile entries
if ($type === 'all' || $type === 'profile') {
    $rows = $pdo->query("SELECT entry_key, entry_type, entry_value FROM portfolio_entries")->fetchAll(PDO::FETCH_ASSOC);
    $profile = [];
    foreach ($rows as $r) {
        $profile[$r['entry_key']] = $r['entry_type'] === 'json' ? json_decode((string)$r['entry_value'], true) : $r['entry_value'];
    }
    $out['profile'] = $profile;
}

// Published projects
if ($type === 'all' || $type === 'projects') {
    $projects = $pdo->query(
        "SELECT p.*, GROUP_CONCAT(pt.technology ORDER BY pt.id SEPARATOR '|||') AS techs
         FROM projects p
         LEFT JOIN project_technologies pt ON pt.project_id = p.id
         WHERE p.status = 'published'
         GROUP BY p.id ORDER BY p.created_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($projects as &$row) {
        $row['technologies'] = $row['techs'] ? array_values(array_filter(explode('|||', $row['techs']))) : [];
        unset($row['techs']);
    }
    $out['projects'] = array_values($projects);
}

// Skills
if ($type === 'all' || $type === 'skills') {
    $skills = $pdo->query(
        "SELECT * FROM portfolio_items WHERE category NOT IN ('', 'award') AND is_published = 1 ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($skills as &$s) {
        $s['metadata'] = $s['metadata'] ? json_decode((string)$s['metadata'], true) : null;
    }
    $out['skills'] = array_values($skills);
}

// Awards
if ($type === 'all' || $type === 'awards') {
    $awards = $pdo->query("SELECT * FROM awards ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $out['awards'] = array_values($awards);
}

ob_end_clean();
echo json_encode(['success' => true, 'data' => $out]);
