<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_auth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    get_db()->prepare('DELETE FROM qr_codes WHERE id = ?')->execute([$id]);
}
header('Location: dashboard.php');
exit;
