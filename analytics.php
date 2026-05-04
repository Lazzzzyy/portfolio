<?php

declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
session_start();

$user = $_SESSION['auth_user'] ?? null;

if (!is_array($user)) {
    header('Location: ./index.html');
    exit;
}

$displayName  = htmlspecialchars((string) ($user['full_name'] ?? 'User'), ENT_QUOTES, 'UTF-8');
$displayEmail = htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8');

require_once './config/database.php';

// ── Summary stats ───────────────────────────────────────────────────────────

$statUniqueVisitors = 0;
$statPageViews      = 0;
$statTotalMessages  = 0;
$statProjectViews   = 0;

// ── Chart 1: Visitor Activity ───────────────────────────────────────────────

$vDay = $vWeek = $vMonth = $vYear = [];

// ── Chart 2: Most Viewed Projects ──────────────────────────────────────────

$topProjectsRows = [];

// ── Chart 3: Top Blog Posts ─────────────────────────────────────────────────

$topPostsRows = [];

// ── Chart 4: Message Activity ───────────────────────────────────────────────

$msgTimelineRows = [];

try {
    $pdo = db();

    // Summary stats
    $statUniqueVisitors = (int) $pdo->query(
        "SELECT COUNT(DISTINCT session_id) FROM visitors"
    )->fetchColumn();

    $statPageViews = (int) $pdo->query(
        "SELECT COUNT(*) FROM visitors"
    )->fetchColumn();

    $statTotalMessages = (int) $pdo->query(
        "SELECT COUNT(*) FROM contact_messages WHERE deleted_at IS NULL"
    )->fetchColumn();

    $statProjectViews = (int) $pdo->query(
        "SELECT COALESCE(SUM(views), 0) FROM projects WHERE deleted_at IS NULL"
    )->fetchColumn();

    // Chart 1 — Visitor Activity (multi-grain)
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
                YEAR(visited_at)    AS yr,
                WEEK(visited_at, 1) AS wk,
                COUNT(*)            AS cnt
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

    // Chart 2 — Most Viewed Projects
    $topProjectsRows = $pdo->query(
        "SELECT title, views
         FROM projects
         WHERE status = 'published' AND deleted_at IS NULL
         ORDER BY views DESC LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Chart 3 — Top Blog Posts
    $topPostsRows = $pdo->query(
        "SELECT title, views
         FROM blog_posts
         WHERE status = 'published' AND deleted_at IS NULL
         ORDER BY views DESC LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Chart 4 — Message Activity
    $msgTimelineRows = $pdo->query(
        "SELECT DATE_FORMAT(created_at, '%b %Y') AS lbl,
                YEAR(created_at)  AS yr,
                MONTH(created_at) AS mo,
                COUNT(*)          AS cnt
         FROM contact_messages
         WHERE deleted_at IS NULL
         GROUP BY yr, mo ORDER BY yr, mo"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (\Exception $e) {
    // leave all defaults as zeroes / empty arrays
}

// ── Build chart data attributes ─────────────────────────────────────────────

$chartVisitors = htmlspecialchars(json_encode([
    'day'   => ['labels' => array_column($vDay,   'lbl'), 'values' => array_map('intval', array_column($vDay,   'cnt'))],
    'week'  => ['labels' => array_column($vWeek,  'lbl'), 'values' => array_map('intval', array_column($vWeek,  'cnt'))],
    'month' => ['labels' => array_column($vMonth, 'lbl'), 'values' => array_map('intval', array_column($vMonth, 'cnt'))],
    'year'  => ['labels' => array_column($vYear,  'lbl'), 'values' => array_map('intval', array_column($vYear,  'cnt'))],
]), ENT_QUOTES, 'UTF-8');

$chartTopProjects = htmlspecialchars(json_encode([
    'labels' => array_map(
        fn($t) => mb_strlen($t) > 26 ? mb_substr($t, 0, 26) . '…' : $t,
        array_column($topProjectsRows, 'title')
    ),
    'values' => array_map('intval', array_column($topProjectsRows, 'views')),
]), ENT_QUOTES, 'UTF-8');

$chartTopPosts = htmlspecialchars(json_encode([
    'labels' => array_map(
        fn($t) => mb_strlen($t) > 26 ? mb_substr($t, 0, 26) . '…' : $t,
        array_column($topPostsRows, 'title')
    ),
    'values' => array_map('intval', array_column($topPostsRows, 'views')),
]), ENT_QUOTES, 'UTF-8');

$chartMsgActivity = htmlspecialchars(json_encode([
    'labels' => array_column($msgTimelineRows, 'lbl'),
    'values' => array_map('intval', array_column($msgTimelineRows, 'cnt')),
]), ENT_QUOTES, 'UTF-8');

?>
<!doctype html>
<html lang="en" data-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Analytics — Portfolio</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <script src="./assets/js/theme-init.js"></script>

    <link rel="stylesheet" href="./assets/css/style.css?v=20260320-f" />
    <link rel="stylesheet" href="./assets/css/app.css?v=20260317" />
    <link rel="stylesheet" href="./assets/css/analytics.css?v=20260317" />

    <script src="https://cdn.jsdelivr.net/npm/apexcharts@latest/dist/apexcharts.min.js"></script>
  </head>
  <body class="dashboard-page">
    <?php include './partials/sidebar.php'; ?>

    <div class="dashboard-body">
      <?php include './partials/header.php'; ?>

      <main class="dashboard-main">
        <div class="dash-content">

          <div class="an-page-header">
            <div>
              <h2 class="an-page-title">Analytics</h2>
              <p class="an-page-subtitle">Portfolio performance overview</p>
            </div>
            <button type="button" class="dash-refresh-btn" id="anRefreshBtn" aria-label="Refresh stats">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="23 4 23 10 17 10"></polyline>
                <polyline points="1 20 1 14 7 14"></polyline>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
              </svg>
              Refresh
            </button>
          </div>

          <div class="an-stats-row">
            <div class="an-stat-card">
              <div class="an-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </div>
              <div class="an-stat-body">
                <span class="an-stat-value" id="anStatVisitors"><?= number_format($statUniqueVisitors) ?></span>
                <span class="an-stat-label">Unique Visitors</span>
              </div>
            </div>

            <div class="an-stat-card">
              <div class="an-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
              </div>
              <div class="an-stat-body">
                <span class="an-stat-value" id="anStatPageViews"><?= number_format($statPageViews) ?></span>
                <span class="an-stat-label">Page Views</span>
              </div>
            </div>

            <div class="an-stat-card">
              <div class="an-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                </svg>
              </div>
              <div class="an-stat-body">
                <span class="an-stat-value" id="anStatProjectViews"><?= number_format($statProjectViews) ?></span>
                <span class="an-stat-label">Project Views</span>
              </div>
            </div>

            <div class="an-stat-card">
              <div class="an-stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
              </div>
              <div class="an-stat-body">
                <span class="an-stat-value" id="anStatMessages"><?= number_format($statTotalMessages) ?></span>
                <span class="an-stat-label">Messages Received</span>
              </div>
            </div>
          </div>

          <!-- ── 1. Visitor Activity ───────────────────────────────────── -->
          <div class="an-chart-card">
            <div class="an-chart-card-header">
              <div>
                <h3 class="an-chart-title">Visitor Activity</h3>
                <p class="an-chart-subtitle">How many people visit your portfolio over time</p>
              </div>
              <select id="visitorsFilter" class="an-chart-filter">
                <option value="day"   selected>Per Day</option>
                <option value="week">Per Week</option>
                <option value="month">Per Month</option>
                <option value="year">Per Year</option>
              </select>
            </div>
            <div id="chartVisitors" data-chart="<?= $chartVisitors ?>"></div>
          </div>

          <!-- ── 2. Most Viewed Projects ──────────────────────────────── -->
          <div class="an-chart-card">
            <div class="an-chart-card-header">
              <div>
                <h3 class="an-chart-title">Most Viewed Projects</h3>
                <p class="an-chart-subtitle">Which projects are getting the most attention</p>
              </div>
            </div>
            <div id="chartTopProjects" data-chart="<?= $chartTopProjects ?>"></div>
          </div>

          <!-- ── 3. Top Blog Posts ────────────────────────────────────── -->
          <div class="an-chart-card">
            <div class="an-chart-card-header">
              <div>
                <h3 class="an-chart-title">Top Blog Posts</h3>
                <p class="an-chart-subtitle">Which articles people are reading the most</p>
              </div>
            </div>
            <div id="chartTopPosts" data-chart="<?= $chartTopPosts ?>"></div>
          </div>

          <!-- ── 4. Message Activity ──────────────────────────────────── -->
          <div class="an-chart-card">
            <div class="an-chart-card-header">
              <div>
                <h3 class="an-chart-title">Message Activity</h3>
                <p class="an-chart-subtitle">How many questions you are receiving over time</p>
              </div>
            </div>
            <div id="chartMsgActivity" data-chart="<?= $chartMsgActivity ?>"></div>
          </div>

        </div>
      </main>
    </div>

    <script src="./assets/js/theme.js"></script>
    <script src="./assets/js/sidebar.js"></script>
    <script src="./assets/js/header.js?v=20260320"></script>
    <script src="./assets/js/tooltip.js"></script>
    <script src="./assets/js/analytics.js"></script>
  </body>
</html>
