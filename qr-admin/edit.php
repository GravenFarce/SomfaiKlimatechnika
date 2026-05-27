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
