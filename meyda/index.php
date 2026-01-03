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

    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // For AJAX requests, just return JSON response
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'cart_count' => array_sum($_SESSION['cart'] ?? [])]);
        exit;
    } else {
        // For regular form submissions, redirect as before
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        header('Location: index.php');
        exit;
    }
}

if ($action === 'remove') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0 && isset($_SESSION['cart'][$id])) unset($_SESSION['cart'][$id]);
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    header('Location: index.php?action=cart'); exit;
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

      <!-- Main Banner Section -->
      <section class="main-banner full-screen-banner">
        <div class="banner-content">
          <div class="banner-text">
            <h2 class="banner-title">MAKE YOUR LOOK MORE <span class="highlight">SIGMA</span></h2>
            <a href="#products" class="shop-button">Shop it Now</a>
          </div>
          <div class="banner-image">
          <img src="assets/model.png" alt="Person wearing clothing">
          </div>
        </div>
      </section>

      <!-- Divider Line -->
      <div class="divider-line"></div>

      <!-- Category Filter Section -->
      <section class="category-filter no-card-filter">
        <div class="filter-container">
          <select id="categoryFilter" onchange="filterProducts()">
            <option value="all">All Categories</option>
            <?php
              // Get unique categories
              $catStmt = $pdo->query("SELECT DISTINCT k.nama_kategori FROM kategori_produk k JOIN produk p ON k.id_kategori = p.id_kategori WHERE p.stok > 0");
              $categories = $catStmt->fetchAll();
              foreach ($categories as $category):
            ?>
              <option value="<?php echo h($category['nama_kategori']); ?>"><?php echo h($category['nama_kategori']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </section>

      <section class="products-grid" id="products" aria-label="Featured products">
        <?php foreach ($products as $p): ?>
          <article class="product-card" data-category="<?php echo h($p['nama_kategori']); ?>">
            <?php if (!empty($p['gambar'])): ?>
              <img src="uploads/<?php echo h($p['gambar']); ?>" alt="<?php echo h($p['nama_produk']); ?>" class="product-img-real">
            <?php else: ?>
              <div class="product-img">IMG</div>
            <?php endif; ?>
            <h3><?php echo h($p['nama_produk']); ?></h3>
            <p class="muted"><?php echo h($p['nama_kategori']); ?></p>
            <p class="desc"><?php echo h($p['deskripsi']); ?></p>
            <p class="price">Rp <?php echo number_format($p['harga'],0,',','.'); ?></p>
            <form class="add-to-cart-form" method="post" action="index.php" onsubmit="addToCart(event, <?php echo (int)$p['id_produk']; ?>)">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="id" value="<?php echo (int)$p['id_produk']; ?>">
              <label style="display:none"><input type="number" name="qty" value="1" min="1"></label>
              <button type="submit" class="add-to-cart-btn no-text"<?php echo $p['stok']<=0 ? ' disabled' : ''; ?> aria-label="<?php echo $p['stok']>0 ? 'Tambah ke Keranjang' : 'Habis'; ?>"></button>
            </form>
          </article>
        <?php endforeach; ?>
      </section>
  </main>

  <footer class="site-footer">
    <div class="container"><small>&copy; MeyDa Collection</small></div>
  </footer>

  <script>
    function filterProducts() {
      const selectedCategory = document.getElementById('categoryFilter').value;
      const productCards = document.querySelectorAll('.product-card');

      productCards.forEach(card => {
        const cardCategory = card.getAttribute('data-category');

        if (selectedCategory === 'all' || cardCategory === selectedCategory) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    }

    // Update the Shop it Now button to smoothly scroll to products
    document.addEventListener('DOMContentLoaded', function() {
      const shopButtons = document.querySelectorAll('.shop-button');
      shopButtons.forEach(button => {
        button.addEventListener('click', function(e) {
          e.preventDefault();
          const targetId = this.getAttribute('href');
          const targetElement = document.querySelector(targetId);

          if (targetElement) {
            targetElement.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }
        });
      });
    });

    // Function to add item to cart via AJAX
    async function addToCart(event, productId) {
      event.preventDefault(); // Prevent the default form submission

      // Find the form element
      const form = event.target.closest('form.add-to-cart-form');
      const formData = new FormData(form);

      // Add header to indicate AJAX request
      const headers = {
        'X-Requested-With': 'XMLHttpRequest'
      };

      try {
        const response = await fetch('index.php', {
          method: 'POST',
          body: formData,
          headers: headers
        });

        if (response.ok) {
          const data = await response.json();

          // Update the cart count in the navigation
          updateCartCount();

          // Optional: Show a success message
          showNotification('Item ditambahkan ke keranjang!');
        } else {
          console.error('Error adding item to cart');
        }
      } catch (error) {
        console.error('Error:', error);
      }
    }

    // Function to update the cart count in the navigation
    function updateCartCount() {
      // We need to fetch the updated cart count from the server
      fetch('index.php?action=cart_count')
        .then(response => response.json())
        .then(data => {
          const cartLink = document.querySelector('a[href="index.php?action=cart"]');
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
      existingNotifications.forEach(notification => notification.remove());

      // Create a notification element
      const notification = document.createElement('div');
      notification.className = 'notification';
      notification.textContent = message;
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #4CAF50;
        color: white;
        padding: 15px;
        border-radius: 5px;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
      `;

      document.body.appendChild(notification);

      // Fade in
      setTimeout(() => {
        notification.style.opacity = '1';
      }, 10);

      // Remove after 3 seconds
      setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
          notification.remove();
        }, 300);
      }, 3000);
    }
  </script>
</body>
</html>
