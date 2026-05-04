<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
session_start();

$user = $_SESSION['auth_user'] ?? null;
if (!is_array($user)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ── Helpers ────────────────────────────────────────────────────────────────

function makeSlug(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function uniqueSlug(PDO $pdo, string $base, int $excludeId = 0): string
{
    $slug    = $base;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare(
            'SELECT id FROM blog_posts WHERE slug = ? AND id != ? LIMIT 1'
        );
        $stmt->execute([$slug, $excludeId]);
        if (!$stmt->fetchColumn()) {
            break;
        }
        $slug = $base . '-' . $counter++;
    }
    return $slug;
}

// ── DB ─────────────────────────────────────────────────────────────────────

try {
    $pdo = db();
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// ── GET ────────────────────────────────────────────────────────────────────

if ($method === 'GET') {
    if ($id > 0) {
        $stmt = $pdo->prepare(
            'SELECT bp.*, p.title AS project_title
             FROM blog_posts bp
             LEFT JOIN projects p ON p.id = bp.project_id
             WHERE bp.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Post not found']);
            exit;
        }
        $row['views'] = (int) ($row['views'] ?? 0);
        echo json_encode(['success' => true, 'data' => $row]);
        exit;
    }

    $rows = $pdo->query(
        'SELECT bp.id, bp.title, bp.slug, bp.excerpt, bp.category,
                bp.thumbnail_url, bp.status, bp.views, bp.project_id,
                bp.published_at, bp.created_at, bp.updated_at,
                p.title AS project_title
         FROM blog_posts bp
         LEFT JOIN projects p ON p.id = bp.project_id
         WHERE bp.deleted_at IS NULL
         ORDER BY bp.updated_at DESC'
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['views'] = (int) ($row['views'] ?? 0);
    }
    unset($row);

    echo json_encode(['success' => true, 'data' => array_values($rows)]);
    exit;
}

// ── DELETE ─────────────────────────────────────────────────────────────────

if ($method === 'DELETE' && $id > 0) {
    $pdo->prepare(
        'UPDATE blog_posts SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL'
    )->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Post removed']);
    exit;
}

// ── POST (create / update) ─────────────────────────────────────────────────

if ($method === 'POST') {
    $title     = trim($_POST['title']      ?? '');
    $excerpt   = trim($_POST['excerpt']    ?? '');
    $content   = trim($_POST['content']    ?? '');
    $category  = trim($_POST['category']   ?? 'General');
    $status    = trim($_POST['status']     ?? 'draft');
    $projectId = (int) ($_POST['project_id'] ?? 0);
    $editId    = (int) ($_POST['id']       ?? 0);
    $slugInput = trim($_POST['slug']       ?? '');

    if ($title === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Post title is required']);
        exit;
    }

    if (!in_array($status, ['published', 'draft', 'archived'], true)) {
        $status = 'draft';
    }

    $category  = $category !== '' ? $category : 'General';
    $projectId = $projectId > 0 ? $projectId : null;

    $slugBase = $slugInput !== '' ? makeSlug($slugInput) : makeSlug($title);
    $slug     = uniqueSlug($pdo, $slugBase, $editId);

    $publishedAt = null;
    if ($status === 'published') {
        if ($editId > 0) {
            $existing = $pdo->prepare('SELECT published_at FROM blog_posts WHERE id = ?');
            $existing->execute([$editId]);
            $prev = $existing->fetchColumn();
            $publishedAt = $prev ?: date('Y-m-d H:i:s');
        } else {
            $publishedAt = date('Y-m-d H:i:s');
        }
    }

    // Thumbnail upload
    $thumbnailUrl = null;
    if ($editId > 0) {
        $ex = $pdo->prepare('SELECT thumbnail_url FROM blog_posts WHERE id = ?');
        $ex->execute([$editId]);
        $thumbnailUrl = $ex->fetchColumn() ?: null;
    }

    if (!empty($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/blog/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext     = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed, true)) {
            $fname = uniqid('blog_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $fname)) {
                if ($thumbnailUrl) {
                    $old = __DIR__ . '/../' . ltrim($thumbnailUrl, './');
                    if (file_exists($old)) {
                        unlink($old);
                    }
                }
                $thumbnailUrl = './assets/uploads/blog/' . $fname;
            }
        }
    }

    if ($editId > 0) {
        $pdo->prepare(
            'UPDATE blog_posts
             SET title=?, slug=?, excerpt=?, content=?, category=?,
                 thumbnail_url=?, status=?, project_id=?, published_at=?, updated_at=NOW()
             WHERE id=?'
        )->execute([$title, $slug, $excerpt, $content, $category,
                    $thumbnailUrl, $status, $projectId, $publishedAt, $editId]);

        echo json_encode(['success' => true, 'message' => 'Post updated successfully', 'id' => $editId, 'slug' => $slug]);
    } else {
        $pdo->prepare(
            'INSERT INTO blog_posts
             (title, slug, excerpt, content, category, thumbnail_url, status, project_id, published_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$title, $slug, $excerpt, $content, $category,
                    $thumbnailUrl, $status, $projectId, $publishedAt]);

        $newId = (int) $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Post created successfully', 'id' => $newId, 'slug' => $slug]);
    }

    exit;
}

// ── GET projects list (for dropdown) ──────────────────────────────────────

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
