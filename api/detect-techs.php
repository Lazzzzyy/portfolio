<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
session_start();
ob_start();
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

$user = $_SESSION['auth_user'] ?? null;
if (!is_array($user)) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$file = $_FILES['project'] ?? null;
if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file received or upload error']);
    exit;
}

// Validate MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
$zipMimes = ['application/zip', 'application/x-zip', 'application/x-zip-compressed', 'application/octet-stream'];
if (!in_array($mime, $zipMimes, true)) {
    ob_end_clean();
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Only ZIP archives are accepted']);
    exit;
}

// 100 MB limit
if ($file['size'] > 100 * 1024 * 1024) {
    ob_end_clean();
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'File exceeds the 100 MB limit']);
    exit;
}

if (!class_exists('ZipArchive')) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ZipArchive extension not available on this server']);
    exit;
}

$zip = new ZipArchive();
if ($zip->open($file['tmp_name']) !== true) {
    ob_end_clean();
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Could not open ZIP archive — file may be corrupted']);
    exit;
}

if ($zip->numFiles > 5000) {
    $zip->close();
    ob_end_clean();
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'ZIP contains too many files (max 5 000). Remove vendor/node_modules before zipping.']);
    exit;
}

// ── Collect all entry names ────────────────────────────────────────────────

$SKIP_DIRS = ['vendor/', 'node_modules/', '.git/', 'dist/', 'build/', '.svn/', '__pycache__/'];

$allPaths  = [];   // all relative paths inside zip (filtered)
$fileNames = [];   // basenames only
$configMap = [];   // basename → zip index  (for cheap content reads)

for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    if ($name === false) continue;

    // Normalize: strip leading top-level folder if present (e.g. "myproject/src/…" → "src/…")
    $parts = explode('/', $name, 3);
    $rel   = count($parts) >= 2 ? implode('/', array_slice($parts, 1)) : $name;

    // Skip ignored directories
    $skip = false;
    foreach ($SKIP_DIRS as $d) {
        if (str_starts_with($rel, $d) || str_contains('/' . $rel . '/', '/' . rtrim($d, '/') . '/')) {
            $skip = true;
            break;
        }
    }
    if ($skip || str_ends_with($rel, '/')) continue;

    $allPaths[]               = $rel;
    $base                     = basename($rel);
    $fileNames[$base]         = true;
    $configMap[$base]         = $i;    // last one wins for duplicates; good enough
}

$zip->close();

// Re-open for content reads
$zip->open($file['tmp_name']);

// ── Detection state ────────────────────────────────────────────────────────

$found = [];

function mark(string ...$names): void
{
    global $found;
    foreach ($names as $n) {
        $found[$n] = true;
    }
}

function readEntry(ZipArchive $z, string $basename): string
{
    global $configMap;
    if (!isset($configMap[$basename])) return '';
    $content = $z->getFromIndex($configMap[$basename]);
    return $content === false ? '' : $content;
}

// ── 1. Extension scan ──────────────────────────────────────────────────────

$EXT_MAP = [
    'php'    => 'PHP',
    'py'     => 'Python',
    'java'   => 'Java',
    'kt'     => 'Kotlin',
    'kts'    => 'Kotlin',
    'swift'  => 'Swift',
    'rb'     => 'Ruby',
    'go'     => 'Go',
    'rs'     => 'Rust',
    'dart'   => 'Dart',
    'cs'     => 'C#',
    'ts'     => 'TypeScript',
    'tsx'    => ['TypeScript', 'React'],
    'jsx'    => ['JavaScript', 'React'],
    'js'     => 'JavaScript',
    'html'   => 'HTML',
    'htm'    => 'HTML',
    'css'    => 'CSS',
    'scss'   => 'Sass',
    'sass'   => 'Sass',
    'vue'    => 'Vue.js',
    'svelte' => 'Svelte',
];

foreach ($allPaths as $rel) {
    // blade.php → HTML (Laravel/Blade templates)
    if (str_ends_with(strtolower($rel), '.blade.php')) {
        mark('HTML');
        continue;
    }
    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    if (!isset($EXT_MAP[$ext])) continue;
    $val = $EXT_MAP[$ext];
    if (is_array($val)) {
        mark(...$val);
    } else {
        mark($val);
    }
}

// ── 2. Config-file-based detection ────────────────────────────────────────

// composer.json
if (isset($fileNames['composer.json'])) {
    mark('PHP');
    $json = json_decode(readEntry($zip, 'composer.json'), true) ?? [];
    $pkgs = array_keys(array_merge($json['require'] ?? [], $json['require-dev'] ?? []));
    foreach ($pkgs as $pkg) {
        $p = strtolower($pkg);
        if (str_contains($p, 'laravel/framework') || str_contains($p, 'laravel/laravel')) mark('PHP', 'Laravel');
        if (str_contains($p, 'symfony/'))     mark('PHP');
        if (str_contains($p, 'doctrine/'))    mark('PHP');
    }
}

