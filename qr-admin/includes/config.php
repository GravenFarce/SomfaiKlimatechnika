<?php
// Database — fill in after creating DB in cPanel
define('DB_HOST', 'localhost');
define('DB_NAME', 'CHANGE_ME');
define('DB_USER', 'CHANGE_ME');
define('DB_PASS', 'CHANGE_ME');

// AES-256 key — must be exactly 32 bytes. Change before deploying.
define('ENCRYPTION_KEY', 'SomfaiQR__SecretKey__32Bytes!!!!');

// Email
define('ADMIN_EMAIL', 'varga.ferenc88@gmail.com');
define('SMTP_HOST',   'mail.somfaiklimatechnika.hu');
define('SMTP_PORT',   587);
define('SMTP_USER',   'CHANGE_ME'); // e.g. admin@somfaiklimatechnika.hu
define('SMTP_PASS',   'CHANGE_ME');

// Site
define('SITE_URL', 'https://somfaiklimatechnika.hu');
