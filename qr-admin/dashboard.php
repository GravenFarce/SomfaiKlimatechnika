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