// artisan → Laravel
if (isset($fileNames['artisan'])) mark('PHP', 'Laravel');

// package.json
if (isset($fileNames['package.json'])) {
    $json = json_decode(readEntry($zip, 'package.json'), true) ?? [];
    $pkgs = array_map('strtolower', array_keys(array_merge(
        $json['dependencies'] ?? [],
        $json['devDependencies'] ?? []
    )));

    foreach ($pkgs as $p) {
        if ($p === 'react' || $p === 'react-dom')                            mark('React', 'JavaScript');
        if ($p === 'react-native')                                            mark('React Native');
        if ($p === 'vue' || str_starts_with($p, '@vue/'))                    mark('Vue.js');
        if ($p === '@angular/core')                                           mark('Angular', 'TypeScript');
        if ($p === 'svelte')                                                  mark('Svelte');
        if ($p === 'next')                                                    mark('Next.js', 'React');
        if ($p === 'nuxt' || str_starts_with($p, '@nuxt/'))                  mark('Nuxt.js', 'Vue.js');
        if ($p === 'express')                                                 mark('Express', 'Node.js');
        if ($p === 'fastify' || $p === 'koa' || $p === 'hapi')               mark('Node.js');
        if ($p === 'bootstrap')                                               mark('Bootstrap');
        if ($p === 'tailwindcss')                                             mark('Tailwind CSS');
        if ($p === 'jquery')                                                  mark('jQuery');
        if ($p === 'alpinejs')                                                mark('Alpine.js');
        if ($p === 'sass' || $p === 'node-sass' || $p === 'dart-sass')       mark('Sass');
        if ($p === 'firebase' || str_starts_with($p, 'firebase/'))           mark('Firebase');
        if ($p === '@supabase/supabase-js')                                   mark('Supabase');
        if ($p === 'mongoose' || $p === 'mongodb')                            mark('MongoDB');
        if ($p === 'redis' || $p === 'ioredis')                              mark('Redis');
        if ($p === 'pg' || $p === 'node-postgres')                           mark('PostgreSQL');
        if ($p === 'mysql' || $p === 'mysql2')                               mark('MySQL');
        if ($p === 'better-sqlite3' || $p === 'sqlite3')                     mark('SQLite');
        if (str_starts_with($p, '@azure/'))                                  mark('Azure');
        if (str_starts_with($p, 'aws-sdk') || str_starts_with($p, '@aws-sdk/')) mark('AWS');
        if ($p === 'typescript')                                              mark('TypeScript');
    }

    // Has package.json + JS/TS → implies Node.js
    if (isset($found['JavaScript']) || isset($found['TypeScript'])) {
        mark('Node.js');
    }
}

// requirements.txt
if (isset($fileNames['requirements.txt'])) {
    mark('Python');
    $content = strtolower(readEntry($zip, 'requirements.txt'));
    if (str_contains($content, 'django'))   mark('Django');
    if (str_contains($content, 'flask'))    mark('Flask');
    if (str_contains($content, 'fastapi'))  mark('Python');
    if (str_contains($content, 'sqlalchemy') || str_contains($content, 'pymysql')) mark('MySQL');
    if (str_contains($content, 'psycopg'))  mark('PostgreSQL');
    if (str_contains($content, 'redis'))    mark('Redis');
    if (str_contains($content, 'pymongo'))  mark('MongoDB');
}

// manage.py → Django
if (isset($fileNames['manage.py'])) mark('Django', 'Python');

// Pipfile / pyproject.toml
if (isset($fileNames['Pipfile']) || isset($fileNames['pyproject.toml'])) mark('Python');

// Gemfile → Ruby / Rails
if (isset($fileNames['Gemfile'])) {
    mark('Ruby');
    $content = strtolower(readEntry($zip, 'Gemfile'));
    if (str_contains($content, 'rails')) mark('Rails', 'Ruby');
}

// go.mod
if (isset($fileNames['go.mod'])) mark('Go');

// Cargo.toml
if (isset($fileNames['Cargo.toml'])) mark('Rust');

// pubspec.yaml → Flutter / Dart
if (isset($fileNames['pubspec.yaml'])) mark('Flutter', 'Dart');

