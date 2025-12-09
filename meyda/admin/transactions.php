<?php
require_once __DIR__ . '/../auth.php';
requireLogin('staff');

$pdo = getPDO();

// Get transaction ID if viewing details
$viewId = (int)($_GET['view'] ?? 0);

// Fetch all transactions with customer info
$stmt = $pdo->query("
    SELECT t.id_transaksi, t.tanggal, COALESCE(p.nama, 'Guest') as pelanggan_nama, t.total, t.status
    FROM transaksi t
    LEFT JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
    ORDER BY t.tanggal DESC
    LIMIT 50
");
$transactions = $stmt->fetchAll();

// Fetch detail if viewing
$transDetail = null;
if ($viewId > 0) {
    $stmtDetail = $pdo->prepare("
        SELECT d.id_detail, d.qty, d.harga_satuan, d.subtotal, pr.nama_produk
        FROM detail_transaksi d
        JOIN produk pr ON d.id_produk = pr.id_produk
        WHERE d.id_transaksi = :id
        ORDER BY d.id_detail
    ");
    $stmtDetail->execute([':id' => $viewId]);
    $transDetail = $stmtDetail->fetchAll();
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Transaksi - MeyDa Collection</title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table th, table td { padding: 10px; border-bottom: 1px solid #eef2f6; text-align: left; }
    table th { background: #f8fafc; font-weight: 600; }
    .status-paid { color: #11644a; background: #f4fffb; padding: 4px 8px; border-radius: 3px; }
    .status-pending { color: #8b5e00; background: #fffbec; padding: 4px 8px; border-radius: 3px; }
    .status-cancelled { color: #8b1e1e; background: #fff4f4; padding: 4px 8px; border-radius: 3px; }
    .action-link { color: #1f6feb; text-decoration: none; }
    .detail-section { margin-top: 30px; padding: 20px; border: 1px solid #eef2f6; border-radius: 6px; background: #f8fafc; }
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
    <h2>Transaksi</h2>

    <?php if ($viewId > 0 && !empty($transDetail)): ?>
      <div class="detail-section">
        <h3>Detail Transaksi #<?php echo $viewId; ?></h3>
        <table>
          <thead>
            <tr>
              <th>Produk</th>
              <th>Qty</th>
              <th>Harga Satuan</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php $total = 0; foreach ($transDetail as $d): $total += $d['subtotal']; ?>
              <tr>
                <td><?php echo htmlspecialchars($d['nama_produk']); ?></td>
                <td><?php echo (int)$d['qty']; ?></td>
                <td>Rp <?php echo number_format($d['harga_satuan'], 0, ',', '.'); ?></td>
                <td>Rp <?php echo number_format($d['subtotal'], 0, ',', '.'); ?></td>
              </tr>
            <?php endforeach; ?>
            <tr style="background: #f8fafc; font-weight: 600;">
              <td colspan="3">Total</td>
              <td>Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
            </tr>
          </tbody>
        </table>
        <p><a href="transactions.php" class="action-link">Kembali ke Daftar</a></p>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Tanggal</th>
            <th>Pelanggan</th>
            <th>Total</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $t): ?>
            <tr>
              <td><?php echo (int)$t['id_transaksi']; ?></td>
              <td><?php echo htmlspecialchars($t['tanggal']); ?></td>
              <td><?php echo htmlspecialchars($t['pelanggan_nama']); ?></td>
              <td>Rp <?php echo number_format($t['total'], 0, ',', '.'); ?></td>
              <td>
                <span class="status-<?php echo htmlspecialchars($t['status']); ?>">
                  <?php echo ucfirst(htmlspecialchars($t['status'])); ?>
                </span>
              </td>
              <td>
                <a href="transactions.php?view=<?php echo $t['id_transaksi']; ?>" class="action-link">Lihat Detail</a>
              </td>
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
