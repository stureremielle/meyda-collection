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

// Calculate Statistics
$totalOrders = count($transactions);
$totalSpent = 0;
foreach ($transactions as $t) {
    if ($t['status'] === 'paid') {
        $totalSpent += $t['total'];
    }
}

// Check if viewing detail
$viewId = (int)($_GET['view'] ?? 0);
$transDetail = null;
if ($viewId > 0) {
    $stmtDetail = $pdo->prepare("
        SELECT d.id_detail, d.qty, d.harga_satuan, d.subtotal, pr.nama_produk, pr.gambar
        FROM detail_transaksi d
        JOIN produk pr ON d.id_produk = pr.id_produk
        WHERE d.id_transaksi = :id
    ");
    $stmtDetail->execute([':id' => $viewId]);
    $transDetail = $stmtDetail->fetchAll();
}

$tab = $_GET['tab'] ?? 'dashboard';
$success = null;
$error = null;

// Handle address update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_address') {
    $newAddress = trim($_POST['address'] ?? '');
    if (empty($newAddress)) {
        $error = "Address cannot be empty.";
    } else {
        try {
            $upd = $pdo->prepare("UPDATE pelanggan SET alamat = :alamat WHERE id_pelanggan = :id");
            $upd->execute([':alamat' => $newAddress, ':id' => $customerId]);
            $_SESSION['customer_address'] = $newAddress;
            $success = "Address updated successfully.";
            $tab = 'settings'; // Stay on settings tab after update
        } catch (Exception $e) {
            $error = "Failed to update address: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Account - MeyDa Collection</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    :root {
      --sidebar-width: 300px;
    }

    body.account-page {
      background: var(--md-sys-color-surface);
      min-height: 100vh;
      display: block;
      padding: 0;
      position: relative;
    }

    .account-layout {
      max-width: 1400px;
      margin: 0 auto;
      padding: 100px 24px 40px;
      display: flex;
      gap: 40px;
      position: relative;
      align-items: flex-start;
    }

    @media (max-width: 992px) {
      .account-layout {
        flex-direction: column;
        padding-top: 80px;
      }
    }

    /* Back Button - matching cart/login */
    .back-button {
      position: absolute;
      top: 40px;
      left: 40px;
      color: var(--muted);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.3s ease;
      z-index: 1001;
    }

    .back-button:hover {
      color: var(--md-sys-color-on-surface);
      transform: translateX(-5px);
    }

    .back-button svg {
      width: 20px;
      height: 20px;
    }

    /* Sidebar Styles */
    .account-sidebar {
      width: var(--sidebar-width);
      flex-shrink: 0;
    }

    @media (max-width: 992px) {
      .account-sidebar {
        width: 100%;
      }
    }

    .profile-summary-card {
      margin-top: 170px;
      background: var(--card);
      border-radius: 24px;
      padding: 40px 24px;
      border: 1px solid var(--md-sys-color-outline);
      text-align: center;
      box-shadow: var(--elevation-1);
    }

    .avatar-placeholder {
      width: 100px;
      height: 100px;
      background: var(--accent);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 40px;
      font-weight: 700;
      margin: 0 auto 24px;
    }

    .profile-name {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .profile-email {
      color: var(--muted);
      font-size: 14px;
      margin-bottom: 32px;
    }

    .profile-nav {
      list-style: none;
      padding: 0;
      margin: 0;
      text-align: left;
    }

    .profile-nav li {
      margin-bottom: 8px;
    }

    .profile-nav a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      color: var(--md-sys-color-on-surface);
      text-decoration: none;
      border-radius: 12px;
      transition: background 0.2s;
    }

    .profile-nav a:hover {
      background: rgba(255, 255, 255, 0.05);
    }

    .profile-nav a.active {
      background: rgba(255, 109, 0, 0.1);
      color: var(--accent);
      font-weight: 600;
    }

    /* Main Content Styles */
    .account-main {
      flex: 1;
      min-width: 0; /* Important for flex child with overflow */
    }

    .dashboard-header {
      margin-bottom: 40px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 24px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: var(--card);
      border: 1px solid var(--md-sys-color-outline);
      border-radius: 20px;
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .stat-label {
      font-size: 14px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .stat-value {
      font-size: 32px;
      font-weight: 700;
      color: var(--accent);
    }

    /* Table Styles */
    .content-card {
      background: var(--card);
      border: 1px solid var(--md-sys-color-outline);
      border-radius: 24px;
      padding: 32px;
      box-shadow: var(--elevation-1);
    }

    .section-title {
      font-family: "Garamond", serif;
      font-size: 32px;
      margin-bottom: 24px;
    }

    .table-container {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th {
      text-align: left;
      padding: 16px;
      border-bottom: 1px solid var(--md-sys-color-outline);
      color: var(--muted);
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    td {
      padding: 20px 16px;
      border-bottom: 1px solid var(--md-sys-color-outline);
      font-size: 15px;
    }

    .status-pill {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .status-paid { background: rgba(74, 222, 128, 0.1); color: #4ade80; }
    .status-pending { background: rgba(251, 191, 36, 0.1); color: #fbbf24; }
    .status-cancelled { background: rgba(248, 113, 113, 0.1); color: #f87171; }

    .action-link {
      color: var(--accent);
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
    }

    .action-link:hover {
      text-decoration: underline;
    }

    /* Detail View Styles */
    .detail-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 32px;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--md-sys-color-outline);
    }

    .product-list-item {
      display: flex;
      gap: 20px;
      padding: 16px 0;
      border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .product-img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
      background: var(--md-sys-color-surface-variant);
    }

    .product-info {
      flex: 1;
    }

    .product-name {
      font-weight: 600;
      margin-bottom: 4px;
    }

    .product-price {
      color: var(--muted);
      font-size: 14px;
    }

    .total-summary {
      margin-top: 32px;
      padding-top: 16px;
      border-top: 1px solid var(--md-sys-color-outline);
      display: flex;
      justify-content: flex-end;
      font-size: 20px;
      font-weight: 700;
    }

    .address-card {
      margin-top: 32px;
      padding: 20px;
      background: rgba(255,255,255,0.03);
      border-radius: 16px;
      border: 1px solid var(--md-sys-color-outline);
    }

    .address-label {
      font-size: 12px;
      color: var(--muted);
      text-transform: uppercase;
      margin-bottom: 8px;
    }

    .form-group {
      margin-bottom: 24px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-size: 14px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .form-control {
      width: 100%;
      padding: 14px;
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid var(--md-sys-color-outline);
      border-radius: 12px;
      color: white;
      font-family: inherit;
      font-size: 16px;
      transition: all 0.2s;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--accent);
      background: rgba(255, 255, 255, 0.05);
    }

    .btn-save {
      background: var(--accent);
      color: white;
      border: none;
      padding: 14px 28px;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-save:hover {
      background: #e55d00;
      transform: translateY(-2px);
    }
  </style>
</head>
<body class="account-page">
  <a href="index.php" class="back-button">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
    Back to Home
  </a>

  <div class="account-layout">

    <!-- Sidebar -->
    <aside class="account-sidebar">
      <div class="profile-summary-card">
        <div class="avatar-placeholder">
          <?php echo strtoupper(substr($_SESSION['customer_name'], 0, 1)); ?>
        </div>
        <h3 class="profile-name"><?php echo h($_SESSION['customer_name']); ?></h3>
        <p class="profile-email"><?php echo h($_SESSION['customer_email']); ?></p>
        
        <ul class="profile-nav">
          <li><a href="account.php?tab=dashboard" class="<?php echo ($tab == 'dashboard') ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
            Dashboard
          </a></li>
          <li><a href="account.php?tab=settings" class="<?php echo ($tab == 'settings') ? 'active' : ''; ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            Settings
          </a></li>
          <li><a href="auth.php?action=logout" style="color: #f87171;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Logout
          </a></li>
        </ul>

        <?php if (!empty($_SESSION['customer_address'])): ?>
          <div class="address-card" style="text-align: left; margin-top: 40px;">
            <div class="address-label">Primary Address</div>
            <p style="font-size: 14px; line-height: 1.5;"><?php echo h($_SESSION['customer_address']); ?></p>
          </div>
        <?php endif; ?>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="account-main">
      <div class="dashboard-header">
        <h2 style="font-family: 'Garamond', serif; font-size: 48px; margin-bottom: 8px;">Hello, <?php echo explode(' ', h($_SESSION['customer_name']))[0]; ?>!</h2>
        <p style="color: var(--muted); font-size: 18px;">Welcome back to your dashboard. Here's what's happening with your account.</p>
      </div>

      <div class="stats-grid">
        <div class="stat-card">
          <span class="stat-label">Total Orders</span>
          <span class="stat-value"><?php echo $totalOrders; ?></span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Total Spent</span>
          <span class="stat-value">Rp <?php echo number_format($totalSpent, 0, ',', '.'); ?></span>
        </div>
        <div class="stat-card">
          <span class="stat-label">Active Cart</span>
          <span class="stat-value"><?php echo array_sum($_SESSION['cart'] ?? []); ?> Items</span>
        </div>
      </div>

      <div class="content-card">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <?php if ($tab === 'settings'): ?>
            <h3 class="section-title">Account Settings</h3>
            <p style="color: var(--muted); margin-bottom: 32px;">Update your personal information and shipping details.</p>
            
            <form method="post" class="settings-form">
                <input type="hidden" name="action" value="update_address">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" value="<?php echo h($_SESSION['customer_name']); ?>" disabled style="opacity: 0.6; cursor: not-allowed;">
                    <small style="color: var(--muted); display: block; margin-top: 4px;">Name cannot be changed at this time.</small>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" class="form-control" value="<?php echo h($_SESSION['customer_email']); ?>" disabled style="opacity: 0.6; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label>Shipping Address</label>
                    <textarea name="address" class="form-control" style="min-height: 120px;" required><?php echo h($_SESSION['customer_address'] ?? ''); ?></textarea>
                </div>

                <div style="margin-top: 32px;">
                    <button type="submit" class="btn-save">Save Changes</button>
                </div>
            </form>

        <?php elseif ($viewId > 0 && !empty($transDetail)): ?>
            <div class="detail-header">
                <h3 class="section-title">Order #<?php echo $viewId; ?> Details</h3>
                <div style="display: flex; gap: 16px; align-items: center;">
                    <a href="receipt.php?id=<?php echo $viewId; ?>" target="_blank" class="action-link" style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        Print Receipt
                    </a>
                    <a href="account.php" class="action-link">Back to Orders</a>
                </div>
            </div>
            
            <div class="details-list">
                <?php $subtotalTotal = 0; foreach ($transDetail as $d): $subtotalTotal += $d['subtotal']; ?>
                    <div class="product-list-item">
                        <?php 
                            $imgPath = !empty($d['gambar']) ? h($d['gambar']) : 'assets/placeholder.jpg';
                            if (!empty($d['gambar']) && !str_contains($d['gambar'], '/') && !str_contains($d['gambar'], '\\')) {
                                $imgPath = 'uploads/' . h($d['gambar']);
                            }
                        ?>
                        <img src="<?php echo $imgPath; ?>" class="product-img" alt="">
                        <div class="product-info">
                            <div class="product-name"><?php echo h($d['nama_produk']); ?></div>
                            <div class="product-price"><?php echo (int)$d['qty']; ?> x Rp <?php echo number_format($d['harga_satuan'], 0, ',', '.'); ?></div>
                        </div>
                        <div style="font-weight: 600;">Rp <?php echo number_format($d['subtotal'], 0, ',', '.'); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="total-summary">
                <span>Total: Rp <?php echo number_format($subtotalTotal, 0, ',', '.'); ?></span>
            </div>

        <?php else: ?>
            <h3 class="section-title">Order History</h3>
            
            <?php if (empty($transactions)): ?>
                <div style="text-align: center; padding: 40px 0;">
                    <p style="color: var(--muted);">You haven't made any orders yet.</p>
                    <a href="index.php#products" class="stat-value" style="font-size: 18px; text-decoration: none; display: block; margin-top: 16px;">Start Shopping →</a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td style="font-weight: 600;">#<?php echo (int)$t['id_transaksi']; ?></td>
                                    <td style="color: var(--muted);"><?php echo date('M d, Y', strtotime($t['tanggal'])); ?></td>
                                    <td style="font-weight: 600;">Rp <?php echo number_format($t['total'], 0, ',', '.'); ?></td>
                                    <td><?php echo (int)$t['item_count']; ?></td>
                                    <td>
                                        <span class="status-pill status-<?php echo h($t['status']); ?>">
                                            <?php echo ucfirst(h($t['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 12px;">
                                            <a href="account.php?view=<?php echo $t['id_transaksi']; ?>" class="action-link">View</a>
                                            <a href="receipt.php?id=<?php echo $t['id_transaksi']; ?>" target="_blank" class="action-link" style="color: var(--muted);">Receipt</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    </main>
  </div>
</body>
</html>