// pom.xml → Java / Spring
if (isset($fileNames['pom.xml'])) {
    mark('Java');
    $content = strtolower(readEntry($zip, 'pom.xml'));
    if (str_contains($content, 'spring')) mark('Spring');
    if (str_contains($content, 'mysql'))  mark('MySQL');
    if (str_contains($content, 'postgresql') || str_contains($content, 'postgres')) mark('PostgreSQL');
    if (str_contains($content, 'mongodb')) mark('MongoDB');
    if (str_contains($content, 'redis'))  mark('Redis');
}

// build.gradle / build.gradle.kts
if (isset($fileNames['build.gradle']) || isset($fileNames['build.gradle.kts'])) {
    if (!isset($found['Kotlin'])) mark('Java');
    $name = isset($fileNames['build.gradle.kts']) ? 'build.gradle.kts' : 'build.gradle';
    $content = strtolower(readEntry($zip, $name));
    if (str_contains($content, 'spring'))     mark('Spring');
    if (str_contains($content, 'mysql'))      mark('MySQL');
    if (str_contains($content, 'postgresql')) mark('PostgreSQL');
    if (str_contains($content, 'mongodb'))    mark('MongoDB');
    if (str_contains($content, 'redis'))      mark('Redis');
}

// tailwind.config.*
foreach (['tailwind.config.js', 'tailwind.config.ts', 'tailwind.config.cjs'] as $tc) {
    if (isset($fileNames[$tc])) { mark('Tailwind CSS'); break; }
}

// next.config.*
if (isset($fileNames['next.config.js']) || isset($fileNames['next.config.ts']) || isset($fileNames['next.config.mjs'])) {
    mark('Next.js', 'React');
}

// nuxt.config.*
if (isset($fileNames['nuxt.config.js']) || isset($fileNames['nuxt.config.ts'])) {
    mark('Nuxt.js', 'Vue.js');
}

// svelte.config.js
if (isset($fileNames['svelte.config.js'])) mark('Svelte');

// angular.json
if (isset($fileNames['angular.json'])) mark('Angular', 'TypeScript');

// Docker
if (isset($fileNames['Dockerfile']) || isset($fileNames['docker-compose.yml']) || isset($fileNames['docker-compose.yaml'])) {
    mark('Docker');
}

// nginx.conf
if (isset($fileNames['nginx.conf'])) mark('Nginx');

// .env.example → database & other tech hints
if (isset($fileNames['.env.example'])) {
    $content = strtolower(readEntry($zip, '.env.example'));
    if (str_contains($content, 'db_connection=mysql') || str_contains($content, 'db_connection = mysql'))  mark('MySQL');
    if (str_contains($content, 'db_connection=pgsql') || str_contains($content, 'postgresql'))             mark('PostgreSQL');
    if (str_contains($content, 'db_connection=sqlite'))                                                    mark('SQLite');
    if (str_contains($content, 'redis_host') || str_contains($content, 'redis_url'))                       mark('Redis');
    if (str_contains($content, 'mongodb'))                                                                  mark('MongoDB');
    if (str_contains($content, 'aws_access_key') || str_contains($content, 's3_bucket'))                  mark('AWS');
    if (str_contains($content, 'azure'))                                                                    mark('Azure');
    if (str_contains($content, 'firebase'))                                                                 mark('Firebase');
    if (str_contains($content, 'supabase'))                                                                 mark('Supabase');
}

// config/database.php → database hints
if (isset($fileNames['database.php'])) {
    $content = strtolower(readEntry($zip, 'database.php'));
    if (str_contains($content, "'mysql'") || str_contains($content, '"mysql"'))       mark('MySQL');
    if (str_contains($content, "'pgsql'") || str_contains($content, '"pgsql"'))       mark('PostgreSQL');
    if (str_contains($content, "'sqlite'") || str_contains($content, '"sqlite"'))     mark('SQLite');
    if (str_contains($content, "'mongodb'") || str_contains($content, '"mongodb"'))   mark('MongoDB');
}

// .gitignore / .git → Git
if (isset($fileNames['.gitignore'])) mark('Git');

// .env files don't reveal tech, skip

// SQL files → database hints
foreach ($allPaths as $rel) {
    if (!str_ends_with(strtolower($rel), '.sql')) continue;
    $content = strtolower($zip->getFromIndex($configMap[basename($rel)] ?? -1) ?: '');
    if (str_contains($content, 'engine=innodb') || str_contains($content, 'auto_increment') || str_contains($content, 'mysql')) {
        mark('MySQL');
    }
    if (str_contains($content, 'sqlite')) mark('SQLite');
    if (str_contains($content, 'pg_dump') || str_contains($content, 'postgresql')) mark('PostgreSQL');
}

$zip->close();

$technologies = array_values(array_keys($found));
sort($technologies);

ob_end_clean();
echo json_encode(['success' => true, 'technologies' => $technologies]);
