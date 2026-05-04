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
$techMap = require __DIR__ . '/../config/tech-map.php';

header('Content-Type: application/json; charset=utf-8');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ── Helpers ────────────────────────────────────────────────────────────────

function detectTechs(string $text, array $techMap): array
{
    $canonical = [
        'html' => 'HTML', 'html5' => 'HTML',
        'css' => 'CSS', 'css3' => 'CSS',
        'js' => 'JavaScript', 'javascript' => 'JavaScript',
        'ts' => 'TypeScript', 'typescript' => 'TypeScript',
        'nodejs' => 'Node.js', 'node' => 'Node.js', 'node.js' => 'Node.js',
        'tailwind' => 'Tailwind CSS', 'tailwindcss' => 'Tailwind CSS',
        'vuejs' => 'Vue.js', 'vue' => 'Vue.js',
        'next.js' => 'Next.js', 'nextjs' => 'Next.js',
        'nuxtjs' => 'Nuxt.js',
        'reactnative' => 'React Native', 'react native' => 'React Native',
        'csharp' => 'C#', 'c#' => 'C#',
        'dotnet' => '.NET', '.net' => '.NET',
        'postgres' => 'PostgreSQL', 'postgresql' => 'PostgreSQL',
        'mongo' => 'MongoDB', 'mongodb' => 'MongoDB',
        'mysql' => 'MySQL', 'php' => 'PHP', 'python' => 'Python',
        'laravel' => 'Laravel', 'react' => 'React', 'angular' => 'Angular',
        'svelte' => 'Svelte', 'bootstrap' => 'Bootstrap', 'sass' => 'Sass',
        'scss' => 'Sass', 'jquery' => 'jQuery', 'django' => 'Django',
        'flask' => 'Flask', 'java' => 'Java', 'spring' => 'Spring',
        'ruby' => 'Ruby', 'rails' => 'Rails', 'go' => 'Go',
        'golang' => 'Go', 'rust' => 'Rust', 'kotlin' => 'Kotlin',
        'swift' => 'Swift', 'flutter' => 'Flutter', 'dart' => 'Dart',
        'sqlite' => 'SQLite', 'redis' => 'Redis', 'firebase' => 'Firebase',
        'supabase' => 'Supabase', 'docker' => 'Docker', 'git' => 'Git',
        'github' => 'GitHub', 'aws' => 'AWS', 'azure' => 'Azure',
        'nginx' => 'Nginx', 'linux' => 'Linux', 'bash' => 'Bash',
        'kubernetes' => 'Kubernetes', 'express' => 'Express',
        'alpinejs' => 'Alpine.js',
    ];

    $found   = [];
    $seenKey = [];

    foreach ($techMap as $key => [$cat, $icon]) {
        $pattern = '/\b' . preg_quote($key, '/') . '\b/i';
        if (preg_match($pattern, $text)) {
            $name = $canonical[$key] ?? ucfirst($key);
            if (!isset($seenKey[$name])) {
                $seenKey[$name] = true;
                $found[]        = $name;
            }
        }
    }

    return array_values($found);
}

function suggestStatus(string $demoUrl): string
{
    return $demoUrl !== '' ? 'published' : 'draft';
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
    $rows = $pdo->query(
        "SELECT p.*,
                GROUP_CONCAT(pt.technology ORDER BY pt.id SEPARATOR '|||') AS techs
         FROM projects p
         LEFT JOIN project_technologies pt ON pt.project_id = p.id
         WHERE p.deleted_at IS NULL
         GROUP BY p.id
         ORDER BY p.updated_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['technologies'] = $row['techs'] !== null
            ? array_filter(explode('|||', $row['techs']))
            : [];
        unset($row['techs']);
        $row['views']          = (int) ($row['views'] ?? 0);
        $row['comments_count'] = (int) ($row['comments_count'] ?? 0);
    }
    unset($row);

    echo json_encode(['success' => true, 'data' => array_values($rows)]);
    exit;
}

// ── DELETE ─────────────────────────────────────────────────────────────────

if ($method === 'DELETE' && $id > 0) {
    $pdo->prepare(
        'UPDATE projects SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL'
    )->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Project removed']);
    exit;
}

// ── POST (create / update) ─────────────────────────────────────────────────

if ($method === 'POST') {
    $title     = trim($_POST['title']       ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $category  = trim($_POST['category']    ?? 'Web Systems');
    $githubUrl = trim($_POST['github_url']  ?? '');
    $demoUrl   = trim($_POST['demo_url']    ?? '');
    $status    = trim($_POST['status']      ?? '');
    $editId    = (int) ($_POST['id']        ?? 0);

    if ($title === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Project title is required']);
        exit;
    }

    $validCats = ['Web Systems', 'Research', 'UI/UX', 'Mobile'];
    if (!in_array($category, $validCats, true)) {
        $category = 'Web Systems';
    }

    if (!in_array($status, ['published', 'draft', 'archived'], true)) {
        $status = suggestStatus($demoUrl);
    }

    // Thumbnail upload
    $thumbnailUrl = null;
    if ($editId > 0) {
        $ex = $pdo->prepare('SELECT thumbnail_url FROM projects WHERE id = ?');
        $ex->execute([$editId]);
        $thumbnailUrl = $ex->fetchColumn() ?: null;
    }

    if (!empty($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/projects/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $ext     = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed, true)) {
            $fname = uniqid('proj_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadDir . $fname)) {
                if ($thumbnailUrl) {
                    $old = __DIR__ . '/../' . ltrim($thumbnailUrl, './');
                    if (file_exists($old)) {
                        unlink($old);
                    }
                }
                $thumbnailUrl = './assets/uploads/projects/' . $fname;
            }
        }
    }

    // Auto-detect technologies
    $detectedTechs = detectTechs($title . ' ' . $desc, $techMap);

    // Merge manual additions
    $manualRaw = trim($_POST['technologies'] ?? '');
    if ($manualRaw !== '') {
        $manual        = array_map('trim', explode(',', $manualRaw));
        $detectedTechs = array_values(array_unique(array_merge($detectedTechs, $manual)));
    }

    $detectedTechs = array_filter($detectedTechs, fn($t) => $t !== '');

    if ($editId > 0) {
        $pdo->prepare(
            'UPDATE projects
             SET title=?, description=?, status=?, category=?,
                 thumbnail_url=?, github_url=?, project_url=?, updated_at=NOW()
             WHERE id=?'
        )->execute([$title, $desc, $status, $category, $thumbnailUrl, $githubUrl, $demoUrl, $editId]);

        $pdo->prepare('DELETE FROM project_technologies WHERE project_id = ?')->execute([$editId]);

        foreach ($detectedTechs as $tech) {
            $pdo->prepare('INSERT INTO project_technologies (project_id, technology) VALUES (?, ?)')
                ->execute([$editId, $tech]);
        }

        echo json_encode(['success' => true, 'message' => 'Project updated successfully', 'id' => $editId]);
    } else {
        $pdo->prepare(
            'INSERT INTO projects (title, description, status, category, thumbnail_url, github_url, project_url)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$title, $desc, $status, $category, $thumbnailUrl, $githubUrl, $demoUrl]);

        $newId = (int) $pdo->lastInsertId();

        foreach ($detectedTechs as $tech) {
            $pdo->prepare('INSERT INTO project_technologies (project_id, technology) VALUES (?, ?)')
                ->execute([$newId, $tech]);
        }

        echo json_encode(['success' => true, 'message' => 'Project created successfully', 'id' => $newId]);
    }

    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
