<?php
require_once __DIR__ . '/auth.php';
requireLogin('customer');

$pdo = getPDO();
$customerId = $_SESSION['customer_id'];

// Ensure payment method table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS metode_pembayaran (
        id_metode INT AUTO_INCREMENT PRIMARY KEY,
        id_pelanggan INT NOT NULL,
        tipe VARCHAR(50) DEFAULT 'credit_card',
        nomor_kartu VARCHAR(20) NOT NULL,
        nama_kartu VARCHAR(100) NOT NULL,
        masa_berlaku VARCHAR(5) NOT NULL,
        cvv VARCHAR(4) NOT NULL,
        FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan) ON DELETE CASCADE
    )
");

// Handle form submissions for payment methods
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_payment') {
        $stmt = $pdo->prepare("INSERT INTO metode_pembayaran (id_pelanggan, nomor_kartu, nama_kartu, masa_berlaku, cvv) VALUES (:id, :num, :name, :exp, :cvv)");
        $stmt->execute([
            ':id' => $customerId,
            ':num' => $_POST['nomor_kartu'],
            ':name' => $_POST['nama_kartu'],
            ':exp' => $_POST['masa_berlaku'],
            ':cvv' => $_POST['cvv']
        ]);
        header("Location: account.php"); exit;
    } elseif ($_POST['action'] === 'delete_payment') {
        $stmt = $pdo->prepare("DELETE FROM metode_pembayaran WHERE id_metode = :id AND id_pelanggan = :cid");
        $stmt->execute([':id' => $_POST['id_metode'], ':cid' => $customerId]);
        header("Location: account.php"); exit;
    }
}

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

// Fetch payment methods
$stmtPay = $pdo->prepare("SELECT * FROM metode_pembayaran WHERE id_pelanggan = :id");
$stmtPay->execute([':id' => $customerId]);
$paymentMethods = $stmtPay->fetchAll();

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
<body class="auth-page">
  <main class="auth-center" style="max-width: 1000px; margin: 40px auto; padding: 20px; position: relative;">
    <a href="index.php" class="back-button">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
      Back to Home
    </a>
    <h2>Akun Saya</h2>

    <div class="account-content">
      <div class="profile-card">
        <h3>Informasi Profil</h3>
        <p><strong>Nama:</strong> <?php echo htmlspecialchars($_SESSION['customer_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['customer_email']); ?></p>
        <p style="margin-top: 10px;"><a href="auth.php?action=logout" class="action-link" style="color: #f87171;">Logout</a></p>
      </div>

      <div class="profile-card">
        <h3>Metode Pembayaran (Kartu Kredit)</h3>
        <?php if (empty($paymentMethods)): ?>
          <p>Anda belum menyimpan metode pembayaran.</p>
        <?php else: ?>
          <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-top: 16px;">
            <?php foreach ($paymentMethods as $pay): ?>
              <div style="background: rgba(255,255,255,0.05); padding: 16px; border-radius: 12px; border: 1px solid var(--md-sys-color-outline);">
                <p style="font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($pay['nama_kartu']); ?></p>
                <p style="font-family: monospace; letter-spacing: 2px; margin: 8px 0; color: var(--accent);"><?php 
                  $num = htmlspecialchars($pay['nomor_kartu']);
                  echo "**** **** **** " . substr($num, -4); 
                ?></p>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
                  <span style="font-size: 12px; color: var(--muted);">Exp: <?php echo htmlspecialchars($pay['masa_berlaku']); ?></span>
                  <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="delete_payment">
                    <input type="hidden" name="id_metode" value="<?php echo $pay['id_metode']; ?>">
                    <button type="submit" style="background: none; border: none; color: #f87171; font-size: 12px; padding: 0; width: auto; cursor: pointer;">Hapus</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--md-sys-color-outline);">
          <h4 style="margin-bottom: 16px;">Tambah Kartu Baru</h4>
          <form method="post" style="display: grid; gap: 12px;">
            <input type="hidden" name="action" value="add_payment">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
              <input type="text" name="nama_kartu" required placeholder="Nama di Kartu" style="width: 100%;">
              <input type="text" name="nomor_kartu" required placeholder="Nomor Kartu (16 digit)" maxlength="16" style="width: 100%;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
              <input type="text" name="masa_berlaku" required placeholder="MM/YY" maxlength="5" style="width: 100%;">
              <input type="password" name="cvv" required placeholder="CVV" maxlength="4" style="width: 100%;">
            </div>
            <button type="submit" style="margin-top: 8px;">Simpan Kartu</button>
          </form>
        </div>
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
</body>
</html>
