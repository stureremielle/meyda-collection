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

// Month names in English
$months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sales Reports - MeyDa Admin</title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .reports-layout {
      max-width: 1400px;
      margin: 0 auto;
      padding: 40px 24px;
    }
  </style>
</head>
<body class="admin-body">
  <?php include __DIR__ . '/_header.php'; ?>

  <main class="reports-layout">
    <div class="admin-page-header">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
          <h2 style="font-family: 'Garamond', serif; font-size: 40px; margin-bottom: 8px;">Sales Reports</h2>
          <p style="color: var(--muted); font-size: 16px;">Monthly financial performance and sales volume.</p>
        </div>
        <a href="reset_data.php" class="admin-btn admin-btn-danger" style="opacity: 0.6;">Reset All Reports</a>
      </div>
    </div>

    <div class="admin-card">
      <?php if (empty($reports)): ?>
        <p style="color: var(--muted); padding: 40px 0; text-align: center;">No reports have been generated yet.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Period</th>
              <th>Total Transactions</th>
              <th>Revenue</th>
              <th>Items Sold</th>
              <th style="text-align: right;">Generated At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reports as $r): ?>
              <tr>
                <td style="font-weight: 600;"><?php echo $months[$r['periode_month']] . ' ' . $r['periode_year']; ?></td>
                <td><?php echo (int)$r['total_transaksi']; ?></td>
                <td style="color: var(--accent); font-weight: 600;">Rp <?php echo number_format($r['total_pendapatan'], 0, ',', '.'); ?></td>
                <td><?php echo (int)$r['total_item_terjual']; ?></td>
                <td style="text-align: right; color: var(--muted); font-size: 13px;"><?php echo date('M d, Y H:i', strtotime($r['generated_at'])); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__ . '/_footer.php'; ?>
