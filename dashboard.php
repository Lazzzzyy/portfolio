<?php

declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
session_start();

$user = $_SESSION['auth_user'] ?? null;

if (!is_array($user)) {
    header('Location: ./index.html');
    exit;
}

$displayName = htmlspecialchars((string) ($user['full_name'] ?? 'User'), ENT_QUOTES, 'UTF-8');
$displayEmail = htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8');

require_once './config/database.php';

$techMap = require './config/tech-map.php';

try {
    $pdo = db();

    $totalProjects = (int) $pdo->query('SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL')->fetchColumn();

    $statusRows   = $pdo->query('SELECT status, COUNT(*) AS cnt FROM projects WHERE deleted_at IS NULL GROUP BY status')->fetchAll();
    $statusCounts = ['published' => 0, 'draft' => 0, 'archived' => 0];
    foreach ($statusRows as $row) {
        $statusCounts[$row['status']] = (int) $row['cnt'];
    }

    $categoryRows   = $pdo->query('SELECT category, COUNT(*) AS cnt FROM projects WHERE deleted_at IS NULL GROUP BY category ORDER BY cnt DESC')->fetchAll();
    $categoryLabels = array_column($categoryRows, 'category');
    $categoryValues = array_map('intval', array_column($categoryRows, 'cnt'));

    $techRows   = $pdo->query('SELECT pt.technology, COUNT(*) AS cnt FROM project_technologies pt INNER JOIN projects p ON p.id = pt.project_id WHERE p.deleted_at IS NULL GROUP BY pt.technology ORDER BY cnt DESC LIMIT 10')->fetchAll();
    $techLabels = array_column($techRows, 'technology');
    $techValues = array_map('intval', array_column($techRows, 'cnt'));

    $growthRows   = $pdo->query('SELECT YEAR(created_at) AS yr, COUNT(*) AS cnt FROM projects WHERE deleted_at IS NULL GROUP BY yr ORDER BY yr')->fetchAll();
    $growthLabels = array_column($growthRows, 'yr');
    $growthValues = array_map('intval', array_column($growthRows, 'cnt'));

    $skillRows = $pdo->query(
        "SELECT pt.technology,
                COUNT(DISTINCT pt.project_id) AS project_count,
                DATE_FORMAT(MAX(p.created_at), '%b %Y') AS last_used
         FROM project_technologies pt
         INNER JOIN projects p ON pt.project_id = p.id
         WHERE p.deleted_at IS NULL
         GROUP BY pt.technology
         ORDER BY project_count DESC, pt.technology ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (\Exception $e) {
    $totalProjects  = 0;
    $statusCounts   = ['published' => 0, 'draft' => 0, 'archived' => 0];
    $categoryLabels = $categoryValues = [];
    $techLabels     = $techValues     = [];
    $growthLabels   = $growthValues   = [];
    $skillRows      = [];
}

$chartStatus = htmlspecialchars(json_encode([
    'labels' => ['Published', 'Draft', 'Archived'],
    'values' => [$statusCounts['published'], $statusCounts['draft'], $statusCounts['archived']],
]), ENT_QUOTES, 'UTF-8');

$chartCat = htmlspecialchars(json_encode([
    'labels' => $categoryLabels,
    'values' => $categoryValues,
]), ENT_QUOTES, 'UTF-8');

$chartTech = htmlspecialchars(json_encode([
    'labels' => $techLabels,
    'values' => $techValues,
]), ENT_QUOTES, 'UTF-8');

$chartGrowth = htmlspecialchars(json_encode([
    'labels' => $growthLabels,
    'values' => $growthValues,
]), ENT_QUOTES, 'UTF-8');

$skillsGrouped = [];
$totalSkills   = 0;

foreach ($skillRows as $row) {
    $key  = strtolower(trim($row['technology']));
    $cat  = $techMap[$key][0] ?? 'Other';
    $icon = $techMap[$key][1] ?? '';
    $cnt  = (int) $row['project_count'];
    $pct  = min($cnt * 12 + 8, 100);
    if ($pct >= 85)     $lvl = 'Expert';
    elseif ($pct >= 65) $lvl = 'Advanced';
    elseif ($pct >= 45) $lvl = 'Proficient';
    elseif ($pct >= 25) $lvl = 'Familiar';
    else                $lvl = 'Beginner';
    $skillsGrouped[$cat][] = [
        'name'     => $row['technology'],
        'devicon'  => $icon,
        'count'    => $cnt,
        'lastUsed' => $row['last_used'],
        'pct'      => $pct,
        'level'    => $lvl,
    ];
    $totalSkills++;
}

$msgCategoryRows  = [];
$msgTimelineRows  = [];
$msgStatusCounts  = ['unread' => 0, 'read' => 0, 'archived' => 0];
$totalMessages    = 0;

try {
    $msgCategoryRows = $pdo->query(
        "SELECT category, COUNT(*) AS cnt
         FROM contact_messages WHERE deleted_at IS NULL GROUP BY category ORDER BY cnt DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $msgTimelineRows = $pdo->query(
        "SELECT DATE_FORMAT(created_at, '%b %Y') AS lbl,
                YEAR(created_at) AS yr,
                MONTH(created_at) AS mo,
                COUNT(*) AS cnt
         FROM contact_messages
         WHERE deleted_at IS NULL
         GROUP BY yr, mo ORDER BY yr, mo"
    )->fetchAll(PDO::FETCH_ASSOC);

    $msgStatusRows = $pdo->query(
        "SELECT status, COUNT(*) AS cnt FROM contact_messages WHERE deleted_at IS NULL GROUP BY status"
    )->fetchAll(PDO::FETCH_ASSOC);
    foreach ($msgStatusRows as $row) {
        $msgStatusCounts[$row['status']] = (int) $row['cnt'];
    }
    $totalMessages = array_sum($msgStatusCounts);
} catch (\Exception $e) {
    $msgCategoryRows = $msgTimelineRows = [];
    $msgStatusCounts = ['unread' => 0, 'read' => 0, 'archived' => 0];
    $totalMessages   = 0;
}

$chartMsgCat = htmlspecialchars(json_encode([
    'labels' => array_column($msgCategoryRows, 'category'),
    'values' => array_map('intval', array_column($msgCategoryRows, 'cnt')),
]), ENT_QUOTES, 'UTF-8');

$chartMsgTimeline = htmlspecialchars(json_encode([
    'labels' => array_column($msgTimelineRows, 'lbl'),
    'values' => array_map('intval', array_column($msgTimelineRows, 'cnt')),
]), ENT_QUOTES, 'UTF-8');

$postsTimeRows    = [];
$postsCatRows     = [];
$postsViewedRows  = [];
$totalPosts       = 0;

try {
    $totalPosts = (int) $pdo->query(
        "SELECT COUNT(*) FROM blog_posts WHERE status = 'published' AND deleted_at IS NULL"
    )->fetchColumn();

    $postsTimeRows = $pdo->query(
        "SELECT DATE_FORMAT(published_at, '%b %Y') AS lbl,
                YEAR(published_at) AS yr,
                MONTH(published_at) AS mo,
                COUNT(*) AS cnt
         FROM blog_posts
         WHERE status = 'published' AND published_at IS NOT NULL AND deleted_at IS NULL
         GROUP BY yr, mo ORDER BY yr, mo"
    )->fetchAll(PDO::FETCH_ASSOC);

    $postsCatRows = $pdo->query(
        "SELECT category, COUNT(*) AS cnt
         FROM blog_posts WHERE status = 'published' AND deleted_at IS NULL
         GROUP BY category ORDER BY cnt DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $postsViewedRows = $pdo->query(
        "SELECT title, views
         FROM blog_posts WHERE status = 'published' AND deleted_at IS NULL
         ORDER BY views DESC LIMIT 8"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    $postsTimeRows = $postsCatRows = $postsViewedRows = [];
    $totalPosts    = 0;
}

$chartPostsTime = htmlspecialchars(json_encode([
    'labels' => array_column($postsTimeRows, 'lbl'),
    'values' => array_map('intval', array_column($postsTimeRows, 'cnt')),
]), ENT_QUOTES, 'UTF-8');

$chartPostsCat = htmlspecialchars(json_encode([
    'labels' => array_column($postsCatRows, 'category'),
    'values' => array_map('intval', array_column($postsCatRows, 'cnt')),
]), ENT_QUOTES, 'UTF-8');

$chartPostsViewed = htmlspecialchars(json_encode([
    'labels' => array_column($postsViewedRows, 'title'),
    'values' => array_map('intval', array_column($postsViewedRows, 'views')),
]), ENT_QUOTES, 'UTF-8');

$totalVisitors  = 0;
$uniqueVisitors = 0;
$totalPageViews = 0;
$avgDuration    = '—';
$vDay = $vWeek = $vMonth = $vYear = [];

try {
    $uniqueVisitors = $totalVisitors = (int) $pdo->query(
        "SELECT COUNT(DISTINCT session_id) FROM visitors"
    )->fetchColumn();

    $totalPageViews = (int) $pdo->query(
        "SELECT COUNT(*) FROM visitors"
    )->fetchColumn();

    $avgSec = (float) $pdo->query(
        "SELECT COALESCE(AVG(duration_seconds), 0) FROM visitors WHERE duration_seconds IS NOT NULL"
    )->fetchColumn();

    if ($avgSec > 0) {
        $mins        = (int) floor($avgSec / 60);
        $secs        = (int) round($avgSec) % 60;
        $avgDuration = $mins > 0 ? "{$mins}m {$secs}s" : "{$secs}s";
    }

    $vDay = $pdo->query(
        "SELECT DATE_FORMAT(visited_at, '%b %d') AS lbl,
                DATE(visited_at) AS dt,
                COUNT(*) AS cnt
         FROM visitors
         WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
         GROUP BY dt ORDER BY dt"
    )->fetchAll(PDO::FETCH_ASSOC);

    $vWeek = $pdo->query(
        "SELECT CONCAT('Wk ', LPAD(WEEK(visited_at, 1), 2, '0'), ' ', YEAR(visited_at)) AS lbl,
                YEAR(visited_at)     AS yr,
                WEEK(visited_at, 1)  AS wk,
                COUNT(*)             AS cnt
         FROM visitors
         WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
         GROUP BY yr, wk ORDER BY yr, wk"
    )->fetchAll(PDO::FETCH_ASSOC);

    $vMonth = $pdo->query(
        "SELECT DATE_FORMAT(visited_at, '%b %Y') AS lbl,
                YEAR(visited_at)  AS yr,
                MONTH(visited_at) AS mo,
                COUNT(*)          AS cnt
         FROM visitors
         WHERE visited_at >= DATE_SUB(CURDATE(), INTERVAL 24 MONTH)
         GROUP BY yr, mo ORDER BY yr, mo"
    )->fetchAll(PDO::FETCH_ASSOC);

    $vYear = $pdo->query(
        "SELECT YEAR(visited_at) AS lbl,
                YEAR(visited_at) AS yr,
                COUNT(*)         AS cnt
         FROM visitors
         GROUP BY yr ORDER BY yr"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    $totalVisitors = $uniqueVisitors = $totalPageViews = 0;
    $avgDuration = '—';
    $vDay = $vWeek = $vMonth = $vYear = [];
}

$chartVisitorsTime = htmlspecialchars(json_encode([
    'day'   => ['labels' => array_column($vDay,   'lbl'), 'values' => array_map('intval', array_column($vDay,   'cnt'))],
    'week'  => ['labels' => array_column($vWeek,  'lbl'), 'values' => array_map('intval', array_column($vWeek,  'cnt'))],
    'month' => ['labels' => array_column($vMonth, 'lbl'), 'values' => array_map('intval', array_column($vMonth, 'cnt'))],
    'year'  => ['labels' => array_column($vYear,  'lbl'), 'values' => array_map('intval', array_column($vYear,  'cnt'))],
]), ENT_QUOTES, 'UTF-8');

$recentProjects = [];
try {
    $stmt = $pdo->query(
        "SELECT title, status, DATE_FORMAT(created_at, '%b %d, %Y') AS date
         FROM projects WHERE deleted_at IS NULL ORDER BY updated_at DESC LIMIT 5"
    );
    $recentProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    $recentProjects = [];
}

$recentMessages = [];
try {
    $stmt = $pdo->query(
        "SELECT name, subject, DATE_FORMAT(created_at, '%b %d, %Y') AS date
         FROM contact_messages WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 5"
    );
    $recentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    $recentMessages = [];
}
?>
<!doctype html>
<html lang="en" data-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Portfolio Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <script src="./assets/js/theme-init.js"></script>

    <link rel="stylesheet" href="./assets/css/style.css?v=20260320-f" />
    <link rel="stylesheet" href="./assets/css/app.css?v=20260317" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/devicon@latest/devicon.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@latest/dist/apexcharts.min.js"></script>
  </head>
  <body class="dashboard-page">
    <?php include './partials/sidebar.php'; ?>

    <div class="dashboard-body">
    <?php include './partials/header.php'; ?>

    <main class="dashboard-main">
      <div class="dash-content">

        <div class="dash-page-header">
          <h2 class="dash-page-title">Dashboard</h2>
          <button type="button" class="dash-refresh-btn" id="dashRefreshBtn" aria-label="Refresh stats">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="23 4 23 10 17 10"></polyline>
              <polyline points="1 20 1 14 7 14"></polyline>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            Refresh
          </button>
        </div>

        <div class="dash-stats-row">
          <button type="button" class="dash-stat-card" data-section="projects">
            <div class="dash-stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
              </svg>
            </div>
            <div class="dash-stat-body">
              <span class="dash-stat-value" id="dashStatProjects"><?= $totalProjects ?></span>
              <span class="dash-stat-label">Total Projects</span>
            </div>
          </button>

          <button type="button" class="dash-stat-card" data-section="skills">
            <div class="dash-stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="16 18 22 12 16 6"></polyline>
                <polyline points="8 6 2 12 8 18"></polyline>
              </svg>
            </div>
            <div class="dash-stat-body">
              <span class="dash-stat-value" id="dashStatSkills"><?= $totalSkills ?></span>
              <span class="dash-stat-label">Total Skills</span>
            </div>
          </button>

          <button type="button" class="dash-stat-card" data-section="messages">
            <div class="dash-stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
              </svg>
            </div>
            <div class="dash-stat-body">
              <span class="dash-stat-value" id="dashStatMessages"><?= $totalMessages ?></span>
              <span class="dash-stat-label">Total Messages</span>
            </div>
          </button>

          <button type="button" class="dash-stat-card" data-section="posts">
            <div class="dash-stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <line x1="10" y1="9" x2="8" y2="9"></line>
              </svg>
            </div>
            <div class="dash-stat-body">
              <span class="dash-stat-value" id="dashStatPosts"><?= $totalPosts ?></span>
              <span class="dash-stat-label">Total Blog Posts</span>
            </div>
          </button>

          <button type="button" class="dash-stat-card" data-section="visitors">
            <div class="dash-stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </div>
            <div class="dash-stat-body">
              <span class="dash-stat-value" id="dashStatVisitors"><?= $totalVisitors ?></span>
              <span class="dash-stat-label">Total Visitors</span>
            </div>
          </button>
        </div>

        <div id="dash-recents" class="dash-recents-row">
          <div class="dash-chart-card">
            <h3 class="dash-chart-title">Recent Messages</h3>
            <?php if (empty($recentMessages)): ?>
              <div class="dash-table-empty">No messages yet</div>
            <?php else: ?>
              <table class="dash-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Subject</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentMessages as $msg): ?>
                    <tr>
                      <td><?= htmlspecialchars($msg['name']) ?></td>
                      <td><?= htmlspecialchars($msg['subject']) ?></td>
                      <td><?= htmlspecialchars($msg['date']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

          <div class="dash-chart-card">
            <h3 class="dash-chart-title">Recent Projects</h3>
            <?php if (empty($recentProjects)): ?>
              <div class="dash-table-empty">No projects yet</div>
            <?php else: ?>
              <table class="dash-table">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentProjects as $proj): ?>
                    <tr>
                      <td><?= htmlspecialchars($proj['title']) ?></td>
                      <td><?= htmlspecialchars(ucfirst($proj['status'])) ?></td>
                      <td><?= htmlspecialchars($proj['date']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>

        <div class="dash-chart-panel dash-chart-panel--hidden" id="panel-projects">
          <div class="dash-charts-row dash-charts-row--two">
            <div class="dash-chart-card">
              <h3 class="dash-chart-title">Project Status</h3>
              <div id="chartStatus" data-chart="<?= $chartStatus ?>"></div>
            </div>
            <div class="dash-chart-card">
              <h3 class="dash-chart-title">Projects by Category</h3>
              <div id="chartCategory" data-chart="<?= $chartCat ?>"></div>
            </div>
          </div>
          <div class="dash-charts-row">
            <div class="dash-chart-card">
              <h3 class="dash-chart-title">Projects by Technology</h3>
              <div id="chartTech" data-chart="<?= $chartTech ?>"></div>
            </div>
          </div>
          <div class="dash-charts-row">
            <div class="dash-chart-card">
              <h3 class="dash-chart-title">Project Growth Over Time</h3>
              <div id="chartGrowth" data-chart="<?= $chartGrowth ?>"></div>
            </div>
          </div>
        </div>

        <div class="dash-chart-panel dash-chart-panel--hidden" id="panel-skills">
          <?php
          $categoryOrder = ['Frontend', 'Backend', 'Database', 'Mobile', 'DevOps', 'Other'];
          $hasSkills = !empty($skillsGrouped);
          ?>
          <?php if (!$hasSkills): ?>
            <div class="dash-panel-placeholder">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
              <span>No skills detected yet — add projects with technologies to populate this section</span>
            </div>
          <?php else: ?>
            <?php foreach ($categoryOrder as $cat): ?>
              <?php if (empty($skillsGrouped[$cat])) continue; ?>
              <div class="skills-section">
                <h4 class="skills-section-title"><?= htmlspecialchars($cat) ?></h4>
                <div class="skills-grid">
                  <?php foreach ($skillsGrouped[$cat] as $skill): ?>
                    <div class="skill-card">
                      <div class="skill-card-header">
                        <?php if ($skill['devicon']): ?>
                          <i class="<?= htmlspecialchars($skill['devicon']) ?> skill-icon" aria-hidden="true"></i>
                        <?php else: ?>
                          <svg class="skill-icon skill-icon--svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                          </svg>
                        <?php endif; ?>
                        <span class="skill-name"><?= htmlspecialchars($skill['name']) ?></span>
                        <span class="skill-level"><?= htmlspecialchars($skill['level']) ?></span>
                      </div>
                      <div class="skill-card-meta">
                        <span>Used in <?= $skill['count'] ?> Project<?= $skill['count'] !== 1 ? 's' : '' ?></span>
                        <?php if ($skill['lastUsed']): ?>
                          <span>Last Used: <?= htmlspecialchars($skill['lastUsed']) ?></span>
                        <?php endif; ?>
                      </div>
                      <div class="skill-progress">
                        <div class="skill-progress-track">
                          <div class="skill-progress-bar" data-pct="<?= $skill['pct'] ?>"></div>
                        </div>
                        <span class="skill-progress-label"><?= $skill['pct'] ?>%</span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="dash-chart-panel dash-chart-panel--hidden" id="panel-messages">

          <div class="msg-status-row">
            <div class="msg-status-badge msg-status-badge--unread">
              <span class="msg-status-count"><?= $msgStatusCounts['unread'] ?></span>
              <span class="msg-status-label">Unread</span>
            </div>
            <div class="msg-status-badge msg-status-badge--read">
              <span class="msg-status-count"><?= $msgStatusCounts['read'] ?></span>
              <span class="msg-status-label">Read</span>
            </div>
            <div class="msg-status-badge msg-status-badge--archived">
              <span class="msg-status-count"><?= $msgStatusCounts['archived'] ?></span>
              <span class="msg-status-label">Archived</span>
            </div>
            <div class="msg-status-badge msg-status-badge--total">
              <span class="msg-status-count"><?= $totalMessages ?></span>
              <span class="msg-status-label">Total</span>
            </div>
          </div>

          <div class="dash-charts-row dash-charts-row--two">
            <div class="dash-chart-card">
              <h3 class="dash-chart-title">Messages by Category</h3>
              <div id="chartMsgCat" data-chart="<?= $chartMsgCat ?>"></div>
            </div>
            <div class="dash-chart-card">
              <h3 class="dash-chart-title">Message Timeline</h3>
              <div id="chartMsgTimeline" data-chart="<?= $chartMsgTimeline ?>"></div>
            </div>
          </div>

          <div class="dash-chart-card">
            <h3 class="dash-chart-title">Recent Messages</h3>
            <?php
            $recentMsgPanel = [];
            try {
                $recentMsgPanel = $pdo->query(
                    "SELECT name, email, category, subject,
                            DATE_FORMAT(created_at, '%b %d, %Y') AS date, status
                     FROM contact_messages WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 8"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                $recentMsgPanel = [];
            }
            ?>
            <?php if (empty($recentMsgPanel)): ?>
              <div class="dash-table-empty">No messages yet</div>
            <?php else: ?>
              <table class="dash-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentMsgPanel as $msg): ?>
                    <tr>
                      <td><?= htmlspecialchars($msg['name']) ?></td>
                      <td><?= htmlspecialchars($msg['category']) ?></td>
                      <td><?= htmlspecialchars($msg['subject']) ?></td>
                      <td><?= htmlspecialchars($msg['date']) ?></td>
                      <td><span class="msg-row-status msg-row-status--<?= htmlspecialchars($msg['status']) ?>"><?= ucfirst(htmlspecialchars($msg['status'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

        </div>

        <div class="dash-chart-panel dash-chart-panel--hidden" id="panel-posts">

          <div class="dash-charts-row dash-charts-row--two">
            <div class="dash-chart-card">
              <h3 class="dash-chart-title">Posts Over Time</h3>
              <div id="chartPostsTime" data-chart="<?= $chartPostsTime ?>"></div>
            </div>
            <div class="dash-chart-card">
              <h3 class="dash-chart-title">Posts by Category</h3>
              <div id="chartPostsCat" data-chart="<?= $chartPostsCat ?>"></div>
            </div>
          </div>

          <div class="dash-chart-card">
            <h3 class="dash-chart-title">Most Viewed Posts</h3>
            <div id="chartPostsViewed" data-chart="<?= $chartPostsViewed ?>"></div>
          </div>

          <div class="dash-chart-card">
            <h3 class="dash-chart-title">Recent Blog Posts</h3>
            <?php
            $recentPostsPanel = [];
            try {
                $recentPostsPanel = $pdo->query(
                    "SELECT title, category, views,
                            DATE_FORMAT(COALESCE(published_at, created_at), '%b %d, %Y') AS date,
                            status
                     FROM blog_posts WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 8"
                )->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                $recentPostsPanel = [];
            }
            ?>
            <?php if (empty($recentPostsPanel)): ?>
              <div class="dash-table-empty">No blog posts yet</div>
            <?php else: ?>
              <table class="dash-table">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Views</th>
                    <th>Date</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentPostsPanel as $post): ?>
                    <tr>
                      <td><?= htmlspecialchars($post['title']) ?></td>
                      <td><?= htmlspecialchars($post['category']) ?></td>
                      <td><?= number_format((int) $post['views']) ?></td>
                      <td><?= htmlspecialchars($post['date']) ?></td>
                      <td><span class="post-row-status post-row-status--<?= htmlspecialchars($post['status']) ?>"><?= ucfirst(htmlspecialchars($post['status'])) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>

        </div>

        <div class="dash-chart-panel dash-chart-panel--hidden" id="panel-visitors">

          <div class="dash-visitor-metrics">
            <div class="dash-visitor-metric">
              <span class="dash-visitor-metric__value"><?= number_format($uniqueVisitors) ?></span>
              <span class="dash-visitor-metric__label">Unique Visitors</span>
            </div>
            <div class="dash-visitor-metric">
              <span class="dash-visitor-metric__value"><?= number_format($totalPageViews) ?></span>
              <span class="dash-visitor-metric__label">Page Views</span>
            </div>
            <div class="dash-visitor-metric">
              <span class="dash-visitor-metric__value"><?= htmlspecialchars($avgDuration) ?></span>
              <span class="dash-visitor-metric__label">Avg. Visit Duration</span>
            </div>
          </div>

          <div class="dash-chart-card">
            <div class="dash-chart-card-header">
              <h3 class="dash-chart-title">Visitors Over Time</h3>
              <select id="visitorsFilter" class="dash-chart-filter">
                <option value="day" selected>Per Day</option>
                <option value="week">Per Week</option>
                <option value="month">Per Month</option>
                <option value="year">Per Year</option>
              </select>
            </div>
            <div id="chartVisitorsTime" data-chart="<?= $chartVisitorsTime ?>"></div>
          </div>

        </div>

      </div>
    </main>

    </div>

    <script src="./assets/js/theme.js"></script>
    <script src="./assets/js/sidebar.js"></script>
    <script src="./assets/js/header.js?v=20260320"></script>
    <script src="./assets/js/tooltip.js"></script>
    <script src="./assets/js/charts.js"></script>
    <script src="./assets/js/skills.js"></script>
    <script src="./assets/js/stat-cards.js"></script>
    <script src="./assets/js/dashboard.js"></script>
  </body>
</html>
