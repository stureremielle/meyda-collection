<?php
require_once __DIR__ . '/../auth.php';
requireLogin('staff');

$pdo = getPDO();

$error = null;
$success = null;

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
        
        $success = "Transaction and report data has been successfully reset.";
    } catch (Exception $e) {
        $pdo->rollback();
        $error = "Failed to reset data: " . $e->getMessage();
    }
}

// Get current counts for display
$transCount = $pdo->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();
$reportCount = $pdo->query("SELECT COUNT(*) FROM laporan")->fetchColumn();
$detailCount = $pdo->query("SELECT COUNT(*) FROM detail_transaksi")->fetchColumn();
?>

<!doctype html>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Store Data - MeyDa Admin</title>
  <link rel="stylesheet" href="<?php echo asset('styles.css'); ?>">
  <style>
    .reset-layout {
      max-width: 800px;
      margin: 0 auto;
      padding: 60px 24px;
    }
    .warning-card {
      background: rgba(248, 113, 113, 0.05);
      border: 1px solid rgba(248, 113, 113, 0.2);
    }
  </style>
</head>
<body class="admin-body">
  <?php include __DIR__ . '/_header.php'; ?>

  <main class="reset-layout">
    <div class="admin-page-header">
      <h2 style="font-family: 'Garamond', serif; font-size: 40px; margin-bottom: 8px;">Reset Store Data</h2>
      <p style="color: var(--muted); font-size: 16px;">Clear transactions and reports to start fresh.</p>
    </div>
    
    <?php if ($success): ?>
      <div class="alert alert-success" style="margin-bottom: 32px;"><?php echo h($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom: 32px;"><?php echo h($error); ?></div>
    <?php endif; ?>
    
    <div class="admin-card">
      <h3 style="font-size: 20px; font-weight: 600; margin-bottom: 24px;">Current Data Statistics</h3>
      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 16px; text-align: center;">
          <div style="font-size: 32px; font-weight: 700; color: var(--accent);"><?php echo $transCount; ?></div>
          <div style="font-size: 13px; color: var(--muted); text-transform: uppercase; margin-top: 4px;">Orders</div>
        </div>
        <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 16px; text-align: center;">
          <div style="font-size: 32px; font-weight: 700; color: var(--accent);"><?php echo $detailCount; ?></div>
          <div style="font-size: 13px; color: var(--muted); text-transform: uppercase; margin-top: 4px;">Items</div>
        </div>
        <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 16px; text-align: center;">
          <div style="font-size: 32px; font-weight: 700; color: var(--accent);"><?php echo $reportCount; ?></div>
          <div style="font-size: 13px; color: var(--muted); text-transform: uppercase; margin-top: 4px;">Reports</div>
        </div>
      </div>
    </div>
    
    <div class="admin-card warning-card">
      <h3 style="font-size: 20px; font-weight: 600; color: #f87171; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
        <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        Critical Warning
      </h3>
      <p style="color: var(--muted); line-height: 1.6; margin-bottom: 24px;">
        You are about to permanently delete all store transaction records and performance reports. 
        <strong>This action cannot be undone.</strong>
      </p>
      
      <ul style="color: var(--muted); font-size: 14px; margin-bottom: 32px; padding-left: 20px;">
        <li style="margin-bottom: 8px;">Deletes all transaction history and customer order records.</li>
        <li style="margin-bottom: 8px;">Deletes all detailed item breakdown for كل orders.</li>
        <li>Wipes all monthly and annual performance reports.</li>
      </ul>
      
      <form method="post" id="resetForm">
        <div style="display: flex; gap: 16px; justify-content: center;">
          <button type="button" class="admin-btn admin-btn-danger" style="padding: 16px 32px; font-size: 16px;" onclick="confirmReset()">
            Reset All Data Now
          </button>
          <a href="reports.php" class="admin-btn admin-btn-secondary" style="padding: 16px 32px; font-size: 16px;">
            Cancel and Return
          </a>
        </div>
      </form>
    </div>
  </main>

  <script>
  function confirmReset() {
      adminConfirm({
          title: 'Wipe Store Data',
          message: 'ARE YOU ABSOLUTELY SURE? This will permanently delete all order history and performance reports. This action cannot be reversed!',
          confirmText: 'Reset Now',
          confirmClass: 'admin-btn-danger'
      }, () => {
          const form = document.getElementById('resetForm');
          const hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = 'confirm_reset';
          hiddenInput.value = '1';
          form.appendChild(hiddenInput);
          form.submit();
      });
  }
  </script>

  <?php include __DIR__ . '/_footer.php'; ?>
</body>
</html>