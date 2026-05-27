<?php
// Run ONCE by visiting: https://somfaiklimatechnika.hu/qr-admin/setup/install.php
// The setup/.htaccess blocks it after first run — temporarily remove that file to re-run.
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/crypto.php';

$db = get_db();

$db->exec("CREATE TABLE IF NOT EXISTS users (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    password_hash      VARCHAR(255) NOT NULL,
    password_encrypted TEXT         NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS qr_codes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    short_code      VARCHAR(10)  NOT NULL UNIQUE,
    name            VARCHAR(255) NOT NULL,
    destination_url TEXT         NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS scans (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    qr_code_id  INT          NOT NULL,
    scanned_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    city        VARCHAR(255) NOT NULL DEFAULT '',
    device      VARCHAR(50)  NOT NULL DEFAULT '',
    FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$count = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($count === 0) {
    $initial = 'password1';
    $stmt = $db->prepare('INSERT INTO users (password_hash, password_encrypted) VALUES (?, ?)');
    $stmt->execute([
        password_hash($initial, PASSWORD_BCRYPT),
        aes_encrypt($initial),
    ]);
    echo "Setup complete. Initial password: password1";
} else {
    echo "Already set up — skipping user seed.";
}
