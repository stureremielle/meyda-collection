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
  <!-- Styles are centralized in styles.css -->
</header>
