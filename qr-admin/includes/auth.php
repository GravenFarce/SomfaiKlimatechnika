<?php
function require_auth(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['authenticated'])) {
        header('Location: ' . SITE_URL . '/qr-admin/');
        exit;
    }
}
