<?php
require_once __DIR__ . '/../auth.php';
requireLogin('staff');

$pdo = getPDO();

// Fetch summary stats
$stats = [];
$stmtTrans = $pdo->query("SELECT COUNT(*) as cnt FROM transaksi WHERE MONTH(tanggal) = MONTH(NOW()) AND YEAR(tanggal) = YEAR(NOW())");
$stats['monthly_trans'] = $stmtTrans->fetch()['cnt'];

$stmtRev = $pdo->query("SELECT COALESCE(SUM(total),0) as rev FROM transaksi WHERE MONTH(tanggal) = MONTH(NOW()) AND YEAR(tanggal) = YEAR(NOW()) AND status='paid'");
$stats['monthly_rev'] = $stmtRev->fetch()['rev'];

$stmtStok = $pdo->query("SELECT COUNT(*) as cnt FROM produk WHERE stok <= 5");
$stats['low_stock'] = $stmtStok->fetch()['cnt'];

$stmtProducts = $pdo->query("SELECT COUNT(*) as cnt FROM produk");
$stats['total_products'] = $stmtProducts->fetch()['cnt'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard - MeyDa Collection</title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .admin-header { background: #f8fafc; border-bottom: 1px solid #eef2f6; padding: 15px 0; margin-bottom: 20px; }
    .admin-nav { display: flex; gap: 15px; margin-top: 10px; }
    .admin-nav a { color: #1f6feb; text-decoration: none; padding: 8px 12px; border-radius: 4px; }
    .admin-nav a:hover { background: #e9eef6; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
    .stat-card { background: #f8fafc; border: 1px solid #eef2f6; padding: 15px; border-radius: 6px; }
    .stat-card h3 { margin: 0 0 10px 0; font-size: 14px; color: #6b7280; }
    .stat-card .value { font-size: 28px; font-weight: 600; color: #1f6feb; }
    .section { margin-bottom: 30px; }
    .section h2 { margin-top: 0; }
    .btn { display: inline-block; padding: 8px 16px; background: #1f6feb; color: white; text-decoration: none; border-radius: 4px; }
    .btn:hover { opacity: 0.95; }
    .logout-btn { background: #8b1e1e; float: right; }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container">
      <h1 class="brand">MeyDa Collection - Admin</h1>
      <nav class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Produk</a>
        <a href="reports.php">Laporan</a>
        <a href="transactions.php">Transaksi</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <div style="padding: 15px 0; border-bottom: 1px solid #eef2f6; margin-bottom: 20px;">
      <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['staff_name']); ?></strong> (<?php echo htmlspecialchars($_SESSION['staff_role']); ?>)</p>
      <a href="../auth.php?action=logout" class="btn logout-btn" onclick="return confirm('Logout?')">Logout</a>
    </div>

    <h2>Dashboard</h2>

    <div class="stats-grid">
      <div class="stat-card">
        <h3>Transaksi Bulan Ini</h3>
        <div class="value"><?php echo (int)$stats['monthly_trans']; ?></div>
      </div>
      <div class="stat-card">
        <h3>Pendapatan Bulan Ini</h3>
        <div class="value">Rp <?php echo number_format($stats['monthly_rev'], 0, ',', '.'); ?></div>
      </div>
      <div class="stat-card">
        <h3>Total Produk</h3>
        <div class="value"><?php echo (int)$stats['total_products']; ?></div>
      </div>
      <div class="stat-card">
        <h3>Stok Rendah (&le;5)</h3>
        <div class="value" style="color: #c84f2c;"><?php echo (int)$stats['low_stock']; ?></div>
      </div>
    </div>

    <div class="section">
      <h3>Manajemen</h3>
      <p>
        <a href="products.php" class="btn">Kelola Produk</a>
        <a href="reports.php" class="btn">Lihat Laporan</a>
        <a href="transactions.php" class="btn">Lihat Transaksi</a>
      </p>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container"><small>&copy; MeyDa Collection Admin</small></div>
  </footer>
</body>
</html>
