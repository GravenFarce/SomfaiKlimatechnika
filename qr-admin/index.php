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
