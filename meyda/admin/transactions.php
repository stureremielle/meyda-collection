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
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Transactions - MeyDa Admin</title>
  <link rel="stylesheet" href="<?php echo asset('styles.css'); ?>">
  <style>
    .transactions-layout {
      max-width: 1400px;
      margin: 0 auto;
      padding: 40px 24px;
    }
  </style>
</head>
<body class="admin-body">
  <?php include __DIR__ . '/_header.php'; ?>

  <main class="transactions-layout">
    <div class="admin-page-header">
      <h2 style="font-family: 'Garamond', serif; font-size: 40px; margin-bottom: 8px;">Transactions</h2>
      <p style="color: var(--muted); font-size: 16px;">View and manage store orders.</p>
    </div>

    <?php if ($viewId > 0 && !empty($transDetail)): ?>
      <div class="admin-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px;">
          <h3 style="font-size: 24px; font-weight: 600;">Order Details #<?php echo $viewId; ?></h3>
          <a href="transactions.php" class="admin-btn admin-btn-secondary">Back to List</a>
        </div>
        
        <table class="admin-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Qty</th>
              <th>Unit Price</th>
              <th style="text-align: right;">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php $total = 0; foreach ($transDetail as $d): $total += $d['subtotal']; ?>
              <tr>
                <td style="font-weight: 600;"><?php echo h($d['nama_produk']); ?></td>
                <td><?php echo (int)$d['qty']; ?></td>
                <td>Rp <?php echo number_format($d['harga_satuan'], 0, ',', '.'); ?></td>
                <td style="text-align: right; color: var(--accent); font-weight: 600;">Rp <?php echo number_format($d['subtotal'], 0, ',', '.'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background: rgba(255,255,255,0.02);">
              <td colspan="3" style="padding: 24px 16px; font-size: 18px; font-weight: 700;">Grand Total</td>
              <td style="padding: 24px 16px; text-align: right; font-size: 24px; font-weight: 700; color: var(--accent);">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      <div class="admin-card">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Date</th>
              <th>Customer</th>
              <th>Total</th>
              <th>Status</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($transactions as $t): ?>
              <tr>
                <td style="color: var(--muted); font-family: monospace;">#<?php echo (int)$t['id_transaksi']; ?></td>
                <td><?php echo date('M d, Y H:i', strtotime($t['tanggal'])); ?></td>
                <td style="font-weight: 600;"><?php echo h($t['pelanggan_nama']); ?></td>
                <td style="font-weight: 600; color: var(--accent);">Rp <?php echo number_format($t['total'], 0, ',', '.'); ?></td>
                <td>
                  <span class="admin-status admin-status-<?php echo h($t['status']); ?>">
                    <?php echo h($t['status']); ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <a href="transactions.php?view=<?php echo $t['id_transaksi']; ?>" class="admin-btn admin-btn-secondary" style="padding: 6px 12px; font-size: 12px;">View Details</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/_footer.php'; ?>
