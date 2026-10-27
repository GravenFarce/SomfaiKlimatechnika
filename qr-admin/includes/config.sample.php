<?php
// Copy to config.php and fill in. config.php is git-ignored and excluded from the deploy.

// Database — fill in after creating DB in cPanel
define('DB_HOST', 'localhost');
define('DB_NAME', 'CHANGE_ME');
define('DB_USER', 'CHANGE_ME');
define('DB_PASS', 'CHANGE_ME');

// AES-256 key — must be exactly 32 bytes.
define('ENCRYPTION_KEY', 'CHANGE_ME_TO_32_BYTES_EXACTLY!!!');

// Email
define('ADMIN_EMAIL', 'admin@example.com');

// Site
define('SITE_URL', 'https://somfaiklimatechnika.hu');
