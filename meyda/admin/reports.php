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
    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #252525; }
    table th, table td { padding: 10px; border-bottom: 1px solid #404040; text-align: left; color: #ffffff; }
    table th { background: #1a1a1a; font-weight: 600; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>

  <main class="container">
    <h2>Laporan Penjualan</h2>

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
