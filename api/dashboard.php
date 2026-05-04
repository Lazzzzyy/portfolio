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

try {
    $pdo = db();
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $totalProjects = (int) $pdo->query(
        'SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL'
    )->fetchColumn();

    $totalMessages = (int) $pdo->query(
        'SELECT COUNT(*) FROM contact_messages WHERE deleted_at IS NULL'
    )->fetchColumn();

    $totalPosts = (int) $pdo->query(
        "SELECT COUNT(*) FROM blog_posts WHERE status = 'published' AND deleted_at IS NULL"
    )->fetchColumn();

    $totalVisitors = (int) $pdo->query(
        'SELECT COUNT(DISTINCT session_id) FROM visitors'
    )->fetchColumn();

    $totalPageViews = (int) $pdo->query(
        'SELECT COUNT(*) FROM visitors'
    )->fetchColumn();

    $totalProjectViews = (int) $pdo->query(
        'SELECT COALESCE(SUM(views), 0) FROM projects WHERE deleted_at IS NULL'
    )->fetchColumn();

    $totalSkills = (int) $pdo->query(
        'SELECT COUNT(DISTINCT pt.technology) FROM project_technologies pt
         INNER JOIN projects p ON p.id = pt.project_id WHERE p.deleted_at IS NULL'
    )->fetchColumn();

    $unreadMessages = (int) $pdo->query(
        "SELECT COUNT(*) FROM contact_messages WHERE status = 'unread' AND deleted_at IS NULL"
    )->fetchColumn();

    $recentProjects = $pdo->query(
        "SELECT title, status, DATE_FORMAT(created_at, '%b %d, %Y') AS date
         FROM projects WHERE deleted_at IS NULL ORDER BY updated_at DESC LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    $recentMessages = $pdo->query(
        "SELECT name, subject, DATE_FORMAT(created_at, '%b %d, %Y') AS date
         FROM contact_messages WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'stats'   => [
            'projects'        => $totalProjects,
            'messages'        => $totalMessages,
            'posts'           => $totalPosts,
            'visitors'        => $totalVisitors,
            'page_views'      => $totalPageViews,
            'project_views'   => $totalProjectViews,
            'skills'          => $totalSkills,
            'unread_messages' => $unreadMessages,
        ],
        'recentProjects' => $recentProjects,
        'recentMessages' => $recentMessages,
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load stats: ' . $e->getMessage()]);
}
