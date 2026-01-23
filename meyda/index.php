<?php
// die("Reached index.php");
require_once __DIR__ . "/auth.php";
$pdo = getPDO();

// Simple router via "action"
$action = $_POST["action"] ?? ($_GET["action"] ?? "home");


if (!isset($_SESSION["cart"])) {
  $_SESSION["cart"] = [];
}

if ($action === "add" && $_SERVER["REQUEST_METHOD"] === "POST") {
  $id = (int) ($_POST["id"] ?? 0);
  $qty = max(1, (int) ($_POST["qty"] ?? 1));
  if ($id > 0) {
    if (!isset($_SESSION["cart"][$id])) {
      $_SESSION["cart"][$id] = 0;
    }
    $_SESSION["cart"][$id] += $qty;

    // Persist to DB if logged in
    if (isCustomer()) {
      $custId = $_SESSION['customer_id'];
      $stmt = $pdo->prepare("INSERT INTO keranjang (id_pelanggan, id_produk, qty) VALUES (:id_pelanggan, :id_produk, :qty) ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)");
      $stmt->execute([':id_pelanggan' => $custId, ':id_produk' => $id, ':qty' => $qty]);
    }
  }

  // Check if this is an AJAX request
  if (
    !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
    strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest"
  ) {
    // For AJAX requests, just return JSON response
    header("Content-Type: application/json");
    echo json_encode([
      "success" => true,
      "cart_count" => array_sum($_SESSION["cart"] ?? []),
    ]);
    exit();
  } else {
    // For regular form submissions, redirect as before
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_write_close();
    }
    header("Location: index.php");
    exit();
  }
}

if ($action === "remove") {
  $id = (int) ($_GET["id"] ?? 0);
  if ($id > 0 && isset($_SESSION["cart"][$id])) {
    unset($_SESSION["cart"][$id]);
  }
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
  }
  header("Location: index?action=cart");
  exit();
}

if ($action === "checkout" && $_SERVER["REQUEST_METHOD"] === "POST") {
  // Only logged-in customers can checkout
  if (!isCustomer()) {
    $error = "Please login as a customer to checkout.";
  } elseif (empty($_SESSION["cart"])) {
    $error = "Your cart is empty.";
  } else {
    try {
      $pdo->beginTransaction();
      // Determine user/staff relationship: if staff is logged in, set id_user to staff id; otherwise use DEFAULT_USER_ID
      $customerId = isCustomer() ? $_SESSION["customer_id"] : null;
      $idUser = isStaff()
        ? (int) $_SESSION["staff_id"]
        : (int) DEFAULT_USER_ID;

      // Verify id_user exists in the database; if not, try to fallback to any admin/staff account
      $chk = $pdo->prepare(
        "SELECT id_user FROM `user` WHERE id_user = :id LIMIT 1",
      );
      $chk->execute([":id" => $idUser]);
      $found = $chk->fetchColumn();
      if (!$found) {
        $chk2 = $pdo->query(
          "SELECT id_user FROM `user` WHERE role IN ('admin','staff') ORDER BY id_user LIMIT 1",
        );
        $fallback = $chk2->fetchColumn();
        if ($fallback) {
          $idUser = (int) $fallback;
        } else {
          throw new Exception(
            "No staff/admin account found. Please contact an administrator.",
          );
        }
      }

      $stmt = $pdo->prepare(
        "INSERT INTO transaksi (id_user, id_pelanggan, tanggal, total, status) VALUES (:id_user, :id_pelanggan, NOW(), 0, 'paid')",
      );
      $stmt->execute([
        ":id_user" => $idUser,
        ":id_pelanggan" => $customerId,
      ]);
      $id_transaksi = $pdo->lastInsertId();

      $total = 0.0;
      $stmtP = $pdo->prepare(
        "SELECT id_produk, nama_produk, harga, stok FROM produk WHERE id_produk = :id",
      );
      $stmtInsertDetail = $pdo->prepare(
        "INSERT INTO detail_transaksi (id_transaksi, id_produk, qty, harga_satuan, subtotal) VALUES (:id_transaksi, :id_produk, :qty, :harga, :subtotal)",
      );
      $stmtUpdateStok = $pdo->prepare(
        "UPDATE produk SET stok = GREATEST(stok - :qty, 0) WHERE id_produk = :id",
      );

      foreach ($_SESSION["cart"] as $pid => $qty) {
        $stmtP->execute([":id" => $pid]);
        $p = $stmtP->fetch();
        if (!$p) {
          throw new Exception("Product not found: " . $pid);
        }
        $harga = (float) $p["harga"];
        $subtotal = $harga * $qty;
        $stmtInsertDetail->execute([
          ":id_transaksi" => $id_transaksi,
          ":id_produk" => $pid,
          ":qty" => $qty,
          ":harga" => $harga,
          ":subtotal" => $subtotal,
        ]);
        $stmtUpdateStok->execute([":qty" => $qty, ":id" => $pid]);
        $total += $subtotal;
      }

      $stmtUp = $pdo->prepare(
        "UPDATE transaksi SET total = :total WHERE id_transaksi = :id",
      );
      $stmtUp->execute([":total" => $total, ":id" => $id_transaksi]);

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
      $_SESSION["cart"] = [];
      
      // Clear DB cart if logged in
      if (isCustomer()) {
        $stmtDB = $pdo->prepare("DELETE FROM keranjang WHERE id_pelanggan = :cid");
        $stmtDB->execute([':cid' => $_SESSION['customer_id']]);
      }

      $success = "Checkout successful. Transaction ID: $id_transaksi";
    } catch (Exception $e) {
      $pdo->rollBack();
      $error = "An error occurred during checkout: " . $e->getMessage();
    }
  }
}

