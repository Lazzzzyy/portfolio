<?php
declare(strict_types=1);

require_once './config/database.php';
$techMap = require './config/tech-map.php';

// ── Helpers ──────────────────────────────────────────────────────────────────

function esc(mixed $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function truncate(string $text, int $limit = 140): array
{
    $text = trim($text);
    if (mb_strlen($text) <= $limit) return ['short' => $text, 'full' => $text, 'cut' => false];
    $short = mb_substr($text, 0, $limit);
    $pos   = mb_strrpos($short, ' ');
    if ($pos !== false) $short = mb_substr($short, 0, $pos);
    return ['short' => rtrim($short, ' .,;'), 'full' => $text, 'cut' => true];
}

// ── Tech icon helper (returns Devicon class) ─────────────────────────────────

function techIcon(string $name, array $techMap): string
{
    $key = strtolower(trim($name));
    if (isset($techMap[$key])) return $techMap[$key][1];
    // Canonical display name (lowercased) → devicon class
    static $canonical = [
        'html'          => 'devicon-html5-plain colored',
        'css'           => 'devicon-css3-plain colored',
        'javascript'    => 'devicon-javascript-plain colored',
        'typescript'    => 'devicon-typescript-plain colored',
        'tailwind css'  => 'devicon-tailwindcss-plain colored',
        'bootstrap'     => 'devicon-bootstrap-plain colored',
        'react'         => 'devicon-react-original colored',
        'vue.js'        => 'devicon-vuejs-plain colored',
        'angular'       => 'devicon-angularjs-plain colored',
        'sass'          => 'devicon-sass-original colored',
        'jquery'        => 'devicon-jquery-plain colored',
        'next.js'       => 'devicon-nextjs-original colored',
        'nuxt.js'       => 'devicon-nuxtjs-plain colored',
        'svelte'        => 'devicon-svelte-plain colored',
        'alpine.js'     => 'devicon-alpinejs-original colored',
        'php'           => 'devicon-php-plain colored',
        'laravel'       => 'devicon-laravel-plain colored',
        'node.js'       => 'devicon-nodejs-plain colored',
        'express'       => 'devicon-express-original colored',
        'python'        => 'devicon-python-plain colored',
        'django'        => 'devicon-django-plain colored',
        'flask'         => 'devicon-flask-original colored',
        'java'          => 'devicon-java-plain colored',
        'spring'        => 'devicon-spring-plain colored',
        'c#'            => 'devicon-csharp-plain colored',
        '.net'          => 'devicon-dot-net-plain colored',
        'ruby'          => 'devicon-ruby-plain colored',
        'rails'         => 'devicon-rails-plain colored',
        'go'            => 'devicon-go-plain colored',
        'rust'          => 'devicon-rust-plain colored',
        'kotlin'        => 'devicon-kotlin-plain colored',
        'mysql'         => 'devicon-mysql-plain colored',
        'postgresql'    => 'devicon-postgresql-plain colored',
        'mongodb'       => 'devicon-mongodb-plain colored',
        'sqlite'        => 'devicon-sqlite-plain colored',
        'redis'         => 'devicon-redis-plain colored',
        'firebase'      => 'devicon-firebase-plain colored',
        'supabase'      => 'devicon-supabase-plain colored',
        'swift'         => 'devicon-swift-plain colored',
        'flutter'       => 'devicon-flutter-plain colored',
        'dart'          => 'devicon-dart-plain colored',
        'react native'  => 'devicon-react-original colored',
        'git'           => 'devicon-git-plain colored',
        'github'        => 'devicon-github-original colored',
        'docker'        => 'devicon-docker-plain colored',
        'kubernetes'    => 'devicon-kubernetes-plain colored',
        'nginx'         => 'devicon-nginx-original colored',
        'linux'         => 'devicon-linux-plain colored',
        'bash'          => 'devicon-bash-plain colored',
        'aws'           => 'devicon-amazonwebservices-original colored',
        'azure'         => 'devicon-azure-plain colored',
    ];
    return $canonical[$key] ?? '';
}

// ── Data ─────────────────────────────────────────────────────────────────────

$profile  = [];
$projects = [];
$skills   = [];
$awards   = [];

try {
    $pdo = db();

    // Profile entries
    $rows = $pdo->query("SELECT entry_key, entry_type, entry_value FROM portfolio_entries")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $profile[$r['entry_key']] = $r['entry_type'] === 'json'
            ? json_decode((string)$r['entry_value'], true)
            : $r['entry_value'];
    }

    // Published projects
    $projects = $pdo->query(
        "SELECT p.*, GROUP_CONCAT(pt.technology ORDER BY pt.id SEPARATOR '|||') AS techs
         FROM projects p
         LEFT JOIN project_technologies pt ON pt.project_id = p.id
         WHERE p.status = 'published' AND p.deleted_at IS NULL
         GROUP BY p.id ORDER BY p.created_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($projects as &$row) {
        $row['technologies'] = $row['techs']
            ? array_values(array_filter(explode('|||', $row['techs'])))
            : [];
        unset($row['techs']);
    }
    unset($row);

    // Skills — auto-derived from project technologies
    $skillRows = $pdo->query(
        "SELECT pt.technology,
                COUNT(DISTINCT pt.project_id) AS project_count,
                DATE_FORMAT(MAX(p.created_at), '%b %Y') AS last_used
         FROM project_technologies pt
         LEFT JOIN projects p ON pt.project_id = p.id
         GROUP BY pt.technology
         ORDER BY project_count DESC, pt.technology ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $skills = [];
    foreach ($skillRows as $row) {
        $key = strtolower(trim($row['technology']));
        $cat = $techMap[$key][0] ?? 'Other';
        $cnt = (int) $row['project_count'];
        $pct = min($cnt * 12 + 8, 100);
        if ($pct >= 85)     $lvl = 'Expert';
        elseif ($pct >= 65) $lvl = 'Advanced';
        elseif ($pct >= 45) $lvl = 'Proficient';
        elseif ($pct >= 25) $lvl = 'Familiar';
        else                $lvl = 'Learning';
        $skills[] = [
            'title'    => $row['technology'],
            'category' => $cat,
            'metadata' => ['pct' => $pct, 'level' => $lvl, 'count' => $cnt, 'lastUsed' => $row['last_used']],
        ];
    }

    // Awards
    $awards = $pdo->query("SELECT * FROM awards WHERE deleted_at IS NULL ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (\Exception $e) {
    // Fallback to empty – page still renders
}

// ── Profile defaults ─────────────────────────────────────────────────────────

$name    = $profile['name']    ?? $profile['about_name']    ?? '';
$bio     = $profile['bio']     ?? $profile['about_bio']     ?? '';
$cvUrl   = $profile['cv_url']  ?? $profile['about_cv_url']  ?? '';
$social  = $profile['social_links'] ?? $profile['socials']  ?? [];

// Theme-aware images
$logoSrc         = './assets/images/logo.png';
$profileLightSrc = './assets/images/profile-light.png';
$profileDarkSrc  = './assets/images/profile-dark.png';

// Social defaults — check JSON blob first, then fall back to individual profile entry keys
$socialDefaults = [
    'email'     => $social['email']     ?? $profile['email']         ?? $profile['contact_email']   ?? $profile['email_address']    ?? '',
    'github'    => $social['github']    ?? $profile['github']        ?? $profile['github_url']       ?? $profile['social_github']    ?? '',
    'linkedin'  => $social['linkedin']  ?? $profile['linkedin']      ?? $profile['linkedin_url']     ?? $profile['social_linkedin']  ?? '',
    'facebook'  => $social['facebook']  ?? $profile['facebook']      ?? $profile['facebook_url']     ?? $profile['social_facebook']  ?? '',
    'instagram' => $social['instagram'] ?? $profile['instagram']     ?? $profile['instagram_url']    ?? $profile['social_instagram'] ?? '',
    'twitter'   => $social['twitter']   ?? $profile['twitter']       ?? $profile['twitter_url']      ?? $profile['social_twitter']   ?? $profile['x_url'] ?? '',
    'telegram'  => $social['telegram']  ?? $profile['telegram']      ?? $profile['telegram_url']     ?? $profile['social_telegram']  ?? '',
    'discord'   => $social['discord']   ?? $profile['discord']       ?? $profile['discord_url']      ?? $profile['social_discord']   ?? '',
    'viber'     => $social['viber']     ?? $profile['viber']         ?? $profile['viber_url']        ?? $profile['social_viber']     ?? '',
    'whatsapp'  => $social['whatsapp']  ?? $profile['whatsapp']      ?? $profile['whatsapp_url']     ?? $profile['social_whatsapp']  ?? '',
];

// Phone numbers stored as profile entries
$phoneDiscord = $profile['phone_discord'] ?? $profile['phone_1'] ?? '';
$phoneTnt     = $profile['phone_tnt']     ?? $profile['phone_2'] ?? '';
$phoneGlobe   = $profile['phone_globe']   ?? $profile['phone_3'] ?? '';

// Group skills by category
$skillsByCategory = [];
foreach ($skills as $skill) {
    $cat = $skill['category'] ?? 'Other';
    $skillsByCategory[$cat][] = $skill;
}

// Category order
$catOrder = ['Frontend', 'Backend', 'Database', 'Mobile', 'DevOps'];
uksort($skillsByCategory, function($a, $b) use ($catOrder) {
    $ai = array_search($a, $catOrder);
    $bi = array_search($b, $catOrder);
    $ai = $ai === false ? 99 : $ai;
    $bi = $bi === false ? 99 : $bi;
    return $ai - $bi;
});
?>
<!doctype html>
<?php $htmlTheme = in_array($_COOKIE['portfolio-theme'] ?? '', ['light', 'dark']) ? $_COOKIE['portfolio-theme'] : 'light'; ?>
<html lang="en" data-theme="<?= htmlspecialchars($htmlTheme) ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= esc($name) ?> &mdash; Portfolio</title>
  <meta name="description" content="<?= esc(mb_substr($bio, 0, 155)) ?>" />

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/devicon@latest/devicon.min.css" />

  <script src="./assets/js/theme-init.js"></script>
  <link rel="stylesheet" href="./assets/css/style.css" />
  <link rel="stylesheet" href="./assets/css/visitor.css?v=1" />
</head>
<body class="vis-body">

<!-- ═══════════════════════════════════════ HEADER ══════════════════════════ -->
<header class="vis-header" id="vis-header">
  <div class="vis-header-inner">

    <button class="vis-logo-btn" id="scrollTopBtn" aria-label="Scroll to top" data-tooltip="Back to top">
      <span class="vis-logo-ring">
        <img src="<?= esc($logoSrc) ?>" class="vis-logo-img" alt="Logo" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
        <span class="vis-logo-fallback" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8" r="3.8"/><path d="M4 20a8 8 0 0 1 16 0"/>
          </svg>
        </span>
      </span>
    </button>

    <nav class="vis-nav" aria-label="Portfolio navigation">
      <a href="#about"    class="vis-nav-link" data-section="about">About</a>
      <a href="#projects" class="vis-nav-link" data-section="projects">Projects</a>
      <a href="#skills"   class="vis-nav-link" data-section="skills">Skills</a>
      <?php if (!empty($awards)): ?>
      <a href="#awards"   class="vis-nav-link" data-section="awards">Awards</a>
      <?php endif; ?>
      <a href="#contact"  class="vis-nav-link" data-section="contact">Contact</a>
    </nav>

    <button class="vis-theme-btn" id="visThemeBtn" aria-label="Toggle theme" data-tooltip="Toggle theme">
      <svg id="visSunIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="5"/>
        <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
        <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
      </svg>
      <svg id="visMoonIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
      </svg>
    </button>

  </div>
</header>

<!-- ═══════════════════════════════════════ ABOUT ═══════════════════════════ -->
<section class="vis-section vis-about" id="about">
  <div class="vis-container vis-about-inner">

    <div class="vis-about-photo">
      <div class="vis-profile-wrap">
        <img
          id="visProfileImg"
          src="<?= esc($profileLightSrc) ?>"
          alt="Profile photo of <?= esc($name) ?>"
          class="vis-profile-img"
          loading="eager"
          data-light="<?= esc($profileLightSrc) ?>"
          data-dark="<?= esc($profileDarkSrc) ?>"
        />
      </div>
    </div>

    <div class="vis-about-content vis-reveal">
      <h1 class="vis-hero-name">Hello, I'm <span class="vis-hero-accent"><?= esc($name) ?></span></h1>
      <p class="vis-hero-bio"><?= esc($bio) ?></p>
      <?php if ($cvUrl && $cvUrl !== '#'): ?>
      <button type="button" class="vis-cv-link" id="visCvBtn"
              data-cv-url="<?= esc($cvUrl) ?>"
              data-tooltip="View / download my CV">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/>
        </svg>
        View My Curriculum Vitae
      </button>
      <?php endif; ?>
    </div>

  </div>
</section>

<!-- ══════════════════════════════════════ PROJECTS ═════════════════════════ -->
<section class="vis-section" id="projects">
  <div class="vis-container">
    <div class="vis-section-head">
      <h2 class="vis-section-title">Projects</h2>
      <span class="vis-section-badge"><?= count($projects) ?></span>
    </div>

    <?php if (empty($projects)): ?>
      <div class="vis-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="3" y="3" width="18" height="18" rx="2"/>
          <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
        </svg>
        <span>No published projects yet.</span>
      </div>
    <?php else: ?>
      <div class="vis-grid" id="projectsGrid">
        <?php foreach ($projects as $i => $proj):
          $desc  = truncate((string)($proj['description'] ?? ''));
          $techs = $proj['technologies'];
        ?>
        <article class="vis-card vis-reveal" style="--delay:<?= $i % 3 ?>">

          <?php if (!empty($proj['thumbnail_url'])): ?>
            <img
              class="vis-card-img"
              src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E"
              data-src="<?= esc($proj['thumbnail_url']) ?>"
              alt="<?= esc($proj['title']) ?>"
              loading="lazy"
            />
          <?php else: ?>
            <div class="vis-card-img-placeholder" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>
              </svg>
            </div>
          <?php endif; ?>

          <div class="vis-card-body">
            <h3 class="vis-card-title"><?= esc($proj['title']) ?></h3>

            <?php if (!empty($desc['short'])): ?>
              <p class="vis-card-desc">
                <?= esc($desc['short']) ?><?php if ($desc['cut']): ?><span class="vis-desc-more" data-full="<?= esc($desc['full']) ?>"> &hellip;<button type="button" class="vis-see-more">See more</button></span><?php endif; ?>
              </p>
            <?php endif; ?>

            <?php if (!empty($techs)): ?>
              <div class="vis-card-techs">
                <?php foreach ($techs as $tech):
                  $iconClass = techIcon($tech, $techMap);
                ?>
                  <?php if ($iconClass): ?>
                    <span class="vis-tech-icon" data-tooltip="<?= esc($tech) ?>">
                      <i class="<?= esc($iconClass) ?>"></i>
                    </span>
                  <?php else: ?>
                    <span class="vis-tech-pill"><?= esc($tech) ?></span>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <div class="vis-card-foot">
              <span class="vis-status vis-status--<?= esc($proj['status']) ?>" data-tooltip="Status: <?= esc(ucfirst($proj['status'])) ?>">
                <svg viewBox="0 0 10 10" aria-hidden="true"><circle cx="5" cy="5" r="4"/></svg>
                <?= esc(ucfirst($proj['status'])) ?>
              </span>

              <div class="vis-card-links">
                <?php if (!empty($proj['github_url'])): ?>
                  <a href="<?= esc($proj['github_url']) ?>" target="_blank" rel="noopener noreferrer"
                     class="vis-icon-link" data-tooltip="View on GitHub" aria-label="GitHub">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/>
                    </svg>
                  </a>
                <?php endif; ?>
                <?php if (!empty($proj['project_url'])): ?>
                  <a href="<?= esc($proj['project_url']) ?>" target="_blank" rel="noopener noreferrer"
                     class="vis-icon-link" data-tooltip="View Live Demo" aria-label="Live Demo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                      <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ═══════════════════════════════════════ SKILLS ══════════════════════════ -->
<section class="vis-section vis-section--alt" id="skills">
  <div class="vis-container">
    <div class="vis-section-head">
      <h2 class="vis-section-title">Skills</h2>
    </div>

    <?php if (empty($skillsByCategory)): ?>
      <div class="vis-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        <span>No skills added yet.</span>
      </div>
    <?php else: ?>
      <?php foreach ($skillsByCategory as $cat => $catSkills): ?>
        <div class="vis-skills-group vis-reveal">
          <h3 class="vis-skills-cat"><?= esc($cat) ?></h3>
          <div class="vis-skills-grid">
            <?php foreach ($catSkills as $skill):
              $meta    = is_array($skill['metadata']) ? $skill['metadata'] : [];
              $pct     = min(100, max(0, (int)($meta['proficiency'] ?? $meta['percentage'] ?? $meta['pct'] ?? 0)));
              $level   = $meta['level'] ?? '';
              $techKey = strtolower(str_replace([' ', '.', '-'], '', $skill['title']));
              $iconClass = techIcon($skill['title'], $techMap);
            ?>
            <div class="vis-skill-card vis-reveal">
              <div class="vis-skill-head">
                <?php if ($iconClass): ?>
                  <i class="<?= esc($iconClass) ?> vis-skill-devicon" data-tooltip="<?= esc($skill['title']) ?>"></i>
                <?php else: ?>
                  <span class="vis-skill-letter" aria-hidden="true"><?= esc(mb_substr($skill['title'], 0, 1)) ?></span>
                <?php endif; ?>
                <span class="vis-skill-name"><?= esc($skill['title']) ?></span>
                <?php if ($level): ?>
                  <span class="vis-skill-level"><?= esc($level) ?></span>
                <?php endif; ?>
              </div>
              <?php if ($pct > 0): ?>
              <div class="vis-skill-bar-wrap">
                <div class="vis-skill-bar" data-pct="<?= $pct ?>"></div>
                <span class="vis-skill-pct"><?= $pct ?>%</span>
              </div>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<!-- ═══════════════════════════════════════ AWARDS ══════════════════════════ -->
<?php if (!empty($awards)): ?>
<section class="vis-section" id="awards">
  <div class="vis-container">
    <div class="vis-section-head">
      <h2 class="vis-section-title">Awards</h2>
      <span class="vis-section-badge"><?= count($awards) ?></span>
    </div>

    <div class="vis-awards-grid">
      <?php foreach ($awards as $i => $aw): ?>
      <div class="vis-award-card vis-reveal" style="--delay:<?= $i % 3 ?>">
        <?php if (!empty($aw['image_url'])): ?>
          <img
            class="vis-award-img"
            src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E"
            data-src="<?= esc($aw['image_url']) ?>"
            alt="<?= esc($aw['title']) ?>"
            loading="lazy"
          />
        <?php else: ?>
          <div class="vis-award-img-placeholder" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/>
            </svg>
          </div>
        <?php endif; ?>
        <div class="vis-award-body">
          <span class="vis-award-year"><?= esc($aw['award_year']) ?></span>
          <h3 class="vis-award-title"><?= esc($aw['title']) ?></h3>
          <span class="vis-award-org">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            <?= esc($aw['organization']) ?>
          </span>
          <?php if (!empty($aw['description'])): ?>
            <p class="vis-award-desc"><?= esc($aw['description']) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════ CONTACT ═════════════════════════ -->
<section class="vis-section vis-section--alt" id="contact">
  <div class="vis-container">
    <div class="vis-section-head">
      <h2 class="vis-section-title">Contact</h2>
    </div>

    <div class="vis-contact-wrap">

      <!-- Social / CTA panel -->
      <div class="vis-contact-left vis-reveal">
        <p class="vis-contact-tagline">Let&rsquo;s work together or just say hello!</p>

        <!-- Social icons grid -->
        <div class="vis-socials">
          <?php
          $socialDefs = [
            'email'     => ['Gmail',       !empty($socialDefaults['email']) ? 'https://mail.google.com/mail/?view=cm&fs=1&to='.rawurlencode($socialDefaults['email']) : '',  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.907 1.528-1.147C21.69 2.28 24 3.434 24 5.457z"/></svg>'],
            'github'    => ['GitHub',      $socialDefaults['github'],    '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0 1 12 6.844a9.59 9.59 0 0 1 2.504.337c1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.02 10.02 0 0 0 22 12.017C22 6.484 17.522 2 12 2z"/></svg>'],
            'linkedin'  => ['LinkedIn',    $socialDefaults['linkedin'],  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>'],
            'telegram'  => ['Telegram',    $socialDefaults['telegram'],  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>'],
            'twitter'   => ['X / Twitter', $socialDefaults['twitter'],   '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>'],
            'facebook'  => ['Facebook',    $socialDefaults['facebook'],  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>'],
            'instagram' => ['Instagram',   $socialDefaults['instagram'], '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>'],
            'discord'   => ['Discord',     $socialDefaults['discord'],   '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>'],
            'viber'     => ['Viber',       $socialDefaults['viber'],     '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.398.002C8.198-.035 1.895.964.592 7.101c-.544 2.268-.598 4.486-.066 6.776.758 3.29 3.576 5.483 6.858 5.99l.074.012v2.882c0 .019 0 .04.002.062.018.22.144.421.327.532.327.198.743.118.975-.187l1.903-2.434c.344.016.687.024 1.03.024 3.2.037 9.503-.962 10.806-7.099.883-4.208-.032-8.826-4.32-10.965C16.547.602 14.02-.03 11.398.002zm.061 1.72c2.33-.03 4.58.535 5.864 1.176 3.563 1.776 4.389 5.618 3.609 9.19-1.07 5.103-6.302 5.924-9.073 5.899-.381-.001-.763-.016-1.143-.047a.862.862 0 0 0-.672.255l-1.31 1.676v-2.013a.862.862 0 0 0-.731-.85c-2.876-.428-5.274-2.25-5.905-4.98-.468-2.02-.414-3.974.076-5.985C3.34 2.544 8.69 1.753 11.46 1.722z"/></svg>'],
            'whatsapp'  => ['WhatsApp',    $socialDefaults['whatsapp'],  '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>'],
          ];
          foreach ($socialDefs as $key => [$label, $url, $icon]):
            $hasUrl = ($key === 'email')
              ? !empty($socialDefaults['email'])
              : !empty($url);
            $href = $hasUrl ? esc($url) : '#';
          ?>
          <a href="<?= $href ?>"
             class="vis-social-btn vis-social--<?= esc($key) ?><?= $hasUrl ? '' : ' vis-social--empty' ?>"
             data-social="<?= esc($key) ?>"
             <?= $hasUrl ? 'target="_blank" rel="noopener noreferrer"' : 'tabindex="-1" aria-hidden="true"' ?>
             data-tooltip="<?= esc($label) ?>"
             aria-label="<?= esc($label) ?>">
            <?= $icon ?>
          </a>
          <?php endforeach; ?>
        </div>

        <!-- Phone numbers (optional) -->
          <div class="vis-phones" id="visPhonesWrap"<?= (!$phoneDiscord && !$phoneTnt && !$phoneGlobe) ? ' hidden' : '' ?>>
            <span class="vis-phones-or">or reach me via</span>
            <div class="vis-phone-row" id="visPhoneRowDito"<?= !$phoneDiscord ? ' hidden' : '' ?>>
              <svg class="vis-phone-carrier" viewBox="0 0 24 24" fill="currentColor" aria-label="Dito Telecom">
                <circle cx="12" cy="12" r="10" fill="#E02020"/><text x="50%" y="56%" text-anchor="middle" font-size="8" font-weight="bold" fill="white" font-family="sans-serif">D</text>
              </svg>
              <span id="visPhoneDito"><?= esc($phoneDiscord) ?></span>
            </div>
            <div class="vis-phone-row" id="visPhoneRowTnt"<?= !$phoneTnt ? ' hidden' : '' ?>>
              <svg class="vis-phone-carrier" viewBox="0 0 24 24" fill="currentColor" aria-label="TNT">
                <circle cx="12" cy="12" r="10" fill="#FF6600"/><text x="50%" y="56%" text-anchor="middle" font-size="7" font-weight="bold" fill="white" font-family="sans-serif">TNT</text>
              </svg>
              <span id="visPhoneTnt"><?= esc($phoneTnt) ?></span>
            </div>
            <div class="vis-phone-row" id="visPhoneRowGlobe"<?= !$phoneGlobe ? ' hidden' : '' ?>>
              <svg class="vis-phone-carrier" viewBox="0 0 24 24" fill="currentColor" aria-label="Globe">
                <circle cx="12" cy="12" r="10" fill="#005BAA"/><text x="50%" y="56%" text-anchor="middle" font-size="6" font-weight="bold" fill="white" font-family="sans-serif">Globe</text>
              </svg>
              <span id="visPhoneGlobe"><?= esc($phoneGlobe) ?></span>
            </div>
          </div>
      </div>

      <!-- Contact form -->
      <div class="vis-contact-right vis-reveal">
        <form class="vis-form" id="visContactForm" novalidate>
          <div class="vis-form-row">
            <div class="vis-form-group">
              <label class="vis-label" for="cf-name">Name <span aria-hidden="true">*</span></label>
              <input class="vis-input" type="text" id="cf-name" name="name" autocomplete="name" required maxlength="120" placeholder="Your full name" />
            </div>
            <div class="vis-form-group">
              <label class="vis-label" for="cf-email">Email <span aria-hidden="true">*</span></label>
              <input class="vis-input" type="email" id="cf-email" name="email" autocomplete="email" required placeholder="your@email.com" />
            </div>
          </div>
          <div class="vis-form-group">
            <label class="vis-label" for="cf-category">Inquiry type <span aria-hidden="true">*</span></label>
            <select class="vis-input" id="cf-category" name="category" required>
              <option value="">Select a type…</option>
              <option value="Website Inquiry">Website Inquiry</option>
              <option value="Collaboration">Collaboration</option>
              <option value="Project Proposal">Project Proposal</option>
              <option value="Job Opportunity">Job Opportunity</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="vis-form-group">
            <label class="vis-label" for="cf-subject">Subject <span aria-hidden="true">*</span></label>
            <input class="vis-input" type="text" id="cf-subject" name="subject" required maxlength="255" placeholder="What is this about?" />
          </div>
          <div class="vis-form-group">
            <label class="vis-label" for="cf-message">Message <span aria-hidden="true">*</span></label>
            <textarea class="vis-input vis-textarea" id="cf-message" name="message" rows="5" required maxlength="5000" placeholder="Write your message here…"></textarea>
          </div>
          <p class="vis-form-status" id="cfStatus" aria-live="polite" hidden></p>
          <button type="submit" class="vis-submit-btn" id="cfSubmitBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
            </svg>
            Send Message
          </button>
        </form>
      </div>

    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════ FOOTER ══════════════════════════ -->
<footer class="vis-footer">
  <div class="vis-container">
    <p>&copy; <?= date('Y') ?> <?= esc($name) ?>. All rights reserved.</p>
  </div>
</footer>

<!-- ═══════════════════════════════════════ CV MODAL ═══════════════════════ -->
<div class="vis-cv-modal" id="visCvModal" role="dialog" aria-modal="true" aria-label="Curriculum Vitae" hidden>
  <div class="vis-cv-modal-backdrop" id="visCvBackdrop"></div>
  <div class="vis-cv-modal-box">
    <div class="vis-cv-modal-header">
      <span class="vis-cv-modal-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
        </svg>
        Curriculum Vitae
      </span>
      <div class="vis-cv-modal-actions">
        <a class="vis-cv-dl-btn" id="visCvDownload" href="#" download="curriculum-vitae.pdf" data-tooltip="Download PDF">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Download
        </a>
        <button type="button" class="vis-cv-close-btn" id="visCvClose" aria-label="Close CV viewer" data-tooltip="Close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
    </div>
    <div class="vis-cv-modal-body">
      <iframe class="vis-cv-iframe" id="visCvIframe" title="Curriculum Vitae" allowfullscreen></iframe>
    </div>
  </div>
</div>

<!-- Tooltip element (shared) -->
<div class="js-tooltip" role="tooltip" aria-hidden="true"></div>

<script src="./assets/js/visitor.js?v=4"></script>
</body>
</html>
