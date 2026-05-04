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

$file = $_FILES['cv'] ?? null;

if (!$file || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file received or upload error']);
    exit;
}

// Validate MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
if ($mime !== 'application/pdf') {
    ob_end_clean();
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Only PDF files are accepted']);
    exit;
}

// 10 MB limit
if ($file['size'] > 10 * 1024 * 1024) {
    ob_end_clean();
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'File exceeds the 10 MB limit']);
    exit;
}

$cloudName = (string) ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? getenv('CLOUDINARY_CLOUD_NAME') ?? '');
$apiKey    = (string) ($_ENV['CLOUDINARY_API_KEY']    ?? getenv('CLOUDINARY_API_KEY')    ?? '');
$apiSecret = (string) ($_ENV['CLOUDINARY_API_SECRET'] ?? getenv('CLOUDINARY_API_SECRET') ?? '');

if ($cloudName === '' || $apiKey === '' || $apiSecret === '') {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Cloudinary credentials not configured']);
    exit;
}

$timestamp = time();
$publicId  = 'portfolio/cv/curriculum-vitae';
$paramsStr = 'public_id=' . $publicId . '&timestamp=' . $timestamp;
$signature = hash('sha256', $paramsStr . $apiSecret);

$endpoint = 'https://api.cloudinary.com/v1_1/' . $cloudName . '/raw/upload';

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        'file'      => new CURLFile($file['tmp_name'], 'application/pdf', 'curriculum-vitae.pdf'),
        'api_key'   => $apiKey,
        'timestamp' => $timestamp,
        'public_id' => $publicId,
        'signature' => $signature,
    ],
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr !== '' || $httpCode !== 200) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to upload to Cloudinary']);
    exit;
}

$result   = json_decode((string) $response, true);
$cvUrl    = (string) ($result['secure_url'] ?? '');

if ($cvUrl === '') {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Cloudinary did not return a URL']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo  = db();
    $stmt = $pdo->prepare(
        "INSERT INTO portfolio_entries (entry_key, entry_type, entry_value)
         VALUES ('cv_url', 'text', :v)
         ON DUPLICATE KEY UPDATE entry_value = VALUES(entry_value)"
    );
    $stmt->execute([':v' => $cvUrl]);
} catch (\Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error while saving CV path']);
    exit;
}

ob_end_clean();
echo json_encode([
    'success' => true,
    'message' => 'CV uploaded successfully',
    'cv_url'  => $cvUrl . '?v=' . time(),
]);
