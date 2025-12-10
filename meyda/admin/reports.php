<?php
require_once __DIR__ . '/../auth.php';
requireLogin('staff');

$pdo = getPDO();

// Fetch laporan data (sales reports)
$stmt = $pdo->query("
    SELECT id_laporan, periode_year, periode_month, total_transaksi, total_pendapatan, total_item_terjual, generated_at
    FROM laporan
    ORDER BY periode_year DESC, periode_month DESC
    LIMIT 24
");
$reports = $stmt->fetchAll();

// Month names in Indonesian
$months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Laporan Penjualan - MeyDa Collection</title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    @font-face {
      font-family: 'Futura';
      src: url('../fonts/futura/Futura Book font.ttf') format('truetype');
      font-weight: 400;
    }
    @font-face {
      font-family: 'Futura';
      src: url('../fonts/futura/futura medium bt.ttf') format('truetype');
      font-weight: 500;
    }
    @font-face {
      font-family: 'Futura';
      src: url('../fonts/futura/Futura Bold font.ttf') format('truetype');
      font-weight: 700;
    }
    * { font-family: 'Futura', system-ui, -apple-system, "Segoe UI", Roboto, 'Google Sans', Arial; }
    html, body { height: 100%; }
    body { display: flex; flex-direction: column; }
    main.container { flex: 1; max-width: 1200px; margin: 0 auto; padding: 12px; width: 100%; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #252525; }
    table th, table td { padding: 16px; border-bottom: 1px solid #404040; text-align: left; color: #ffffff; }
    table th { background: #1a1a1a; font-weight: 600; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>

  <main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h2>Laporan Penjualan</h2>
      <a href="reset_data.php" class="btn-reset">Reset Data</a>
    </div>

    <?php if (empty($reports)): ?>
      <p>Tidak ada laporan.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Periode</th>
            <th>Total Transaksi</th>
            <th>Pendapatan</th>
            <th>Total Item Terjual</th>
            <th>Generated</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reports as $r): ?>
            <tr>
              <td><?php echo $months[$r['periode_month']] . ' ' . $r['periode_year']; ?></td>
              <td><?php echo (int)$r['total_transaksi']; ?></td>
              <td>Rp <?php echo number_format($r['total_pendapatan'], 0, ',', '.'); ?></td>
              <td><?php echo (int)$r['total_item_terjual']; ?></td>
              <td><?php echo htmlspecialchars($r['generated_at']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </main>

  <footer class="site-footer">
    <div class="container"><small>&copy; MeyDa Collection Admin</small></div>
  </footer>
</body>
</html>
