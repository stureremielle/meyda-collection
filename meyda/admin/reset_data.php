<?php
require_once __DIR__ . '/../auth.php';
requireLogin('staff');

$pdo = getPDO();

$message = '';
$messageType = '';

if ($_POST['confirm_reset'] ?? false) {
    try {
        // Start transaction to ensure data consistency
        $pdo->beginTransaction();
        
        // Reset reports table
        $pdo->exec("DELETE FROM laporan");
        
        // Reset transactions and related data
        $pdo->exec("DELETE FROM detail_transaksi");
        $pdo->exec("DELETE FROM transaksi");
        
        $pdo->commit();
        
        $message = "Data transaksi dan laporan telah direset.";
        $messageType = 'success';
    } catch (Exception $e) {
        $pdo->rollback();
        $message = "Gagal mereset data: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get current counts for display
$transCount = $pdo->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();
$reportCount = $pdo->query("SELECT COUNT(*) FROM laporan")->fetchColumn();
$detailCount = $pdo->query("SELECT COUNT(*) FROM detail_transaksi")->fetchColumn();
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Data - MeyDa Collection</title>
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
    
    .container { max-width: 800px; margin: 0 auto; padding: 20px; width: 100%; }
    
    .reset-warning {
      background: #4a2a2a;
      border: 1px solid #ff6d00;
      border-radius: 8px;
      padding: 20px;
      margin: 20px 0;
    }
    
    .stats-box {
      background: #252525;
      border-radius: 8px;
      padding: 15px;
      margin: 15px 0;
    }
    
    .btn-reset {
      background: #ff3d00;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      transition: background 0.3s;
    }
    
    .btn-reset:hover {
      background: #cc3000;
    }
    
    .btn-cancel {
      background: #555;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
      text-decoration: none;
      margin-left: 10px;
      transition: background 0.3s;
    }
    
    .btn-cancel:hover {
      background: #777;
    }
    
    .form-actions {
      margin: 20px 0;
      text-align: center;
    }
    
    .message {
      padding: 12px;
      border-radius: 6px;
      margin: 15px 0;
      text-align: center;
    }
    
    .message.success {
      background: #2a4a3a;
      color: #99ff99;
      border: 1px solid #3a6a4a;
    }
    
    .message.error {
      background: #4a2a2a;
      color: #ff9999;
      border: 1px solid #6a3a3a;
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>

  <main class="container">
    <h2>Reset Data Transaksi & Laporan</h2>
    
    <?php if ($message): ?>
      <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
    
    <div class="stats-box">
      <h3>Data saat ini:</h3>
      <p><strong>Transaksi:</strong> <?php echo $transCount; ?></p>
      <p><strong>Detail Transaksi:</strong> <?php echo $detailCount; ?></p>
      <p><strong>Laporan:</strong> <?php echo $reportCount; ?></p>
    </div>
    
    <div class="reset-warning">
      <h3>Peringatan Penting!</h3>
      <p>Anda akan melakukan reset terhadap semua data transaksi dan laporan.</p>
      <p>Tindakan ini akan:</p>
      <ul>
        <li>Menghapus semua transaksi yang pernah dibuat</li>
        <li>Menghapus semua detail transaksi</li>
        <li>Menghapus semua laporan penjualan</li>
      </ul>
      <p><strong>Tindakan ini tidak dapat dibatalkan.</strong> Pastikan Anda yakin sebelum melanjutkan.</p>
    </div>
    
    <form method="post" onsubmit="return confirm('Apakah Anda YAKIN ingin mereset semua data transaksi dan laporan? Tindakan ini tidak dapat dibatalkan!');">
      <div class="form-actions">
        <button type="submit" name="confirm_reset" value="1" class="btn-reset">Reset Sekarang</button>
        <a href="reports.php" class="btn-cancel">Batal</a>
      </div>
    </form>
  </main>

  <footer class="site-footer">
    <div class="container"><small>&copy; MeyDa Collection Admin</small></div>
  </footer>
</body>
</html>