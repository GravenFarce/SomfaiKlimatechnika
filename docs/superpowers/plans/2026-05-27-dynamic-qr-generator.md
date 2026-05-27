# Dynamic QR Code Generator — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a private, password-protected PHP admin tool at `somfaiklimatechnika.hu/qr-admin/` for creating and managing dynamic QR codes, with per-scan analytics (city, device, timestamp).

**Architecture:** Server-rendered PHP pages gated by PHP sessions. MySQL stores QR codes and scan data. The redirect handler at `/r/{code}` logs scans and issues an instant 301 redirect. PHPMailer sends password reminder emails to varga.ferenc88@gmail.com.

**Tech Stack:** PHP 8.1+, MySQL 5.7+, PHPMailer 6.x, PHPUnit 11.x, qrcode.js 1.0 (CDN), ip-api.com (free IP geolocation)

---

## File Map

| File | Purpose |
|------|---------|
| `composer.json` | Dependencies: PHPMailer (prod), PHPUnit (dev) |
| `phpunit.xml` | PHPUnit config |
| `tests/CryptoTest.php` | Unit tests: AES encrypt/decrypt |
| `tests/QrHelpersTest.php` | Unit tests: detect_device |
| `qr-admin/includes/config.php` | DB creds, SMTP, encryption key, constants |
| `qr-admin/includes/db.php` | PDO singleton: `get_db(): PDO` |
| `qr-admin/includes/crypto.php` | `aes_encrypt()`, `aes_decrypt()` |
| `qr-admin/includes/auth.php` | `require_auth()` — session gate |
| `qr-admin/includes/qr_helpers.php` | `generate_short_code()`, `detect_device()`, `get_city()` |
| `qr-admin/includes/mailer.php` | `send_password_reminder()` — PHPMailer wrapper |
| `qr-admin/includes/sidebar.php` | Shared sidebar HTML include |
| `qr-admin/setup/install.php` | One-time: creates tables, seeds `password1` |
| `qr-admin/css/style.css` | Admin UI styles |
| `qr-admin/index.php` | Login page |
| `qr-admin/dashboard.php` | QR code list with scan counts |
| `qr-admin/edit.php` | Create and edit QR codes |
| `qr-admin/delete.php` | Delete QR code + scans |
| `qr-admin/analytics.php` | Per-QR scan stats |
| `qr-admin/change-password.php` | Change password form |
| `qr-admin/logout.php` | Destroys session |
| `r/index.php` | Redirect handler: logs scan + 301 redirect |
| `r/.htaccess` | Rewrites `/r/{code}` → `r/index.php?code={code}` |
| `qr-admin/.htaccess` | Disables directory listing |
| `qr-admin/includes/.htaccess` | Blocks direct access to includes |
| `qr-admin/setup/.htaccess` | Blocks direct access to setup |

---

### Task 1: Composer setup

**Files:**
- Create: `composer.json`
- Create: `phpunit.xml`
- Create: `.gitignore` (update)

- [ ] **Step 1: Check Composer is available**

```powershell
composer --version
```
Expected: `Composer version 2.x.x ...`
If missing, download installer from getcomposer.org and install.

- [ ] **Step 2: Create composer.json**

Create `composer.json` in project root:
```json
{
    "name": "somfai/qr-admin",
    "type": "project",
    "require": {
        "phpmailer/phpmailer": "^6.9"
    },
    "require-dev": {
        "phpunit/phpunit": "^11"
    }
}
```

- [ ] **Step 3: Install dependencies**

```powershell
composer install
```
Expected: `vendor/` directory created with `vendor/autoload.php`.

- [ ] **Step 4: Create phpunit.xml**

