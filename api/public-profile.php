<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo  = db();
    $rows = $pdo->query(
        "SELECT entry_key, entry_type, entry_value FROM portfolio_entries"
    )->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        $data[$r['entry_key']] = $r['entry_type'] === 'json'
            ? json_decode((string) $r['entry_value'], true)
            : $r['entry_value'];
    }

    $s = (isset($data['social_links']) && is_array($data['social_links']))
        ? $data['social_links']
        : [];

    echo json_encode([
        'success' => true,
        'social'  => [
            'email'     => $s['email']     ?? $data['email']         ?? $data['contact_email']   ?? '',
            'github'    => $s['github']    ?? $data['github']        ?? $data['github_url']       ?? '',
            'linkedin'  => $s['linkedin']  ?? $data['linkedin']      ?? $data['linkedin_url']     ?? '',
            'facebook'  => $s['facebook']  ?? $data['facebook']      ?? $data['facebook_url']     ?? '',
            'instagram' => $s['instagram'] ?? $data['instagram']     ?? $data['instagram_url']    ?? '',
            'twitter'   => $s['twitter']   ?? $data['twitter']       ?? $data['twitter_url']      ?? '',
            'telegram'  => $s['telegram']  ?? $data['telegram']      ?? $data['telegram_url']     ?? '',
            'discord'   => $s['discord']   ?? $data['discord']       ?? $data['discord_url']      ?? '',
            'viber'     => $s['viber']     ?? $data['viber']         ?? $data['viber_url']        ?? '',
            'whatsapp'  => $s['whatsapp']  ?? $data['whatsapp']      ?? $data['whatsapp_url']     ?? '',
        ],
        'phones'  => [
            'dito'  => $data['phone_discord'] ?? $data['phone_1'] ?? '',
            'tnt'   => $data['phone_tnt']     ?? $data['phone_2'] ?? '',
            'globe' => $data['phone_globe']   ?? $data['phone_3'] ?? '',
        ],
        'name'   => $data['name']   ?? $data['about_name']  ?? '',
        'bio'    => $data['bio']    ?? $data['about_bio']   ?? '',
        'cv_url' => $data['cv_url'] ?? $data['about_cv_url'] ?? '',
    ]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load profile']);
}
