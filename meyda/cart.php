<?php
require_once __DIR__ . '/auth.php';
$pdo = getPDO();

// Simple router via "action"
$action = $_POST['action'] ?? $_GET['action'] ?? 'view';

function h($s){ return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($action === 'remove') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0 && isset($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    header('Location: cart.php'); exit;
}

if ($action === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  // Only logged-in customers can checkout
  if (!isCustomer()) {
    $error = 'Silakan login sebagai pelanggan untuk melakukan checkout.';
  } elseif (empty($_SESSION['cart'])) {
    $error = 'Keranjang kosong.';
    } else {
        try {
            $pdo->beginTransaction();
            // Determine user/staff relationship: if staff is logged in, set id_user to staff id; otherwise use DEFAULT_USER_ID
            $customerId = isCustomer() ? $_SESSION['customer_id'] : null;
            $idUser = isStaff() ? (int)$_SESSION['staff_id'] : (int)DEFAULT_USER_ID;

            // Verify id_user exists in the database; if not, try to fallback to any admin/staff account
            $chk = $pdo->prepare("SELECT id_user FROM `user` WHERE id_user = :id LIMIT 1");
            $chk->execute([':id' => $idUser]);
            $found = $chk->fetchColumn();
            if (!$found) {
              $chk2 = $pdo->query("SELECT id_user FROM `user` WHERE role IN ('admin','staff') ORDER BY id_user LIMIT 1");
              $fallback = $chk2->fetchColumn();
              if ($fallback) {
                $idUser = (int)$fallback;
              } else {
                throw new Exception('Tidak ada akun staff/admin di sistem. Silakan buat akun admin melalui /admin/setup.php terlebih dahulu.');
              }
            }

            $stmt = $pdo->prepare("INSERT INTO transaksi (id_user, id_pelanggan, tanggal, total, status) VALUES (:id_user, :id_pelanggan, NOW(), 0, 'paid')");
            $stmt->execute([':id_user' => $idUser, ':id_pelanggan' => $customerId]);
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

// Handle cart count API request
if ($action === 'cart_count') {
    header('Content-Type: application/json');
    echo json_encode(['count' => array_sum($_SESSION['cart'] ?? [])]);
    exit;
}

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
  <title>Keranjang - MeyDa Collection</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="cart-styles.css">
</head>
<body>
  <header class="site-header transparent-nav">
    <div class="container header-container">
      <h1 class="brand">meyda</h1>
      <nav class="nav">
        <a href="index.php">home</a>
        <a href="cart.php">cart (<?php echo array_sum($_SESSION['cart'] ?? []); ?>)</a>
        <?php if (isLoggedIn()): ?>
          <?php if (isCustomer()): ?>
            <a href="account.php">Hi, <?php echo htmlspecialchars($_SESSION['customer_name']); ?></a>
            <a href="auth.php?action=logout">logout</a>
          <?php elseif (isStaff()): ?>
            <a href="admin/products.php">admin</a>
            <a href="auth.php?action=logout">logout</a>
          <?php endif; ?>
        <?php else: ?>
          <a href="login.php?mode=customer">login</a>
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

    <div class="cart-container">
      <div class="cart-header">
        <h2>Keranjang</h2>
      </div>
      <?php if (empty($cart_items)): ?>
        <p>Keranjang kosong.</p>
      <?php else: ?>
        <div class="cart-items">
        <?php foreach($cart_items as $it): ?>
          <div class="cart-item-card">
            <div class="cart-item-details">
              <h3 class="cart-item-name"><?php echo h($it['nama']); ?></h3>
              <p class="cart-item-price">Rp <?php echo number_format($it['harga'],0,',','.'); ?></p>
              <div class="cart-item-quantity">
                <span>Qty: <?php echo (int)$it['qty']; ?></span>
                <span class="cart-item-subtotal">Rp <?php echo number_format($it['subtotal'],0,',','.'); ?></span>
              </div>
            </div>
            <a href="cart.php?action=remove&id=<?php echo (int)$it['id']; ?>" class="remove-btn">Hapus</a>
          </div>
        <?php endforeach; ?>
        </div>

        <div class="cart-summary">
          <div class="summary-row total-row">
            <span><strong>Total</strong></span>
            <span><strong>Rp <?php echo number_format($cart_total,0,',','.'); ?></strong></span>
          </div>
        </div>

        <div class="checkout-container">
          <form method="post" action="cart.php?action=checkout">
            <input type="hidden" name="action" value="checkout">
            <button type="submit" class="checkout-btn">Checkout</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__ . '/_footer.php'; ?>

  <script>
    // Function to update the cart count in the navigation
    function updateCartCount() {
      // We need to fetch the updated cart count from the server
      fetch('index.php?action=cart_count')
        .then(response => response.json())
        .then(data => {
          const cartLink = document.querySelector('a[href="cart.php"]');
          if (cartLink) {
            // Extract the text content before the cart count and append the new count
            cartLink.innerHTML = 'cart (' + data.count + ')';
          }
        })
        .catch(error => console.error('Error updating cart count:', error));
    }

    // Function to show a notification
    function showNotification(message) {
      // Remove any existing notifications
      const existingNotifications = document.querySelectorAll('.notification');
      existingNotifications.forEach(notification => {
        notification.remove();
      });

      // Create notification element
      const notification = document.createElement('div');
      notification.className = 'notification';
      notification.textContent = message;
      
      // Style the notification
      Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        backgroundColor: '#4CAF50',
        color: 'white',
        padding: '15px 20px',
        borderRadius: '5px',
        zIndex: '1000',
        boxShadow: '0 4px 8px rgba(0,0,0,0.2)',
        fontFamily: 'inherit',
        fontSize: '14px'
      });
      
      // Add to body
      document.body.appendChild(notification);
      
      // Auto-remove after 3 seconds
      setTimeout(() => {
        notification.remove();
      }, 3000);
    }
  </script>
</body>
</html>