Create `phpunit.xml`:
```xml
<?xml version="1.0"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

- [ ] **Step 5: Add vendor/ to .gitignore**

Append to `.gitignore` (create if missing):
```
/vendor/
```

- [ ] **Step 6: Create tests/ directory and commit**

```powershell
New-Item -ItemType Directory -Force tests
git add composer.json composer.lock phpunit.xml .gitignore
git commit -m "chore: add Composer with PHPMailer and PHPUnit"
```

---

### Task 2: Directory structure + config + DB connection

**Files:**
- Create: `qr-admin/includes/config.php`
- Create: `qr-admin/includes/db.php`

- [ ] **Step 1: Create directories**

```powershell
New-Item -ItemType Directory -Force "qr-admin/includes"
New-Item -ItemType Directory -Force "qr-admin/css"
New-Item -ItemType Directory -Force "qr-admin/setup"
New-Item -ItemType Directory -Force "r"
```

- [ ] **Step 2: Create config.php**

Create `qr-admin/includes/config.php`:
```php
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
```

- [ ] **Step 3: Create db.php**

Create `qr-admin/includes/db.php`:
```php
<?php
function get_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}
```

- [ ] **Step 4: Commit**

```powershell
git add "qr-admin/"
git commit -m "feat: add config and DB connection"
```

---

### Task 3: Install script

**Files:**
- Create: `qr-admin/setup/install.php`

- [ ] **Step 1: Create install.php**

Create `qr-admin/setup/install.php`:
```php
<?php
// Run ONCE by visiting: https://somfaiklimatechnika.hu/qr-admin/setup/install.php
// The setup/.htaccess blocks it after first run — remove that file to re-run.
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/crypto.php';

$db = get_db();

