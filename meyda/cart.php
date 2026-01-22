<?php
require_once __DIR__ . '/auth.php';
$pdo = getPDO();

// Simple router via "action"
$action = $_POST['action'] ?? $_GET['action'] ?? 'view';


if (!isset($_SESSION['cart']))
  $_SESSION['cart'] = [];

if ($action === 'remove') {
  $id = (int) ($_GET['id'] ?? 0);
  if ($id > 0 && isset($_SESSION['cart'][$id]))
    unset($_SESSION['cart'][$id]);
  if (session_status() === PHP_SESSION_ACTIVE)
    session_write_close();
  header('Location: cart');
  exit;
}

if ($action === 'save_address' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isCustomer()) {
    $newAddress = trim($_POST['address'] ?? '');
    if (!empty($newAddress)) {
      $stmt = $pdo->prepare("UPDATE pelanggan SET alamat = :alamat WHERE id_pelanggan = :id");
      $stmt->execute([':alamat' => $newAddress, ':id' => $_SESSION['customer_id']]);
      $_SESSION['customer_address'] = $newAddress;
      $success = "Address updated successfully.";
    }
  }
}

if ($action === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  // Only logged-in customers can checkout
  if (!isCustomer()) {
    $error = 'Please login as a customer to checkout.';
  } elseif (empty($_SESSION['cart'])) {
    $error = 'Your cart is empty.';
  } else {
    try {
      $pdo->beginTransaction();
      // Determine user/staff relationship: if staff is logged in, set id_user to staff id; otherwise use DEFAULT_USER_ID
      $customerId = isCustomer() ? $_SESSION['customer_id'] : null;
      $idUser = isStaff() ? (int) $_SESSION['staff_id'] : (int) DEFAULT_USER_ID;

      // Verify id_user exists in the database; if not, try to fallback to any admin/staff account
      $chk = $pdo->prepare("SELECT id_user FROM `user` WHERE id_user = :id LIMIT 1");
      $chk->execute([':id' => $idUser]);
      $found = $chk->fetchColumn();
      if (!$found) {
        $chk2 = $pdo->query("SELECT id_user FROM `user` WHERE role IN ('admin','staff') ORDER BY id_user LIMIT 1");
        $fallback = $chk2->fetchColumn();
        if ($fallback) {
          $idUser = (int) $fallback;
        } else {
          throw new Exception('No staff/admin account found in the system. Please contact an administrator.');
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
        if (!$p)
          throw new Exception('Product not found: ' . $pid);
        $harga = (float) $p['harga'];
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
      $success = "Checkout successful. Transaction ID: $id_transaksi";
    } catch (Exception $e) {
      $pdo->rollBack();
      $error = 'Checkout failed: ' . $e->getMessage();
    }
  }
}

// Handle cart count API request
if ($action === 'cart_count') {
  header('Content-Type: application/json');
  echo json_encode(['count' => array_sum($_SESSION['cart'] ?? [])]);
  exit;
}

if ($action === 'update_qty' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');
  $id = (int) ($_POST['id'] ?? 0);
  $qty = (int) ($_POST['qty'] ?? 1);

  if ($id > 0 && isset($_SESSION['cart'][$id])) {
    // Basic check against database stock
    $stmtS = $pdo->prepare("SELECT stok FROM produk WHERE id_produk = :id");
    $stmtS->execute([':id' => $id]);
    $stok = (int) $stmtS->fetchColumn();

    $qty = max(1, min($qty, $stok));
    $_SESSION['cart'][$id] = $qty;

    // Prepare response data
    $stmtC = $pdo->prepare("SELECT harga FROM produk WHERE id_produk = :id");
    $stmtC->execute([':id' => $id]);
    $harga = (float) $stmtC->fetchColumn();
    $subtotal = $harga * $qty;

    // Recalculate total
    $total = 0;
    $ids = array_keys($_SESSION['cart']);
    if (!empty($ids)) {
      $placeholders = implode(',', array_fill(0, count($ids), '?'));
      $stmtAll = $pdo->prepare("SELECT id_produk, harga FROM produk WHERE id_produk IN ($placeholders)");
      $stmtAll->execute($ids);
      $prices = $stmtAll->fetchAll(PDO::FETCH_KEY_PAIR);
      foreach ($_SESSION['cart'] as $pid => $q) {
        $total += ($prices[$pid] ?? 0) * $q;
      }
    }

    echo json_encode([
      'success' => true,
      'qty' => $qty,
      'subtotal' => 'Rp ' . number_format($subtotal, 0, ',', '.'),
      'total' => 'Rp ' . number_format($total, 0, ',', '.'),
      'cart_count' => array_sum($_SESSION['cart'])
    ]);
  } else {
    echo json_encode(['success' => false]);
  }
  exit;
}

$cart_items = [];

$cart_total = 0.0;
if (!empty($_SESSION['cart'])) {
  $ids = array_keys($_SESSION['cart']);
  // prepare placeholders
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $stmtC = $pdo->prepare("SELECT id_produk, nama_produk, harga, gambar FROM produk WHERE id_produk IN ($placeholders)");
  $stmtC->execute($ids);
  $rows = $stmtC->fetchAll();
  $rowsAssoc = [];
  foreach ($rows as $r)
    $rowsAssoc[$r['id_produk']] = $r;
  foreach ($_SESSION['cart'] as $pid => $q) {
    $p = $rowsAssoc[$pid] ?? null;
    if ($p) {
      $subtotal = $p['harga'] * $q;
      $cart_items[] = [
        'id' => $pid,
        'nama' => $p['nama_produk'],
        'qty' => $q,
        'harga' => $p['harga'],
        'subtotal' => $subtotal,
        'gambar' => $p['gambar']
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
  <title>Shopping Cart - MeyDa Collection</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="cart-styles.css">
  <style>
    /* Virtual Account Display - Backup Inline */
    .va-display-container {
      background: rgba(255, 255, 255, 0.08);
      border: 1px dashed var(--md-sys-color-outline);
      border-radius: 16px;
      padding: 24px;
      margin: 24px 0;
      text-align: center;
    }

    .va-label {
      font-size: 14px;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-bottom: 12px;
      display: block;
    }

    .va-number {
      font-family: monospace;
      font-size: 32px;
      font-weight: 700;
      color: var(--accent);
      letter-spacing: 4px;
      word-break: break-all;
    }

    .va-copy-hint {
      font-size: 14px;
      color: var(--muted);
      margin-top: 12px;
      display: block;
    }
  </style>
</head>

<body class="auth-page">
  <main class="auth-center">
    <a href="index" class="back-button">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
        stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
      </svg>
      Back to Home
    </a>

    <div class="cart-container">
      <div class="cart-wrapper">
        <div class="cart-header">
          <h2 style="font-family: 'Garamond', serif; font-size: 48px; margin-bottom: 12px;">Your Cart</h2>
          <p style="color: var(--muted); font-size: 18px;">Review your items before proceeding to checkout</p>
        </div>

        <?php if (empty($cart_items)): ?>
          <div class="empty-cart-container">
            <div class="empty-cart-icon">
              <!-- Provided base64 icon -->
              <img
                src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0Ij48ZyBmaWxsPSJub25lIiBzdHJva2U9IiNmNGYxZjEiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgc3Ryb2tlLXdpZHRoPSIyIj48cGF0aCBkPSJNNCAxOWEyIDIgMCAxIDAgNCAwYTIgMiAwIDAgMC00IDAiLz48cGF0aCBkPSJNMTMgMTdINlYzSDQiLz48cGF0aCBkPSJtNiA1bDE0IDFsLTEgN0g2bTE2IDlsLTUtNW0wIDVsNS01Ii8+PC9nPjwvc3ZnPg=="
                style="width: 120px; height: 120px;">
            </div>
            <h3 class="empty-cart-message">Your cart is empty</h3>
            <p class="empty-cart-sub">Looks like you haven't added anything to your cart yet.</p>
            <a href="index#products" class="btn-continue-shopping">Start Shopping</a>
          </div>
        <?php else: ?>
          <div class="cart-items">
            <?php foreach ($cart_items as $it): ?>
              <div class="cart-item-card" id="card-<?php echo (int) $it['id']; ?>">
                <div style="display: flex; align-items: stretch; gap: 32px; flex: 1;">
                  <?php
                  $imgPath = !empty($it['gambar']) ? h($it['gambar']) : 'assets/placeholder.jpg';
                  if (!empty($it['gambar']) && !str_contains($it['gambar'], '/') && !str_contains($it['gambar'], '\\')) {
                    $imgPath = 'uploads/' . h($it['gambar']);
                  }
                  ?>
                  <img src="<?php echo $imgPath; ?>" alt="<?php echo h($it['nama']); ?>" class="cart-item-image">
                  <div class="cart-item-details">
                    <h3 class="cart-item-name"><?php echo h($it['nama']); ?></h3>
                    <p class="cart-item-price">Rp <?php echo number_format($it['harga'], 0, ',', '.'); ?></p>
                    <div class="cart-item-quantity">
                      <div class="quantity-control">
                        <button type="button" class="qty-btn"
                          onclick="changeQuantity(<?php echo (int) $it['id']; ?>, -1)">-</button>
                        <input type="number" id="qty-<?php echo (int) $it['id']; ?>" value="<?php echo (int) $it['qty']; ?>"
                          class="qty-input" readonly>
                        <button type="button" class="qty-btn"
                          onclick="changeQuantity(<?php echo (int) $it['id']; ?>, 1)">+</button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="remove-btn-container">
                  <span class="cart-item-subtotal" id="subtotal-<?php echo (int) $it['id']; ?>">Rp
                    <?php echo number_format($it['subtotal'], 0, ',', '.'); ?></span>
                  <a href="javascript:void(0)"
                    onclick="confirmRemove(<?php echo (int) $it['id']; ?>, '<?php echo addslashes(h($it['nama'])); ?>')"
                    class="remove-link">Remove</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="payment-section">
            <h3
              style="font-family: 'Futura', sans-serif; font-size: 20px; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 24px;">
              Payment Method</h3>
            <div class="payment-methods">
              <div class="payment-method-card active" onclick="selectPayment(this, 'cc')">
                <div class="method-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                    <line x1="1" y1="10" x2="23" y2="10"></line>
                  </svg>
                </div>
                <span class="method-name">Credit Card</span>
                <span class="method-info">Visa, Mastercard</span>
              </div>
              <div class="payment-method-card" onclick="selectPayment(this, 'va')">
                <div class="method-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                  </svg>
                </div>
                <span class="method-name">Bank Transfer</span>
                <span class="method-info">Virtual Account</span>
              </div>
            </div>
          </div>

          <div class="cart-summary">
            <div class="summary-row" style="color: var(--muted);">
              <span>Subtotal</span>
              <span id="summary-subtotal">Rp <?php echo number_format($cart_total, 0, ',', '.'); ?></span>
            </div>
            <div class="summary-row" style="color: var(--muted);">
              <span>Shipping</span>
              <span>Calculated at next step</span>
            </div>
            <div class="summary-row total-row">
              <span>Total</span>
              <span id="cart-total" style="color: var(--accent);">Rp
                <?php echo number_format($cart_total, 0, ',', '.'); ?></span>
            </div>
          </div>

          <div class="checkout-container" style="margin-top: 48px;">
            <?php if (!isCustomer()): ?>
              <div class="restriction-box">
                <p>Please login to proceed with your purchase.</p>
                <a href="login?redirect=cart" class="checkout-btn"
                  style="text-decoration: none; display: inline-block; text-align: center;">Login to Checkout</a>
              </div>
            <?php elseif (empty($_SESSION['customer_address'])): ?>
              <div class="restriction-box address-form-box">
                <h3 style="margin-bottom: 12px; font-size: 20px;">Shipping Address Required</h3>
                <p style="color: var(--muted); margin-bottom: 20px;">Please provide your shipping address to proceed.</p>
                <form action="cart.php?action=save_address" method="post">
                  <textarea name="address" required placeholder="Enter your full address here..."
                    style="width: 100%; min-height: 100px; margin-bottom: 16px;" class="sim-input"></textarea>
                  <button type="submit" class="checkout-btn" style="padding: 16px; font-size: 18px;">Save Address &
                    Continue</button>
                </form>
              </div>
            <?php else: ?>
              <div class="address-summary"
                style="margin-bottom: 24px; padding: 20px; background: rgba(255,255,255,0.03); border-radius: 16px; border: 1px solid var(--md-sys-color-outline);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                  <span
                    style="font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px;">Shipping
                    to:</span>
                  <a href="javascript:void(0)" onclick="toggleAddressEdit()" class="link"
                    style="font-size: 12px;">Change</a>
                </div>
                <p style="font-size: 16px; line-height: 1.5;"><?php echo h($_SESSION['customer_address']); ?></p>

                <div id="inline-address-edit"
                  style="display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--md-sys-color-outline);">
                  <form action="cart?action=save_address" method="post">
                    <textarea name="address" required class="sim-input"
                      style="width: 100%; min-height: 80px; margin-bottom: 12px;"><?php echo h($_SESSION['customer_address']); ?></textarea>
                    <button type="submit" class="link"
                      style="background: none; border: none; cursor: pointer; padding: 0;">Update Address</button>
                  </form>
                </div>
              </div>
              <button type="button" class="checkout-btn" style="padding: 24px; font-size: 24px;"
                onclick="showPaymentInputs()">Proceed to Payment</button>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <!-- Payment Simulation Overlay -->
  <div id="payment-overlay" class="payment-simulation-overlay">
    <div class="simulation-card">
      <div id="simulation-form-step" class="simulation-form active">
        <h3 id="sim-title" class="simulation-status" style="margin-bottom: 24px; text-align: center;">Payment Details
        </h3>

        <!-- CC Fields -->
        <div id="cc-fields">
          <div class="sim-form-group">
            <label>Card Number</label>
            <input type="text" class="sim-input" placeholder="0000 0000 0000 0000">
          </div>
          <div style="display: flex; gap: 16px;">
            <div class="sim-form-group" style="flex: 2;">
              <label>Expiry Date</label>
              <input type="text" class="sim-input" placeholder="MM/YY">
            </div>
            <div class="sim-form-group" style="flex: 1;">
              <label>CVV</label>
              <input type="text" class="sim-input" placeholder="000">
            </div>
          </div>
        </div>

        <!-- VA Fields -->
        <div id="va-fields" style="display: none;">
          <div class="sim-form-group">
            <label>Select Bank</label>
            <select class="sim-input">
              <option>BCA</option>
              <option>Mandiri</option>
              <option>BNI</option>
              <option>BRI</option>
            </select>
          </div>
        </div>

        <button type="button" class="checkout-btn" onclick="startFinalSimulation()">Confirm & Pay</button>
        <button type="button" class="modal-btn modal-btn-cancel" style="margin-top: 12px; width: 100%; height: 50px;"
          onclick="closePaymentOverlay()">Cancel</button>
      </div>

      <div id="simulation-loading" style="display: none;">
        <div class="simulation-loader"></div>
        <p class="simulation-status">Processing Payment</p>
        <p class="simulation-message">Please wait while we secure your transaction...</p>
      </div>

      <div id="simulation-success" style="display: none;">
        <div class="success-icon active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
            stroke-linejoin="round" style="width: 40px; height: 40px;">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
        </div>
        <p class="simulation-status" id="success-header">Payment Successful!</p>
        <p class="simulation-message" id="simulation-message-text">Your order has been placed successfully.</p>
        <div id="va-result"></div>
        <p id="redirect-hint" style="margin-top: 24px; font-size: 14px; color: var(--muted);">Redirecting you shortly...
        </p>
      </div>
    </div>
  </div>

  <!-- Custom Confirmation Modal -->
  <div id="confirm-modal" class="custom-modal-overlay">
    <div class="custom-modal-card">
      <div class="modal-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
          stroke-linejoin="round">
          <polyline points="3 6 5 6 21 6"></polyline>
          <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
          <line x1="10" y1="11" x2="10" y2="17"></line>
          <line x1="14" y1="11" x2="14" y2="17"></line>
        </svg>
      </div>
      <h3 class="modal-title">Remove Item?</h3>
      <p class="modal-message">Are you sure you want to remove <strong id="modal-item-name"></strong> from your cart?
      </p>
      <div class="modal-actions">
        <button type="button" class="modal-btn modal-btn-cancel" onclick="closeConfirmModal()">Cancel</button>
        <button type="button" class="modal-btn modal-btn-confirm" id="confirm-remove-btn">Yes, Remove</button>
      </div>
    </div>
  </div>

  <form id="checkout-form" method="post" action="cart?action=checkout" style="display: none;">
    <input type="hidden" name="action" value="checkout">
  </form>

  <script>
    let selectedPaymentMethod = 'cc';
    let itemToRemoveId = null;

    function selectPayment(element, method) {
      document.querySelectorAll('.payment-method-card').forEach(card => card.classList.remove('active'));
      element.classList.add('active');
      selectedPaymentMethod = method;
    }

    function showPaymentInputs() {
      const overlay = document.getElementById('payment-overlay');
      const ccFields = document.getElementById('cc-fields');
      const vaFields = document.getElementById('va-fields');
      const simTitle = document.getElementById('sim-title');

      if (selectedPaymentMethod === 'cc') {
        ccFields.style.display = 'block';
        vaFields.style.display = 'none';
        simTitle.textContent = 'Credit Card Details';
      } else {
        ccFields.style.display = 'none';
        vaFields.style.display = 'block';
        simTitle.textContent = 'Bank Selection';
      }

      overlay.classList.add('active');
    }

    function closePaymentOverlay() {
      document.getElementById('payment-overlay').classList.remove('active');
    }

    function startFinalSimulation() {
      const formStep = document.getElementById('simulation-form-step');
      const loader = document.getElementById('simulation-loading');
      const success = document.getElementById('simulation-success');
      const simMessage = document.getElementById('simulation-message-text');
      const successHeader = document.getElementById('success-header');
      const redirectHint = document.getElementById('redirect-hint');

      formStep.style.display = 'none';
      loader.style.display = 'block';

      // Generate VA if needed
      let vaHtml = '';
      if (selectedPaymentMethod === 'va') {
        const bank = document.querySelector('#va-fields select').value;
        const vaNumber = Math.floor(Math.random() * 9000000000000) + 1000000000000;
        vaHtml = `
          <div class="va-display-container">
            <span class="va-label">${bank} Virtual Account</span>
            <div class="va-number">${vaNumber}</div>
            <span class="va-copy-hint">Copy this number and complete yourpayment</span>
          </div>
        `;
        successHeader.textContent = 'Order Reserved';
        simMessage.textContent = 'Please complete your payment using the Virtual Account below.';
      } else {
        successHeader.textContent = 'Payment Successful!';
        simMessage.textContent = 'Your order has been placed successfully.';
      }

      const vaContainer = document.getElementById('va-result');
      vaContainer.innerHTML = vaHtml;

      setTimeout(() => {
        loader.style.display = 'none';
        success.style.display = 'block';

        if (selectedPaymentMethod === 'va') {
          // No auto-redirect for VA, show a button
          redirectHint.style.display = 'none';
          const finishBtn = document.createElement('button');
          finishBtn.type = 'button';
          finishBtn.className = 'checkout-btn';
          finishBtn.style.marginTop = '24px';
          finishBtn.textContent = "I already paid";
          finishBtn.onclick = () => document.getElementById('checkout-form').submit();
          vaContainer.appendChild(finishBtn);
        } else {
          // Auto-redirect for Credit Card as before
          redirectHint.style.display = 'block';
          setTimeout(() => {
            document.getElementById('checkout-form').submit();
          }, 2000);
        }
      }, 2500);
    }

    function confirmRemove(id, name) {
      itemToRemoveId = id;
      document.getElementById('modal-item-name').textContent = name;
      document.getElementById('confirm-modal').classList.add('active');

      document.getElementById('confirm-remove-btn').onclick = function () {
        window.location.href = `cart?action=remove&id=${id}`;
      };
    }

    function closeConfirmModal() {
      document.getElementById('confirm-modal').classList.remove('active');
    }

    function toggleAddressEdit() {
      const el = document.getElementById('inline-address-edit');
      el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
    }

    async function changeQuantity(productId, change) {
      const input = document.getElementById(`qty-${productId}`);
      const currentQty = parseInt(input.value);
      const newQty = currentQty + change;

      if (newQty < 1) return;

      const formData = new FormData();
      formData.append('action', 'update_qty');
      formData.append('id', productId);
      formData.append('qty', newQty);

      try {
        const response = await fetch('cart.php', {
          method: 'POST',
          body: formData
        });
        const data = await response.json();

        if (data.success) {
          input.value = data.qty;
          document.getElementById(`subtotal-${productId}`).textContent = data.subtotal;
          document.getElementById('cart-total').textContent = data.total;
          document.getElementById('summary-subtotal').textContent = data.total;
        }
      } catch (error) {
        console.error('Error changing quantity:', error);
      }
    }
  </script>
</body>

</html>
<?php
// Wrap session/header cleanup in a shutdown function to be safe
register_shutdown_function(function () {
  if (session_status() === PHP_SESSION_ACTIVE)
    session_write_close();
});
?>