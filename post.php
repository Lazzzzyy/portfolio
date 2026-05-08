<?php

declare(strict_types=1);

require_once './config/database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header('Location: ./visitor');
    exit;
}

try {
    $pdo  = db();
    $stmt = $pdo->prepare(
        'SELECT bp.*, p.title AS project_title, p.github_url, p.project_url,
                p.thumbnail_url AS project_thumb
         FROM blog_posts bp
         LEFT JOIN projects p ON p.id = bp.project_id
         WHERE bp.id = ? AND bp.status = "published"'
    );
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        header('Location: ./visitor');
        exit;
    }

    // Increment views
    $pdo->prepare('UPDATE blog_posts SET views = views + 1 WHERE id = ?')->execute([$id]);

} catch (\Exception $e) {
    header('Location: ./visitor');
    exit;
}

$title   = htmlspecialchars($post['title'],       ENT_QUOTES, 'UTF-8');
$excerpt = htmlspecialchars($post['excerpt'] ?? '', ENT_QUOTES, 'UTF-8');
$cover   = htmlspecialchars($post['thumbnail_url'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en" data-theme="light">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $title ?> — Portfolio</title>
    <meta name="description" content="<?= $excerpt ?>" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap"
      rel="stylesheet"
    />

    <script src="./assets/js/theme-init.js"></script>
    <link rel="stylesheet" href="./assets/css/style.css?v=20260316" />
    <link rel="stylesheet" href="./assets/css/post.css?v=20260316" />
  </head>
  <body class="post-page">

    <header class="post-site-header">
      <div class="post-site-header-inner">
        <a href="./visitor" class="post-back-link">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
          Back to Portfolio
        </a>
        <button id="themeToggle" type="button" class="post-theme-btn" aria-label="Toggle theme">
          <svg id="iconSun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
          </svg>
          <svg id="iconMoon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
          </svg>
        </button>
      </div>
    </header>

    <main class="post-main">
      <article class="post-article">

        <?php if ($cover): ?>
          <div class="post-cover-wrap">
            <img class="post-cover" src="<?= $cover ?>" alt="<?= $title ?>" />
          </div>
        <?php endif; ?>

        <div class="post-content-wrap">

          <div class="post-meta-top">
            <span class="post-category"><?= htmlspecialchars($post['category'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></span>
            <?php if (!empty($post['published_at'])): ?>
              <span class="post-date">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <?= date('F j, Y', strtotime($post['published_at'])) ?>
              </span>
            <?php endif; ?>
            <span class="post-views">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              <?= number_format((int)($post['views'] ?? 0) + 1) ?> views
            </span>
          </div>

          <h1 class="post-title"><?= $title ?></h1>

          <?php if (!empty($post['excerpt'])): ?>
            <p class="post-excerpt"><?= htmlspecialchars($post['excerpt'], ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>

          <?php if (!empty($post['project_title'])): ?>
            <div class="post-project-card">
              <?php if (!empty($post['project_thumb'])): ?>
                <img class="post-project-thumb" src="<?= htmlspecialchars($post['project_thumb'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($post['project_title'], ENT_QUOTES, 'UTF-8') ?>" />
              <?php endif; ?>
              <div class="post-project-info">
                <span class="post-project-label">Related Project</span>
                <span class="post-project-name"><?= htmlspecialchars($post['project_title'], ENT_QUOTES, 'UTF-8') ?></span>
                <div class="post-project-links">
                  <?php if (!empty($post['github_url'])): ?>
                    <a href="<?= htmlspecialchars($post['github_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="post-project-link">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path>
                      </svg>
                      GitHub
                    </a>
                  <?php endif; ?>
                  <?php if (!empty($post['project_url'])): ?>
                    <a href="<?= htmlspecialchars($post['project_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="post-project-link">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                      </svg>
                      Live Demo
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <div class="post-body">
            <?= nl2br(htmlspecialchars($post['content'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
          </div>

        </div>
      </article>
    </main>

    <script src="./assets/js/theme.js"></script>
  </body>
</html>
