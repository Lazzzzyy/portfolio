<?php

declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_method('POST');

$data = request_data();

$name     = trim((string) ($data['name']     ?? ''));
$email    = normalize_email($data['email']   ?? '');
$category = trim((string) ($data['category'] ?? ''));
$subject  = trim((string) ($data['subject']  ?? ''));
$message  = trim((string) ($data['message']  ?? ''));

$allowed = ['Website Inquiry', 'Collaboration', 'Project Proposal', 'Job Opportunity', 'Other'];

$errors = [];

if ($name === '' || mb_strlen($name) > 120) {
    $errors[] = 'Name is required (max 120 characters).';
}
if ($email === '') {
    $errors[] = 'A valid email address is required.';
}
if (!in_array($category, $allowed, true)) {
    $errors[] = 'Please select a valid inquiry type.';
}
if ($subject === '' || mb_strlen($subject) > 255) {
    $errors[] = 'Subject is required (max 255 characters).';
}
if ($message === '' || mb_strlen($message) > 5000) {
    $errors[] = 'Message is required (max 5000 characters).';
}

if (!empty($errors)) {
    json_response(422, ['success' => false, 'errors' => $errors]);
}

try {
    $pdo  = db();
    $stmt = $pdo->prepare(
        'INSERT INTO contact_messages (name, email, category, subject, message, ip_address)
         VALUES (:name, :email, :category, :subject, :message, :ip)'
    );
    $stmt->execute([
        ':name'     => $name,
        ':email'    => $email,
        ':category' => $category,
        ':subject'  => $subject,
        ':message'  => $message,
        ':ip'       => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    json_response(200, ['success' => true, 'message' => 'Your message has been sent successfully.']);
} catch (\Exception $e) {
    json_response(500, ['success' => false, 'errors' => ['Server error. Please try again later.']]);
}
