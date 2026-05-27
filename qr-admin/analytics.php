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