// Handle cart count API request
if ($action === "cart_count") {
  header("Content-Type: application/json");
  echo json_encode(["count" => array_sum($_SESSION["cart"] ?? [])]);
  exit();
}

$stmt = $pdo->query(
  "SELECT p.id_produk, p.nama_produk, p.deskripsi, p.harga, p.stok, p.gambar, k.nama_kategori FROM produk p JOIN kategori_produk k ON p.id_kategori = k.id_kategori ORDER BY p.created_at LIMIT 12",
);
$products = $stmt->fetchAll();

$cart_items = [];
$cart_total = 0.0;
if (!empty($_SESSION["cart"])) {
  $ids = array_keys($_SESSION["cart"]);
  // prepare placeholders
  $placeholders = implode(",", array_fill(0, count($ids), "?"));
  $stmtC = $pdo->prepare(
    "SELECT id_produk, nama_produk, harga FROM produk WHERE id_produk IN ($placeholders)",
  );
  $stmtC->execute($ids);
  $rows = $stmtC->fetchAll();
  $rowsAssoc = [];
  foreach ($rows as $r) {
    $rowsAssoc[$r["id_produk"]] = $r;
  }
  foreach ($_SESSION["cart"] as $pid => $q) {
    $p = $rowsAssoc[$pid] ?? null;
    if ($p) {
      $subtotal = $p["harga"] * $q;
      $cart_items[] = [
        "id" => $pid,
        "nama" => $p["nama_produk"],
        "qty" => $q,
        "harga" => $p["harga"],
        "subtotal" => $subtotal,
      ];
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
  <link rel="stylesheet" href="<?php echo asset('styles.css'); ?>">
  <link rel="stylesheet" href="<?php echo asset('product-card.css'); ?>">
</head>

<body>
  <header class="site-header">
    <div class="minimal-header-container">
      <div class="header-nav-left">
        <a href="index.php" class="brand-minimal">meyda</a>
      </div>
      <div class="header-nav-middle">
        <a href="#products" class="header-link">collection</a>
        <a href="#newsletter" class="header-link">newsletter</a>
      </div>
      <div class="header-nav-right">
        <a href="cart" class="header-link">cart (<?php echo array_sum($_SESSION["cart"] ?? []); ?>)</a>
        <?php if (isLoggedIn()): ?>
          <?php if (isCustomer()): ?>
            <a href="account" class="header-link">profile</a>
            <a href="auth?action=logout" class="header-link">logout</a>
          <?php elseif (isStaff()): ?>
            <a href="auth?action=logout" class="header-link">logout</a>
          <?php endif; ?>
        <?php else: ?>
          <a href="login?mode=customer" class="header-link">login</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="container">
    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?php echo h($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><?php echo h($success); ?></div>
    <?php endif; ?>

    <?php
    // Include the HeroCard component
    require_once __DIR__ . "/HeroCard.php";
    echo renderHeroCard([
      "headline" =>
        "Discover our latest collection of premium fashion items designed to elevate your style.",
      "slogan" => "",
      "cta_text" => "Shop Now",
    ]);

    // Include the Features component
    require_once __DIR__ . "/Features.php";
    echo renderFeaturesBar();
    ?>
    <section class="our_collection">
      <h2 id="products">Our Collection</h2>
    </section>

    <!-- Category Filter Section -->
    <section class="category-filter no-card-filter">
      <div class="filter-container">
        <div class="category-pills">
          <button class="category-pill active" data-category="all">All</button>
          <?php
          // Get unique categories
          $catStmt = $pdo->query(
            "SELECT DISTINCT k.nama_kategori FROM kategori_produk k JOIN produk p ON k.id_kategori = p.id_kategori WHERE p.stok > 0 ORDER BY k.nama_kategori ASC",
          );
          $categories = $catStmt->fetchAll();
          foreach ($categories as $category): ?>
            <button class="category-pill" data-category="<?php echo h(
              $category["nama_kategori"],
            ); ?>"><?php echo h($category["nama_kategori"]); ?></button>
          <?php endforeach;
          ?>
        </div>
      </div>
    </section>

    <section class="products-grid" id="products" aria-label="Featured products">
      <?php foreach ($products as $p): ?>
        <article class="product-card" data-category="<?php echo h(
          $p["nama_kategori"],
        ); ?>">
          <?php if (!empty($p["gambar"])): ?>
            <img src="uploads/<?php echo h(
              $p["gambar"],
            ); ?>" alt="<?php echo h(
               $p["nama_produk"],
             ); ?>" class="product-img-real">
          <?php else: ?>
            <div class="product-img">IMG</div>
          <?php endif; ?>
          <h3><?php echo h($p["nama_produk"]); ?></h3>
          <p class="muted"><?php echo h($p["nama_kategori"]); ?></p>
          <p class="desc"><?php echo h($p["deskripsi"]); ?></p>
          <p class="price">Rp <?php echo number_format(
            $p["harga"],
            0,
            ",",
            ".",
          ); ?></p>
          <form class="add-to-cart-form" method="post" action="index.php" onsubmit="addToCart(event, <?php echo (int) $p[
            "id_produk"
          ]; ?>)">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="id" value="<?php echo (int) $p[
              "id_produk"
            ]; ?>">
            <div class="quantity-control">
              <button type="button" class="qty-btn" onclick="adjustQuantity(this, -1)">-</button>
              <input type="number" name="qty" value="1" min="1" max="<?php echo (int) $p['stok']; ?>" class="qty-input">
              <button type="button" class="qty-btn" onclick="adjustQuantity(this, 1)">+</button>
            </div>
            <button type="submit" class="add-to-cart-btn no-text" <?php echo $p[
              "stok"
            ] <= 0
              ? " disabled"
              : ""; ?> aria-label="<?php echo $p["stok"] > 0
                 ? "Add to Cart"
                 : "Out of Stock"; ?>"></button>
          </form>
        </article>
      <?php endforeach; ?>
    </section>

    <?php
    // Include the Newsletter component
    require_once __DIR__ . "/Newsletter.php";
    echo renderNewsletter();
    ?>
  </main>

  <?php include __DIR__ . "/_footer.php"; ?>

  <script>
    // Smooth scroll for header links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });

    function filterProducts(category) {
      const productCards = document.querySelectorAll('.product-card');
      const grid = document.querySelector('.products-grid');

      // Update active pill button
      document.querySelectorAll('.category-pill').forEach(pill => {
        pill.classList.remove('active');
      });

      const activePill = document.querySelector(`.category-pill[data-category="${category}"]`);
      if (activePill) activePill.classList.add('active');

      // Add a "filtering" class to the grid for coordinated transitions if needed
      grid.classList.add('filtering');

      productCards.forEach(card => {
        const cardCategory = card.getAttribute('data-category');

        // Start fade out
        card.style.opacity = '0';
        card.style.transform = 'translateY(10px)';

        setTimeout(() => {
          if (category === 'all' || cardCategory === category) {
            card.style.display = 'flex';
            // Trigger reflow
            card.offsetHeight;
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
          } else {
            card.style.display = 'none';
          }
        }, 300); // Wait for fade out to complete
      });

      setTimeout(() => {
        grid.classList.remove('filtering');
      }, 600);
    }



    // Update the Shop it Now button to smoothly scroll to products and handle other DOM ready tasks
    document.addEventListener('DOMContentLoaded', function () {
      const shopButtons = document.querySelectorAll('.shop-button');
      shopButtons.forEach(button => {
        button.addEventListener('click', function (e) {
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

      // Handle divider visibility - make it appear after scrolling down
      const divider = document.querySelector('.divider-line');
      if (divider) {
        // Initially hide the divider until it comes into view
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
            }
          });
        }, { threshold: 0.1 });

        observer.observe(divider);
      }

      // Add event listeners to category pills
      const categoryPills = document.querySelectorAll('.category-pill');
      categoryPills.forEach(pill => {
        pill.addEventListener('click', function () {
          const category = this.getAttribute('data-category');
          filterProducts(category);
        });
      });

      // Set default filter to 'all'
      filterProducts('all');
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
          showNotification('Item added to cart!');
        } else {
          console.error('Error adding item to cart');
        }
      } catch (error) {
        console.error('Error:', error);
      }
    }

    // Function showNotification (consolidated and styled)
    function showNotification(message, type = 'success') {
      const existing = document.querySelectorAll('.toast-notification');
      existing.forEach(n => n.remove());

      const notification = document.createElement('div');
      notification.className = `toast-notification toast-${type}`;

      const icon = type === 'success' ?
        `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" /><polyline points="22 4 12 14.01 9 11.01" /></svg>` :
        `<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

      notification.innerHTML = `
        <div class="toast-content">
          <span class="toast-icon">${icon}</span>
          <span class="toast-message">${message}</span>
        </div>
      `;

      document.body.appendChild(notification);

      // Trigger animation
      setTimeout(() => notification.classList.add('active'), 10);

      // Auto remove
      setTimeout(() => {
        notification.classList.remove('active');
        setTimeout(() => notification.remove(), 300);
      }, 3000);
    }

    // Function to update the cart count in the navigation (consolidated)
    function updateCartCount() {
      fetch('index.php?action=cart_count')
        .then(response => response.json())
        .then(data => {
          const cartLinks = document.querySelectorAll('a[href*="cart"]');
          cartLinks.forEach(cartLink => {
            const baseText = cartLink.textContent.includes('cart') ? 'cart' : cartLink.textContent.split('(')[0].trim();
            cartLink.innerHTML = `${baseText} (${data.count})`;
          });
        })
        .catch(error => console.error('Error updating cart count:', error));
    }

    // Function to adjust quantity in input field
    function adjustQuantity(btn, change) {
      const container = btn.closest('.quantity-control');
      const input = container.querySelector('.qty-input');
      let currentValue = parseInt(input.value) || 1;
      const minValue = parseInt(input.min) || 1;
      const maxValue = parseInt(input.max) || 999;

      let newValue = currentValue + change;

      // Ensure the value stays within bounds
      newValue = Math.max(minValue, Math.min(newValue, maxValue));

      input.value = newValue;
    }

  </script>
</body>

</html>