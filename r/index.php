<?php
require_once __DIR__ . '/../qr-admin/includes/config.php';
require_once __DIR__ . '/../qr-admin/includes/db.php';
require_once __DIR__ . '/../qr-admin/includes/qr_helpers.php';

$code = preg_replace('/[^a-z0-9]/', '', strtolower($_GET['code'] ?? ''));

if (!$code) {
    http_response_code(404);
    exit('Not found');
}

$stmt = get_db()->prepare('SELECT id, destination_url FROM qr_codes WHERE short_code = ?');
$stmt->execute([$code]);
$qr = $stmt->fetch();

if (!$qr) {
    http_response_code(404);
    exit('Not found');
}

$ip     = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0];
$city   = get_city(trim($ip));
$device = detect_device($_SERVER['HTTP_USER_AGENT'] ?? '');

get_db()->prepare('INSERT INTO scans (qr_code_id, city, device) VALUES (?, ?, ?)')
        ->execute([$qr['id'], $city, $device]);

header('Location: ' . $qr['destination_url'], true, 301);
exit;
