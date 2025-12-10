<?php
require_once __DIR__ . '/auth.php';
requireLogin('customer');

$pdo = getPDO();
$customerId = $_SESSION['customer_id'];

// Fetch customer transactions
$stmt = $pdo->prepare("
    SELECT t.id_transaksi, t.tanggal, t.total, t.status, COUNT(d.id_detail) as item_count
    FROM transaksi t
    LEFT JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
    WHERE t.id_pelanggan = :id
    GROUP BY t.id_transaksi
    ORDER BY t.tanggal DESC
");
$stmt->execute([':id' => $customerId]);
$transactions = $stmt->fetchAll();

// Check if viewing detail
$viewId = (int)($_GET['view'] ?? 0);
$transDetail = null;
if ($viewId > 0) {
    $stmtDetail = $pdo->prepare("
        SELECT d.id_detail, d.qty, d.harga_satuan, d.subtotal, pr.nama_produk
        FROM detail_transaksi d
        JOIN produk pr ON d.id_produk = pr.id_produk
        WHERE d.id_transaksi = :id
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
  <title>Akun Saya - MeyDa Collection</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    /* Override global container for account page to prevent conflicts */
    main.account-container { 
      max-width: 1200px; 
      margin: 0 auto; 
      padding: 0 24px; 
    }
    
    @media (max-width: 768px) {
      main.account-container { 
        padding: 0 16px; 
      }
    }
    
    /* Additional account-specific styling */
    .account-content {
      background: var(--card);
      border-radius: 12px;
      padding: 24px;
      margin-top: 20px;
      border: 1px solid var(--md-sys-color-outline);
    }
    
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table th, table td { padding: 10px; border-bottom: 1px solid var(--md-sys-color-outline); text-align: left; }
    table th { background: var(--md-sys-color-surface-variant); font-weight: 600; }
    .status-paid { color: #4ade80; background: #1a3620; padding: 4px 8px; border-radius: 3px; }
    .status-pending { color: #fbbf24; background: #362c1a; padding: 4px 8px; border-radius: 3px; }
    .status-cancelled { color: #f87171; background: #361a1a; padding: 4px 8px; border-radius: 3px; }
    .action-link { color: var(--accent); text-decoration: none; font-weight: 500; }
    .detail-section { margin-top: 30px; padding: 20px; border: 1px solid var(--md-sys-color-outline); border-radius: 8px; background: var(--md-sys-color-surface-variant); }
    .profile-card { padding: 20px; border: 1px solid var(--md-sys-color-outline); border-radius: 8px; background: var(--md-sys-color-surface-variant); margin-bottom: 20px; }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="container">
      <h1 class="brand">MeyDa Collection</h1>
      <nav class="nav">
        <a href="index.php">Home</a>
        <a href="index.php?action=cart">Cart</a>
        <a href="account.php">Hi, <?php echo htmlspecialchars($_SESSION['customer_name']); ?></a>
        <a href="auth.php?action=logout">Logout</a>
      </nav>
    </div>
  </header>

  <main class="account-container">
    <h2>Akun Saya</h2>

    <div class="account-content">
      <div class="profile-card">
        <h3>Informasi Profil</h3>
        <p><strong>Nama:</strong> <?php echo htmlspecialchars($_SESSION['customer_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['customer_email']); ?></p>
      </div>

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
        <p><a href="account.php" class="action-link">Kembali ke Riwayat Transaksi</a></p>
      </div>
    <?php else: ?>
      <h3>Riwayat Transaksi</h3>
      <?php if (empty($transactions)): ?>
        <p>Anda belum memiliki transaksi.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID Transaksi</th>
              <th>Tanggal</th>
              <th>Total</th>
              <th>Status</th>
              <th>Item</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($transactions as $t): ?>
              <tr>
                <td><?php echo (int)$t['id_transaksi']; ?></td>
                <td><?php echo htmlspecialchars($t['tanggal']); ?></td>
                <td>Rp <?php echo number_format($t['total'], 0, ',', '.'); ?></td>
                <td>
                  <span class="status-<?php echo htmlspecialchars($t['status']); ?>">
                    <?php echo ucfirst(htmlspecialchars($t['status'])); ?>
                  </span>
                </td>
                <td><?php echo (int)$t['item_count']; ?></td>
                <td>
                  <a href="account.php?view=<?php echo $t['id_transaksi']; ?>" class="action-link">Lihat Detail</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    <?php endif; ?>
    </div> <!-- Close account-content div -->
  </main>

  <footer class="site-footer">
    <div class="container"><small>&copy; MeyDa Collection</small></div>
  </footer>
</body>
</html>
