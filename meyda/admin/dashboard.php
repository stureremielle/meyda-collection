<?php
require_once __DIR__ . '/../auth.php';
requireLogin('staff');

$pdo = getPDO();

// Summary Stats
$stats = [];
$stmtTrans = $pdo->query("SELECT COUNT(*) as cnt FROM transaksi WHERE MONTH(tanggal) = MONTH(NOW()) AND YEAR(tanggal) = YEAR(NOW())");
$stats['monthly_trans'] = $stmtTrans->fetch()['cnt'];

$stmtRev = $pdo->query("SELECT COALESCE(SUM(total),0) as rev FROM transaksi WHERE MONTH(tanggal) = MONTH(NOW()) AND YEAR(tanggal) = YEAR(NOW()) AND status='paid'");
$stats['monthly_rev'] = $stmtRev->fetch()['rev'];

$stmtStok = $pdo->query("SELECT COUNT(*) as cnt FROM produk WHERE stok <= 5");
$stats['low_stock'] = $stmtStok->fetch()['cnt'];

// Avg Order Value
$stmtAvg = $pdo->query("SELECT COALESCE(AVG(total), 0) as avg_val FROM transaksi WHERE status='paid'");
$stats['avg_order'] = $stmtAvg->fetch()['avg_val'];

// Chart Data: Last 30 Days Sales
$stmtChart = $pdo->query("
    SELECT DATE(tanggal) as date, SUM(total) as daily_total
    FROM transaksi
    WHERE status = 'paid' AND tanggal >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(tanggal)
    ORDER BY DATE(tanggal) ASC
");
$chartDataRaw = $stmtChart->fetchAll();
$chartLabels = [];
$chartValues = [];
foreach ($chartDataRaw as $row) {
    $chartLabels[] = date('d M', strtotime($row['date']));
    $chartValues[] = (float)$row['daily_total'];
}

// Recent Transactions
$stmtRecent = $pdo->query("
    SELECT t.id_transaksi, t.tanggal, COALESCE(p.nama, 'Guest') as customer, t.total, t.status
    FROM transaksi t
    LEFT JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
    ORDER BY t.tanggal DESC
    LIMIT 5
");
$recentTransactions = $stmtRecent->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard - MeyDa Admin</title>
  <link rel="stylesheet" href="<?php echo asset('styles.css'); ?>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .dashboard-layout {
      max-width: 1400px;
      margin: 0 auto;
      padding: 40px 24px;
    }
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 24px;
      margin-bottom: 40px;
    }
    .analytics-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 32px;
      margin-bottom: 40px;
    }
    @media (max-width: 1100px) {
      .analytics-grid { grid-template-columns: 1fr; }
    }
    .chart-container {
      height: 350px;
      margin-top: 24px;
    }
  </style>
</head>
<body class="admin-body">
  <?php include __DIR__ . '/_header.php'; ?>

  <main class="dashboard-layout">
    <div class="admin-page-header">
      <h2 style="font-family: 'Garamond', serif; font-size: 40px; margin-bottom: 8px;">Dashboard Overview</h2>
      <p style="color: var(--muted); font-size: 16px;">Real-time performance and store analytics.</p>
    </div>

    <div class="stats-row">
      <div class="admin-stats-card">
        <span class="admin-stats-label">Monthly Revenue</span>
        <span class="admin-stats-value">Rp <?php echo number_format($stats['monthly_rev'], 0, ',', '.'); ?></span>
      </div>
      <div class="admin-stats-card">
        <span class="admin-stats-label">Monthly Orders</span>
        <span class="admin-stats-value"><?php echo $stats['monthly_trans']; ?></span>
      </div>
      <div class="admin-stats-card">
        <span class="admin-stats-label">Avg. Order Value</span>
        <span class="admin-stats-value">Rp <?php echo number_format($stats['avg_order'], 0, ',', '.'); ?></span>
      </div>
      <div class="admin-stats-card">
        <span class="admin-stats-label">Low Stock Alerts</span>
        <span class="admin-stats-value" style="color: #f87171;"><?php echo $stats['low_stock']; ?></span>
      </div>
    </div>

    <div class="analytics-grid">
      <div class="admin-card">
        <h3 style="font-size: 20px; font-weight: 600;">Sales Trend (30 Days)</h3>
        <div class="chart-container">
          <canvas id="salesChart"></canvas>
        </div>
      </div>

      <div class="admin-card">
        <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 24px;">Recent Transactions</h3>
        <div class="recent-list">
          <?php foreach ($recentTransactions as $t): ?>
            <div style="padding: 16px 0; border-bottom: 1px solid var(--md-sys-color-outline); display: flex; justify-content: space-between; align-items: center;">
              <div>
                <div style="font-weight: 600; font-size: 14px;">#<?php echo $t['id_transaksi']; ?> - <?php echo h($t['customer']); ?></div>
                <div style="color: var(--muted); font-size: 12px;"><?php echo date('M d, H:i', strtotime($t['tanggal'])); ?></div>
              </div>
              <div style="text-align: right;">
                <div style="font-weight: 600; font-size: 14px;">Rp <?php echo number_format($t['total'], 0, ',', '.'); ?></div>
                <span class="admin-status admin-status-<?php echo h($t['status']); ?>" style="font-size: 10px; padding: 2px 8px;"><?php echo $t['status']; ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <a href="transactions.php" class="admin-btn admin-btn-secondary" style="width: 100%; justify-content: center; margin-top: 24px;">View All Transactions</a>
      </div>
    </div>

    <div style="text-align: center; margin-top: 40px;">
      <a href="reset_data.php" class="admin-btn admin-btn-danger" style="opacity: 0.5;">Reset Store Data</a>
    </div>
  </main>

  <script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [{
          label: 'Revenue',
          data: <?php echo json_encode($chartValues); ?>,
          borderColor: '#ff6d00',
          backgroundColor: 'rgba(255, 109, 0, 0.1)',
          borderWidth: 3,
          tension: 0.4,
          fill: true,
          pointBackgroundColor: '#ff6d00',
          pointBorderColor: '#1a1a1a',
          pointBorderWidth: 2,
          pointRadius: 4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(255, 255, 255, 0.05)' },
            ticks: { 
              color: '#a0a0a0',
              callback: (value) => 'Rp ' + value.toLocaleString()
            }
          },
          x: {
            grid: { display: false },
            ticks: { color: '#a0a0a0' }
          }
        }
      }
    });
  </script>
  <?php include __DIR__ . '/_footer.php'; ?>
