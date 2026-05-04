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
        $rows = $pdo->query(
            'SELECT * FROM awards WHERE deleted_at IS NULL ORDER BY sort_order ASC, id ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['award_year'] = (int) $row['award_year'];
            $row['sort_order'] = (int) $row['sort_order'];
        }
        unset($row);

        ob_end_clean();
        echo json_encode(['success' => true, 'data' => array_values($rows)]);
    } catch (\Exception $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Could not load awards: ' . $e->getMessage()]);
    }
    exit;
}

// ── PATCH (reorder) ────────────────────────────────────────────────────────

if ($method === 'PATCH') {
    try {
        $body  = json_decode(file_get_contents('php://input'), true);
        $items = $body['order'] ?? [];

        if (!is_array($items) || empty($items)) {
            ob_end_clean();
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No order data provided']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE awards SET sort_order = ? WHERE id = ?');
        foreach ($items as $item) {
            $stmt->execute([(int) $item['sort_order'], (int) $item['id']]);
        }

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Order saved']);
    } catch (\Exception $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Reorder failed: ' . $e->getMessage()]);
    }
    exit;
}

// ── DELETE ─────────────────────────────────────────────────────────────────

if ($method === 'DELETE' && $id > 0) {
    try {
        $pdo->prepare(
            'UPDATE awards SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL'
        )->execute([$id]);
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Award removed']);
    } catch (\Exception $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Remove failed: ' . $e->getMessage()]);
    }
    exit;
}

// ── POST (create / update) ─────────────────────────────────────────────────

if ($method === 'POST') {
    try {
        $title        = trim($_POST['title']        ?? '');
        $organization = trim($_POST['organization'] ?? '');
        $awardYear    = (int) ($_POST['award_year'] ?? date('Y'));
        $description  = trim($_POST['description']  ?? '');
        $editId       = (int) ($_POST['id']         ?? 0);

        if ($title === '') {
            ob_end_clean();
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Award title is required']);
            exit;
        }

        if ($organization === '') {
            ob_end_clean();
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Organization is required']);
            exit;
        }

        $currentYear = (int) date('Y');
        if ($awardYear < 1990 || $awardYear > $currentYear + 1) {
            $awardYear = $currentYear;
        }

        // Image upload
        $imageUrl = null;
        if ($editId > 0) {
            $ex = $pdo->prepare('SELECT image_url FROM awards WHERE id = ?');
            $ex->execute([$editId]);
            $imageUrl = $ex->fetchColumn() ?: null;
        }

        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/uploads/awards/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed, true)) {
                $fname = uniqid('award_', true) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fname)) {
                    if ($imageUrl) {
                        $old = __DIR__ . '/../' . ltrim($imageUrl, './');
                        if (file_exists($old)) {
                            unlink($old);
                        }
                    }
                    $imageUrl = './assets/uploads/awards/' . $fname;
                }
            }
        }

        if ($editId > 0) {
            $pdo->prepare(
                'UPDATE awards
                 SET title=?, organization=?, award_year=?, description=?,
                     image_url=?, updated_at=NOW()
                 WHERE id=?'
            )->execute([$title, $organization, $awardYear, $description, $imageUrl, $editId]);

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Award updated successfully', 'id' => $editId]);
        } else {
            $maxSort   = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), -1) FROM awards')->fetchColumn();
            $sortOrder = $maxSort + 1;

            $pdo->prepare(
                'INSERT INTO awards (title, organization, award_year, description, image_url, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$title, $organization, $awardYear, $description, $imageUrl, $sortOrder]);

            $newId = (int) $pdo->lastInsertId();
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Award added successfully', 'id' => $newId]);
        }
    } catch (\Exception $e) {
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()]);
    }
    exit;
}

ob_end_clean();
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
