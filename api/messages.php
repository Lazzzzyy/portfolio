<?php

declare(strict_types=1);

ob_start();
ini_set('display_errors', '0');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/session.php';
session_start();

$user = $_SESSION['auth_user'] ?? null;
if (!is_array($user)) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $pdo = db();
} catch (\Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// ── GET ────────────────────────────────────────────────────────────────────

if ($method === 'GET') {
    try {
        $status  = trim($_GET['status'] ?? 'all');
        $search  = trim($_GET['search'] ?? '');
        $page    = max(1, (int) ($_GET['page']     ?? 1));
        $perPage = min(50, max(10, (int) ($_GET['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;

        $validStatuses = ['unread', 'read', 'archived'];
        $where  = ['deleted_at IS NULL'];
        $params = [];

        if (in_array($status, $validStatuses, true)) {
            $where[]  = 'status = ?';
            $params[] = $status;
        }

        if ($search !== '') {
            $like     = '%' . $search . '%';
            $where[]  = '(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages $whereClause");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        $dataParams = array_merge($params, [$perPage, $offset]);
        $dataStmt   = $pdo->prepare(
            "SELECT id, name, email, category, subject, message, status,
                    DATE_FORMAT(created_at, '%b %d, %Y') AS date,
                    created_at
             FROM contact_messages
             $whereClause
             ORDER BY
               CASE status WHEN 'unread' THEN 0 ELSE 1 END,
               created_at DESC
             LIMIT ? OFFSET ?"
        );
        $dataStmt->execute($dataParams);
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        $countsStmt = $pdo->query(
            "SELECT status, COUNT(*) AS cnt FROM contact_messages WHERE deleted_at IS NULL GROUP BY status"
        );
        $counts = ['unread' => 0, 'read' => 0, 'archived' => 0];
        foreach ($countsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $counts[$row['status']] = (int) $row['cnt'];
        }
        $counts['total'] = $counts['unread'] + $counts['read'] + $counts['archived'];

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'data'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'counts'  => $counts,
        ]);
    } catch (\Exception $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Load failed: ' . $e->getMessage()]);
    }
    exit;
}

// ── PATCH (update status) ──────────────────────────────────────────────────

if ($method === 'PATCH') {
    try {
        $body   = json_decode(file_get_contents('php://input'), true);
        $msgId  = (int) ($body['id']     ?? 0);
        $status = trim((string) ($body['status'] ?? ''));

        $validStatuses = ['read', 'unread', 'archived'];

        if ($msgId <= 0 || !in_array($status, $validStatuses, true)) {
            ob_end_clean();
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid id or status']);
            exit;
        }

        $pdo->prepare(
            'UPDATE contact_messages SET status = ?, updated_at = NOW() WHERE id = ?'
        )->execute([$status, $msgId]);

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Status updated', 'status' => $status]);
    } catch (\Exception $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
    }
    exit;
}

// ── DELETE ─────────────────────────────────────────────────────────────────

if ($method === 'DELETE' && $id > 0) {
    try {
        $pdo->prepare(
            'UPDATE contact_messages SET deleted_at = NOW() WHERE id = ?'
        )->execute([$id]);
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Message removed']);
    } catch (\Exception $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Remove failed: ' . $e->getMessage()]);
    }
    exit;
}

ob_end_clean();
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
