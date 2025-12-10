<?php
// Shared admin header
// Usage: include __DIR__ . '/_header.php'; placed inside <body>
?>
<header class="site-header">
  <div class="container">
    <h1 class="brand">MeyDa Collection - Admin</h1>
    <nav class="nav">
      <?php $cur = basename($_SERVER['PHP_SELF']); ?>
      <a href="dashboard.php" class="<?php echo $cur === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
      <a href="products.php" class="<?php echo $cur === 'products.php' ? 'active' : ''; ?>">Produk</a>
      <a href="categories.php" class="<?php echo $cur === 'categories.php' ? 'active' : ''; ?>">Kategori</a>
      <a href="reports.php" class="<?php echo $cur === 'reports.php' ? 'active' : ''; ?>">Laporan</a>
      <a href="transactions.php" class="<?php echo $cur === 'transactions.php' ? 'active' : ''; ?>">Transaksi</a>
      <a href="../index.php?action=logout" class="btn logout-btn" onclick="return confirm('Logout?')">Logout</a>
    </nav>
  </div>
  <style>
    /* Minimal nav styles kept with header to avoid relying on external stylesheet */
    .nav { display: flex; gap: 15px; margin-top: 10px; align-items: center; flex-wrap: wrap; }
    .nav a { color: #ff6d00; text-decoration: none; padding: 8px 12px; border-radius: 4px; transition: all 0.2s; }
    .nav a:hover { background: #404040; color: #ffffff; }
    .nav a.active { color: #ffffff; background: #ff6d00; font-weight: 600; }
    .logout-btn { margin-left: 12px; background: #8b1e1e; color: #fff; padding: 8px 12px; border-radius: 6px; text-decoration: none; }
    .logout-btn:hover { background: #6b1515; }
  </style>
</header>