$db->exec("CREATE TABLE IF NOT EXISTS users (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    password_hash    VARCHAR(255) NOT NULL,
    password_encrypted TEXT NOT NULL
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
```

- [ ] **Step 2: Commit**

```powershell
git add "qr-admin/setup/install.php"
git commit -m "feat: add DB install script"
```

---

### Task 4: Crypto utilities + tests

**Files:**
- Create: `qr-admin/includes/crypto.php`
- Create: `tests/CryptoTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/CryptoTest.php`:
```php
<?php
use PHPUnit\Framework\TestCase;

if (!defined('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', 'TestKey_32BytesLong_ForUnitTests!');
}
require_once __DIR__ . '/../qr-admin/includes/crypto.php';

class CryptoTest extends TestCase
{
    public function test_encrypt_then_decrypt_returns_original(): void
    {
        $this->assertSame('password1', aes_decrypt(aes_encrypt('password1')));
    }

    public function test_encrypt_produces_different_ciphertext_each_time(): void
    {
        $this->assertNotSame(aes_encrypt('password1'), aes_encrypt('password1'));
    }

    public function test_output_is_valid_base64(): void
    {
        $this->assertNotFalse(base64_decode(aes_encrypt('test'), true));
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```powershell
.\vendor\bin\phpunit tests\CryptoTest.php --testdox
```
Expected: FAIL — `Call to undefined function aes_encrypt()`

- [ ] **Step 3: Create crypto.php**

Create `qr-admin/includes/crypto.php`:
```php
<?php
function aes_encrypt(string $plaintext): string
{
    $iv = random_bytes(16);
    return base64_encode($iv . openssl_encrypt($plaintext, 'AES-256-CBC', ENCRYPTION_KEY, 0, $iv));
}

function aes_decrypt(string $ciphertext): string
{
    $data = base64_decode($ciphertext);
    return openssl_decrypt(substr($data, 16), 'AES-256-CBC', ENCRYPTION_KEY, 0, substr($data, 0, 16));
}
```

- [ ] **Step 4: Run — expect PASS**

```powershell
.\vendor\bin\phpunit tests\CryptoTest.php --testdox
```
Expected: 3 tests, 3 passed.

- [ ] **Step 5: Commit**

```powershell
git add "qr-admin/includes/crypto.php" "tests/CryptoTest.php"
git commit -m "feat: add AES-256 crypto helpers with tests"
```

---

### Task 5: QR helpers + tests

**Files:**
- Create: `qr-admin/includes/qr_helpers.php`
- Create: `tests/QrHelpersTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/QrHelpersTest.php`:
```php
<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../qr-admin/includes/qr_helpers.php';

class QrHelpersTest extends TestCase
{
    public function test_iphone_is_mobile(): void
    {
        $ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';
        $this->assertSame('mobile', detect_device($ua));
    }

    public function test_ipad_is_tablet(): void
    {
        $ua = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';
        $this->assertSame('tablet', detect_device($ua));
    }

    public function test_windows_chrome_is_desktop(): void
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36';
        $this->assertSame('desktop', detect_device($ua));
    }

    public function test_android_mobile_is_mobile(): void
    {
        $ua = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/124.0 Mobile Safari/537.36';
        $this->assertSame('mobile', detect_device($ua));
    }
}
```

- [ ] **Step 2: Run — expect FAIL**

```powershell
.\vendor\bin\phpunit tests\QrHelpersTest.php --testdox
```
Expected: FAIL — `Call to undefined function detect_device()`

- [ ] **Step 3: Create qr_helpers.php**

Create `qr-admin/includes/qr_helpers.php`:
```php
<?php
function detect_device(string $user_agent): string
{
    $ua = strtolower($user_agent);
    if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
        return 'tablet';
    }
    if (str_contains($ua, 'iphone') || str_contains($ua, 'android') || str_contains($ua, 'mobile')) {
        return 'mobile';
    }
    return 'desktop';
}

function generate_short_code(PDO $db): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    for ($i = 0; $i < 10; $i++) {
        $code = '';
        for ($j = 0; $j < 6; $j++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $db->prepare('SELECT id FROM qr_codes WHERE short_code = ?');
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            return $code;
        }
    }
    throw new RuntimeException('Could not generate a unique short code after 10 attempts.');
}

function get_city(string $ip): string
{
    $url      = 'http://ip-api.com/json/' . urlencode($ip) . '?fields=city&lang=hu';
    $response = @file_get_contents($url);
    if ($response === false) {
        return 'Ismeretlen';
    }
    $data = json_decode($response, true);
    return $data['city'] ?? 'Ismeretlen';
}
```

- [ ] **Step 4: Run all tests — expect PASS**

```powershell
.\vendor\bin\phpunit --testdox
```
Expected: 7 tests, 7 passed.

- [ ] **Step 5: Commit**

```powershell
git add "qr-admin/includes/qr_helpers.php" "tests/QrHelpersTest.php"
git commit -m "feat: add QR helper functions with tests"
```

---

### Task 6: Auth helper + sidebar

**Files:**
- Create: `qr-admin/includes/auth.php`
- Create: `qr-admin/includes/sidebar.php`

- [ ] **Step 1: Create auth.php**

Create `qr-admin/includes/auth.php`:
```php
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
```

- [ ] **Step 2: Create sidebar.php**

Create `qr-admin/includes/sidebar.php`:
```php
<aside class="sidebar">
  <a href="dashboard.php" class="brand">QR Admin</a>
  <nav>
    <a href="dashboard.php">Irányítópult</a>
    <a href="edit.php">Új QR kód</a>
    <a href="change-password.php">Jelszó módosítás</a>
  </nav>
  <div class="logout">
    <a href="logout.php" class="btn btn-ghost" style="width:100%;text-align:center">Kijelentkezés</a>
  </div>
</aside>
```

- [ ] **Step 3: Commit**

```powershell
git add "qr-admin/includes/auth.php" "qr-admin/includes/sidebar.php"
git commit -m "feat: add auth helper and sidebar include"
```

---

### Task 7: CSS

**Files:**
- Create: `qr-admin/css/style.css`

- [ ] **Step 1: Create style.css**

Create `qr-admin/css/style.css`:
```css
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; color: #222; min-height: 100vh; }

/* Auth pages */
.auth-wrap { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
.auth-card { background: #fff; border-radius: 10px; box-shadow: 0 2px 16px rgba(0,0,0,.1); padding: 2.5rem 2rem; width: 100%; max-width: 380px; }
.auth-card h1 { font-size: 1.3rem; margin-bottom: 1.5rem; text-align: center; }

/* Layout */
.layout { display: flex; min-height: 100vh; }
.sidebar { width: 220px; background: #1e2a38; color: #cdd6e0; padding: 1.5rem 1rem; flex-shrink: 0; display: flex; flex-direction: column; }
.sidebar .brand { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 2rem; display: block; text-decoration: none; }
.sidebar nav a { display: block; padding: .5rem .75rem; border-radius: 6px; color: #cdd6e0; text-decoration: none; margin-bottom: .25rem; font-size: .9rem; }
.sidebar nav a:hover { background: #2e3f52; color: #fff; }
.sidebar .logout { margin-top: auto; padding-top: 2rem; }
.main { flex: 1; padding: 2rem; overflow-x: auto; }
.main h2 { font-size: 1.4rem; margin-bottom: 1.5rem; }

/* Forms */
.form-group { margin-bottom: 1rem; }
label { display: block; font-size: .85rem; font-weight: 600; margin-bottom: .35rem; }
input[type=text], input[type=url], input[type=password] { width: 100%; padding: .55rem .75rem; border: 1px solid #ccc; border-radius: 6px; font-size: .95rem; }
input:focus { outline: none; border-color: #3b7dd8; box-shadow: 0 0 0 2px rgba(59,125,216,.2); }

/* Buttons */
.btn { display: inline-block; padding: .55rem 1.25rem; border-radius: 6px; border: none; cursor: pointer; font-size: .9rem; font-weight: 600; text-decoration: none; transition: opacity .15s; }
.btn:hover { opacity: .85; }
.btn-primary { background: #3b7dd8; color: #fff; }
.btn-danger  { background: #e74c3c; color: #fff; }
.btn-ghost   { background: transparent; border: 1px solid #ccc; color: #444; }

/* Table */
table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 6px rgba(0,0,0,.07); }
th, td { padding: .75rem 1rem; text-align: left; font-size: .9rem; }
th { background: #f0f3f7; font-weight: 700; border-bottom: 1px solid #e0e6ef; }
tr + tr td { border-top: 1px solid #f0f3f7; }
td a { color: #3b7dd8; text-decoration: none; }
td a:hover { text-decoration: underline; }

/* Alerts */
.alert { padding: .75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: .9rem; }
.alert-error   { background: #fde8e8; color: #c0392b; border: 1px solid #f5c6cb; }
.alert-success { background: #e8f7ee; color: #1e7e34; border: 1px solid #b8dfc8; }

/* QR preview */
.qr-box { margin-top: 1.5rem; }
.qr-box canvas { display: block; }
.qr-box .qr-url { font-size: .8rem; color: #666; margin-top: .5rem; word-break: break-all; }

/* Password reminder */
.reminder-link { display: block; margin-top: .5rem; font-size: .8rem; text-align: center; color: #3b7dd8; cursor: pointer; background: none; border: none; text-decoration: none; }
.reminder-link:hover { text-decoration: underline; }
```

- [ ] **Step 2: Commit**

```powershell
git add "qr-admin/css/style.css"
git commit -m "feat: add admin CSS"
```

---

### Task 8: Login page

**Files:**
- Create: `qr-admin/index.php`

- [ ] **Step 1: Create index.php**

Create `qr-admin/index.php`:
```php
<?php
session_start();

if (!empty($_SESSION['authenticated'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/crypto.php';
require_once __DIR__ . '/includes/mailer.php';

$error = '';
$info  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remind'])) {
        $user = get_db()->query('SELECT password_encrypted FROM users LIMIT 1')->fetch();
        if ($user) {
            send_password_reminder(aes_decrypt($user['password_encrypted']));
        }
        $info = 'A jelszó el lett küldve a megadott email-címre.';
    } elseif (isset($_POST['password'])) {
        $user = get_db()->query('SELECT password_hash FROM users LIMIT 1')->fetch();
        if ($user && password_verify($_POST['password'], $user['password_hash'])) {
            $_SESSION['authenticated'] = true;
            header('Location: dashboard.php');
            exit;
        }
        $error = 'Hibás jelszó.';
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>QR Admin – Bejelentkezés</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <h1>QR Admin</h1>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($info): ?>
      <div class="alert alert-success"><?= htmlspecialchars($info) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="password">Jelszó</label>
        <input type="password" id="password" name="password" autofocus required/>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%">Belépés</button>
    </form>

    <form method="POST" style="margin-top:.5rem">
      <button type="submit" name="remind" value="1" class="reminder-link">
        Elfelejtette jelszavát?
      </button>
    </form>
  </div>
</div>
</body>
</html>
```

- [ ] **Step 2: Commit**

```powershell
git add "qr-admin/index.php"
git commit -m "feat: add login page"
```

---

### Task 9: PHPMailer wrapper

**Files:**
- Create: `qr-admin/includes/mailer.php`

- [ ] **Step 1: Create mailer.php**

Create `qr-admin/includes/mailer.php`:
```php
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_password_reminder(string $password): bool
{
    require_once __DIR__ . '/../../vendor/autoload.php';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_USER, 'QR Admin');
        $mail->addAddress(ADMIN_EMAIL);
        $mail->Subject = 'QR Admin – Jelszó emlékeztető';
        $mail->Body    = "A jelenlegi QR Admin jelszava: {$password}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer error: ' . $e->getMessage());
        return false;
    }
}
```

- [ ] **Step 2: Commit**

```powershell
git add "qr-admin/includes/mailer.php"
git commit -m "feat: add PHPMailer password reminder"
```

---

### Task 10: Logout

**Files:**
- Create: `qr-admin/logout.php`

- [ ] **Step 1: Create logout.php**

Create `qr-admin/logout.php`:
```php
<?php
session_start();
session_destroy();
header('Location: index.php');
exit;
```

- [ ] **Step 2: Commit**

```powershell
git add "qr-admin/logout.php"
git commit -m "feat: add logout"
```

---

### Task 11: Dashboard

**Files:**
- Create: `qr-admin/dashboard.php`

- [ ] **Step 1: Create dashboard.php**

Create `qr-admin/dashboard.php`:
```php
<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_auth();

$codes = get_db()->query(
    'SELECT q.id, q.short_code, q.name, q.destination_url, q.created_at,
            COUNT(s.id) AS scan_count
     FROM qr_codes q
     LEFT JOIN scans s ON s.qr_code_id = q.id
     GROUP BY q.id
     ORDER BY q.created_at DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>QR Admin – Irányítópult</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
<div class="layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">
    <h2>QR kódok</h2>
    <p style="margin-bottom:1rem">
      <a href="edit.php" class="btn btn-primary">+ Új QR kód</a>
    </p>
    <?php if (empty($codes)): ?>
      <p>Még nincs QR kód. Hozzon létre egyet!</p>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Név</th>
          <th>Cél URL</th>
          <th>Rövid link</th>
          <th>Beolvasások</th>
          <th>Létrehozva</th>
          <th>Műveletek</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($codes as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['name']) ?></td>
          <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
            <a href="<?= htmlspecialchars($c['destination_url']) ?>" target="_blank" rel="noopener">
              <?= htmlspecialchars($c['destination_url']) ?>
            </a>
          </td>
          <td>
            <a href="<?= SITE_URL ?>/r/<?= htmlspecialchars($c['short_code']) ?>" target="_blank">
              /r/<?= htmlspecialchars($c['short_code']) ?>
            </a>
          </td>
          <td><?= (int)$c['scan_count'] ?></td>
          <td><?= htmlspecialchars(substr($c['created_at'], 0, 10)) ?></td>
          <td>
            <a href="edit.php?id=<?= (int)$c['id'] ?>">Szerkesztés</a> |
            <a href="analytics.php?id=<?= (int)$c['id'] ?>">Statisztika</a> |
            <a href="delete.php?id=<?= (int)$c['id'] ?>"
               onclick="return confirm('Biztosan törli?')"
               style="color:#e74c3c">Törlés</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
```

- [ ] **Step 2: Commit**

```powershell
git add "qr-admin/dashboard.php"
git commit -m "feat: add dashboard"
```

---

### Task 12: Create/Edit QR code

**Files:**
- Create: `qr-admin/edit.php`

- [ ] **Step 1: Create edit.php**

Create `qr-admin/edit.php`:
```php
<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/qr_helpers.php';
require_auth();

$db    = get_db();
$id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$qr    = null;

if ($id) {
    $stmt = $db->prepare('SELECT * FROM qr_codes WHERE id = ?');
    $stmt->execute([$id]);
    $qr = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $url  = trim($_POST['destination_url'] ?? '');

    if ($name === '' || $url === '') {
        $error = 'Minden mező kitöltése kötelező.';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $error = 'Érvénytelen URL formátum.';
    } elseif ($id && $qr) {
        $db->prepare('UPDATE qr_codes SET name = ?, destination_url = ? WHERE id = ?')
           ->execute([$name, $url, $id]);
        $stmt = $db->prepare('SELECT * FROM qr_codes WHERE id = ?');
        $stmt->execute([$id]);
        $qr = $stmt->fetch();
    } else {
        $code = generate_short_code($db);
        $db->prepare('INSERT INTO qr_codes (short_code, name, destination_url) VALUES (?, ?, ?)')
           ->execute([$code, $name, $url]);
        $id = (int)$db->lastInsertId();
        $stmt = $db->prepare('SELECT * FROM qr_codes WHERE id = ?');
        $stmt->execute([$id]);
        $qr = $stmt->fetch();
    }
}

$redirect_url = $qr ? SITE_URL . '/r/' . $qr['short_code'] : '';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>QR Admin – <?= $id ? 'Szerkesztés' : 'Új QR kód' ?></title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
<div class="layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">
    <h2><?= $id ? 'QR kód szerkesztése' : 'Új QR kód' ?></h2>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" style="max-width:480px">
      <div class="form-group">
        <label for="name">Név / Felirat</label>
        <input type="text" id="name" name="name"
               value="<?= htmlspecialchars($qr['name'] ?? '') ?>" required/>
      </div>
      <div class="form-group">
        <label for="destination_url">Cél URL</label>
        <input type="url" id="destination_url" name="destination_url"
               value="<?= htmlspecialchars($qr['destination_url'] ?? '') ?>" required/>
      </div>
      <button type="submit" class="btn btn-primary"><?= $id ? 'Mentés' : 'Létrehozás' ?></button>
      <a href="dashboard.php" class="btn btn-ghost" style="margin-left:.5rem">Vissza</a>
    </form>

    <?php if ($redirect_url): ?>
    <div class="qr-box" style="margin-top:1.5rem">
      <div id="qrcode"></div>
      <p class="qr-url"><?= htmlspecialchars($redirect_url) ?></p>
      <button onclick="downloadQR()" class="btn btn-ghost" style="margin-top:.75rem">
        QR letöltése (PNG)
      </button>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
      const qr = new QRCode(document.getElementById('qrcode'), {
        text: <?= json_encode($redirect_url) ?>,
        width: 200, height: 200,
        correctLevel: QRCode.CorrectLevel.H
      });
      function downloadQR() {
        const canvas = document.querySelector('#qrcode canvas');
        if (!canvas) return;
        const a = document.createElement('a');
        a.download = 'qr-<?= htmlspecialchars($qr['short_code']) ?>.png';
        a.href = canvas.toDataURL('image/png');
        a.click();
      }
    </script>
    <?php endif; ?>
  </main>
</div>
</body>
</html>
```

- [ ] **Step 2: Commit**

```powershell
git add "qr-admin/edit.php"
git commit -m "feat: add create/edit QR code page"
```

---

### Task 13: Delete QR code

**Files:**
- Create: `qr-admin/delete.php`

- [ ] **Step 1: Create delete.php**

Create `qr-admin/delete.php`:
```php
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
```

- [ ] **Step 2: Commit**

```powershell
git add "qr-admin/delete.php"
git commit -m "feat: add delete QR code"
```

---

### Task 14: Redirect handler

**Files:**
- Create: `r/index.php`

- [ ] **Step 1: Create r/index.php**

Create `r/index.php`:
```php
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
```

- [ ] **Step 2: Commit**

```powershell
git add "r/index.php"
git commit -m "feat: add redirect handler with scan logging"
```

---

### Task 15: Analytics page

**Files:**
- Create: `qr-admin/analytics.php`

- [ ] **Step 1: Create analytics.php**

Create `qr-admin/analytics.php`:
```php
<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_auth();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: dashboard.php'); exit; }

$db = get_db();

$qr_stmt = $db->prepare('SELECT * FROM qr_codes WHERE id = ?');
$qr_stmt->execute([$id]);
$qr = $qr_stmt->fetch();
if (!$qr) { header('Location: dashboard.php'); exit; }

$total_stmt = $db->prepare('SELECT COUNT(*) FROM scans WHERE qr_code_id = ?');
$total_stmt->execute([$id]);
$total = (int)$total_stmt->fetchColumn();

$daily_stmt = $db->prepare(
    'SELECT DATE(scanned_at) AS day, COUNT(*) AS cnt
     FROM scans WHERE qr_code_id = ?
       AND scanned_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY DATE(scanned_at) ORDER BY day DESC'
);
$daily_stmt->execute([$id]);
$daily = $daily_stmt->fetchAll();

$city_stmt = $db->prepare(
    'SELECT city, COUNT(*) AS cnt FROM scans WHERE qr_code_id = ?
     GROUP BY city ORDER BY cnt DESC LIMIT 20'
);
$city_stmt->execute([$id]);
$cities = $city_stmt->fetchAll();

$device_stmt = $db->prepare(
    'SELECT device, COUNT(*) AS cnt FROM scans WHERE qr_code_id = ?
     GROUP BY device ORDER BY cnt DESC'
);
$device_stmt->execute([$id]);
$devices = $device_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>QR Admin – Statisztika</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
<div class="layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">
    <h2>Statisztika: <?= htmlspecialchars($qr['name']) ?></h2>
    <p style="margin-bottom:1.5rem">
      Összesen: <strong><?= $total ?> beolvasás</strong> &nbsp;|&nbsp;
      <a href="<?= SITE_URL ?>/r/<?= htmlspecialchars($qr['short_code']) ?>" target="_blank">
        /r/<?= htmlspecialchars($qr['short_code']) ?>
      </a>
    </p>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-bottom:2rem">

      <div>
        <h3 style="margin-bottom:.75rem;font-size:1rem">Napi beolvasások (30 nap)</h3>
        <?php if (empty($daily)): ?>
          <p style="font-size:.85rem;color:#888">Nincs adat.</p>
        <?php else: ?>
        <table>
          <thead><tr><th>Dátum</th><th>db</th></tr></thead>
          <tbody>
            <?php foreach ($daily as $row): ?>
            <tr><td><?= htmlspecialchars($row['day']) ?></td><td><?= (int)$row['cnt'] ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

      <div>
        <h3 style="margin-bottom:.75rem;font-size:1rem">Városok</h3>
        <?php if (empty($cities)): ?>
          <p style="font-size:.85rem;color:#888">Nincs adat.</p>
        <?php else: ?>
        <table>
          <thead><tr><th>Város</th><th>db</th></tr></thead>
          <tbody>
            <?php foreach ($cities as $row): ?>
            <tr><td><?= htmlspecialchars($row['city']) ?></td><td><?= (int)$row['cnt'] ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

      <div>
        <h3 style="margin-bottom:.75rem;font-size:1rem">Eszközök</h3>
        <?php if (empty($devices)): ?>
          <p style="font-size:.85rem;color:#888">Nincs adat.</p>
        <?php else: ?>
        <table>
          <thead><tr><th>Eszköz</th><th>db</th></tr></thead>
          <tbody>
            <?php foreach ($devices as $row): ?>
            <tr><td><?= htmlspecialchars($row['device']) ?></td><td><?= (int)$row['cnt'] ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

    </div>
    <a href="dashboard.php" class="btn btn-ghost">← Vissza</a>
  </main>
</div>
</body>
</html>
```

- [ ] **Step 2: Commit**

```powershell
git add "qr-admin/analytics.php"
git commit -m "feat: add analytics page"
```

---

### Task 16: Change password

**Files:**
- Create: `qr-admin/change-password.php`

- [ ] **Step 1: Create change-password.php**

Create `qr-admin/change-password.php`:
```php
<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/crypto.php';
require_auth();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $db      = get_db();
    $user    = $db->query('SELECT password_hash FROM users LIMIT 1')->fetch();

    if (!password_verify($current, $user['password_hash'])) {
        $error = 'A jelenlegi jelszó helytelen.';
    } elseif (strlen($new) < 6) {
        $error = 'Az új jelszónak legalább 6 karakter hosszúnak kell lennie.';
    } elseif ($new !== $confirm) {
        $error = 'A két jelszó nem egyezik.';
    } else {
        $db->prepare('UPDATE users SET password_hash = ?, password_encrypted = ? WHERE id = 1')
           ->execute([password_hash($new, PASSWORD_BCRYPT), aes_encrypt($new)]);
        $success = 'A jelszó sikeresen megváltozott.';
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>QR Admin – Jelszó módosítás</title>
  <link rel="stylesheet" href="css/style.css"/>
</head>
<body>
<div class="layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="main">
    <h2>Jelszó módosítás</h2>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" style="max-width:380px">
      <div class="form-group">
        <label for="current_password">Jelenlegi jelszó</label>
        <input type="password" id="current_password" name="current_password" required/>
      </div>
      <div class="form-group">
        <label for="new_password">Új jelszó</label>
        <input type="password" id="new_password" name="new_password" required/>
      </div>
      <div class="form-group">
        <label for="confirm_password">Új jelszó megerősítése</label>
        <input type="password" id="confirm_password" name="confirm_password" required/>
      </div>
      <button type="submit" class="btn btn-primary">Jelszó módosítása</button>
    </form>
  </main>
</div>
</body>
</html>
```

- [ ] **Step 2: Commit**

```powershell
git add "qr-admin/change-password.php"
git commit -m "feat: add change password page"
```

---

### Task 17: .htaccess files

**Files:**
- Create: `r/.htaccess`
- Create: `qr-admin/.htaccess`
- Create: `qr-admin/includes/.htaccess`
- Create: `qr-admin/setup/.htaccess`

- [ ] **Step 1: Create r/.htaccess**

Create `r/.htaccess`:
```apache
Options -Indexes
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^([a-z0-9]+)$ index.php?code=$1 [L,QSA]
```

- [ ] **Step 2: Create qr-admin/.htaccess**

Create `qr-admin/.htaccess`:
```apache
Options -Indexes
```

- [ ] **Step 3: Create qr-admin/includes/.htaccess**

Create `qr-admin/includes/.htaccess`:
```apache
Order deny,allow
Deny from all
```

- [ ] **Step 4: Create qr-admin/setup/.htaccess**

Create `qr-admin/setup/.htaccess`:
```apache
Order deny,allow
Deny from all
```

- [ ] **Step 5: Commit**

```powershell
git add "r/.htaccess" "qr-admin/.htaccess" "qr-admin/includes/.htaccess" "qr-admin/setup/.htaccess"
git commit -m "feat: add .htaccess security rules"
```

---

### Task 18: Deploy + end-to-end verification

- [ ] **Step 1: Fill in config.php with real values**

Open `qr-admin/includes/config.php` and replace all `CHANGE_ME` placeholders:
- `DB_NAME`, `DB_USER`, `DB_PASS` — from cPanel → MySQL Databases
- `ENCRYPTION_KEY` — any random 32-character string (e.g. `MyS3cur3QrAdm1nK3y_32BytesLong!!`)
- `SMTP_USER` — email account created in cPanel (e.g. `admin@somfaiklimatechnika.hu`)
- `SMTP_PASS` — password set for that email account in cPanel

Do **not** commit config.php after filling in real credentials.

- [ ] **Step 2: Upload files to Tárhely.eu via cPanel File Manager**

Upload the following to `public_html/`:
- `index.html`, `css/`, `js/`, `images/` (main site — already there)
- `vendor/` (run `composer install --no-dev` first to exclude PHPUnit)
- `r/` (redirect handler)
- `qr-admin/` (entire admin tool)

- [ ] **Step 3: Create MySQL database in cPanel**

In cPanel → MySQL Databases:
1. Create database (note the name — prefixed with your cPanel username, e.g. `username_qradmin`)
2. Create a database user with a strong password
3. Add user to database with ALL PRIVILEGES
4. Update `config.php` with these values

- [ ] **Step 4: Run install script**

Visit: `https://somfaiklimatechnika.hu/qr-admin/setup/install.php`

Expected output: `Setup complete. Initial password: password1`

- [ ] **Step 5: End-to-end test**

1. Visit `https://somfaiklimatechnika.hu/qr-admin/` → login page shown
2. Wrong password → "Hibás jelszó." alert
3. Click "Elfelejtette jelszavát?" → confirmation shown, email arrives at varga.ferenc88@gmail.com
4. Login with `password1` → dashboard shown (empty)
5. Create QR code: name "Test", URL `https://google.com` → QR image shown
6. Download QR PNG → file saved
7. Visit the `/r/{code}` URL → instant redirect to google.com
8. Check dashboard → scan count = 1
9. Click Statisztika → city and device shown
10. Change password → log out → log in with new password → works

- [ ] **Step 6: Final commit**

```powershell
git add .
git commit -m "feat: complete dynamic QR code generator"
```
