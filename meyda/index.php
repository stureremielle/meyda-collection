<?php
require_once __DIR__ . '/auth.php';
$pdo = getPDO();

// Simple router via "action"
$action = $_POST['action'] ?? $_GET['action'] ?? 'home';

function h($s){ return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if ($id > 0) {
        if (!isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id] = 0;
        $_SESSION['cart'][$id] += $qty;
    }
    // ensure session data is written before redirect
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    header('Location: index.php?action=cart'); exit;
}

if ($action === 'remove') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0 && isset($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    header('Location: index.php?action=cart'); exit;
}

if ($action === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['cart'])) {
        $error = 'Keranjang kosong.';
    } else {
        try {
            $pdo->beginTransaction();
            // If customer is logged in, use their ID; otherwise NULL (guest)
            $customerId = isCustomer() ? $_SESSION['customer_id'] : null;
            $stmt = $pdo->prepare("INSERT INTO transaksi (id_user, id_pelanggan, tanggal, total, status) VALUES (:id_user, :id_pelanggan, NOW(), 0, 'paid')");
            $stmt->execute([':id_user' => DEFAULT_USER_ID, ':id_pelanggan' => $customerId]);
            $id_transaksi = $pdo->lastInsertId();

            $total = 0.0;
            $stmtP = $pdo->prepare("SELECT id_produk, nama_produk, harga, stok FROM produk WHERE id_produk = :id");
            $stmtInsertDetail = $pdo->prepare("INSERT INTO detail_transaksi (id_transaksi, id_produk, qty, harga_satuan, subtotal) VALUES (:id_transaksi, :id_produk, :qty, :harga, :subtotal)");
            $stmtUpdateStok = $pdo->prepare("UPDATE produk SET stok = GREATEST(stok - :qty, 0) WHERE id_produk = :id");

            foreach ($_SESSION['cart'] as $pid => $qty) {
                $stmtP->execute([':id' => $pid]);
                $p = $stmtP->fetch();
                if (!$p) throw new Exception('Produk tidak ditemukan: ' . $pid);
                $harga = (float)$p['harga'];
                $subtotal = $harga * $qty;
                $stmtInsertDetail->execute([
                    ':id_transaksi' => $id_transaksi,
                    ':id_produk' => $pid,
                    ':qty' => $qty,
                    ':harga' => $harga,
                    ':subtotal' => $subtotal
                ]);
                $stmtUpdateStok->execute([':qty' => $qty, ':id' => $pid]);
                $total += $subtotal;
            }

            $stmtUp = $pdo->prepare("UPDATE transaksi SET total = :total WHERE id_transaksi = :id");
            $stmtUp->execute([':total' => $total, ':id' => $id_transaksi]);

            // update laporan for current month (use heredoc to avoid accidental escaping)
            $sqlL = <<<'SQL'
INSERT INTO laporan (periode_year, periode_month, total_transaksi, total_pendapatan, total_item_terjual)
SELECT YEAR(t.tanggal), MONTH(t.tanggal),
       COUNT(DISTINCT t.id_transaksi),
       SUM(t.total),
       COALESCE(SUM(d.qty),0)
FROM transaksi t
LEFT JOIN detail_transaksi d ON t.id_transaksi = d.id_transaksi
WHERE YEAR(t.tanggal)=YEAR(CURRENT_DATE()) AND MONTH(t.tanggal)=MONTH(CURRENT_DATE())
GROUP BY YEAR(t.tanggal), MONTH(t.tanggal)
ON DUPLICATE KEY UPDATE
  total_transaksi=VALUES(total_transaksi),
  total_pendapatan=VALUES(total_pendapatan),
  total_item_terjual=VALUES(total_item_terjual),
  generated_at=CURRENT_TIMESTAMP
SQL;
            $stmtL = $pdo->prepare($sqlL);
            $stmtL->execute();

            $pdo->commit();
            $_SESSION['cart'] = [];
            $success = "Checkout berhasil. ID Transaksi: $id_transaksi";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Terjadi kesalahan saat checkout: ' . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar, k.nama_kategori FROM produk p JOIN kategori_produk k ON p.id_kategori = k.id_kategori ORDER BY p.created_at LIMIT 12");
$products = $stmt->fetchAll();

$cart_items = [];
$cart_total = 0.0;
if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    // prepare placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmtC = $pdo->prepare("SELECT id_produk, nama_produk, harga FROM produk WHERE id_produk IN ($placeholders)");
    $stmtC->execute($ids);
    $rows = $stmtC->fetchAll();
    $rowsAssoc = [];
    foreach ($rows as $r) $rowsAssoc[$r['id_produk']] = $r;
    foreach ($_SESSION['cart'] as $pid => $q) {
        $p = $rowsAssoc[$pid] ?? null;
        if ($p) {
            $subtotal = $p['harga'] * $q;
            $cart_items[] = ['id' => $pid, 'nama' => $p['nama_produk'], 'qty' => $q, 'harga' => $p['harga'], 'subtotal' => $subtotal];
            $cart_total += $subtotal;
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>MeyDa Collection</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <h1 class="brand">MeyDa Collection</h1>
      <nav class="nav">
        <a href="index.php">Home</a>
        <a href="index.php?action=cart">Cart (<?php echo array_sum($_SESSION['cart'] ?? []); ?>)</a>
        <?php if (isLoggedIn()): ?>
          <?php if (isCustomer()): ?>
            <a href="account.php">Hi, <?php echo htmlspecialchars($_SESSION['customer_name']); ?></a>
            <a href="auth.php?action=logout">Logout</a>
          <?php elseif (isStaff()): ?>
            <a href="admin/products.php">Admin</a>
            <a href="auth.php?action=logout">Logout</a>
          <?php endif; ?>
        <?php else: ?>
          <a href="login.php?mode=customer">Login</a>
          <a href="register.php">Register</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main class="container">
    <?php if(!empty($error)): ?>
      <div class="alert alert-error"><?php echo h($error); ?></div>
    <?php endif; ?>
    <?php if(!empty($success)): ?>
      <div class="alert alert-success"><?php echo h($success); ?></div>
    <?php endif; ?>

    <?php if ($action === 'cart'): ?>
      <h2>Keranjang</h2>
      <?php if (empty($cart_items)): ?>
        <p>Keranjang kosong.</p>
      <?php else: ?>
        <table class="cart">
          <thead><tr><th>Produk</th><th>Qty</th><th>Harga</th><th>Subtotal</th><th></th></tr></thead>
          <tbody>
            <?php foreach($cart_items as $it): ?>
              <tr>
                <td><?php echo h($it['nama']); ?></td>
                <td><?php echo (int)$it['qty']; ?></td>
                <td>Rp <?php echo number_format($it['harga'],0,',','.'); ?></td>
                <td>Rp <?php echo number_format($it['subtotal'],0,',','.'); ?></td>
                <td><a href="index.php?action=remove&id=<?php echo (int)$it['id']; ?>" class="link">Hapus</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><td colspan="3"><strong>Total</strong></td><td><strong>Rp <?php echo number_format($cart_total,0,',','.'); ?></strong></td><td></td></tr>
          </tfoot>
        </table>

        <form method="post" action="index.php?action=checkout">
          <input type="hidden" name="action" value="checkout">
          <button type="submit">Checkout</button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <section class="hero">
        <h2>Simple, quality clothing</h2>
        <p>Minimal design, trusted service.</p>
      </section>

      <section class="products-grid" aria-label="Featured products">
        <?php foreach ($products as $p): ?>
          <article class="product-card">
            <?php if (!empty($p['gambar'])): ?>
              <img src="uploads/<?php echo h($p['gambar']); ?>" alt="<?php echo h($p['nama_produk']); ?>" class="product-img-real">
            <?php else: ?>
              <div class="product-img">IMG</div>
            <?php endif; ?>
            <h3><?php echo h($p['nama_produk']); ?></h3>
            <p class="muted"><?php echo h($p['nama_kategori']); ?></p>
            <p class="desc"><?php echo h($p['deskripsi']); ?></p>
            <p class="price">Rp <?php echo number_format($p['harga'],0,',','.'); ?></p>
            <form method="post" action="index.php">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="id" value="<?php echo (int)$p['id_produk']; ?>">
              <label style="display:none"><input type="number" name="qty" value="1" min="1"></label>
              <button type="submit"<?php echo $p['stok']<=0 ? ' disabled' : ''; ?>><?php echo $p['stok']>0 ? 'Tambah ke Keranjang' : 'Habis'; ?></button>
            </form>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
  </main>

  <footer class="site-footer">
    <div class="container"><small>&copy; MeyDa Collection</small></div>
  </footer>
</body>
</html>
