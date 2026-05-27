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
