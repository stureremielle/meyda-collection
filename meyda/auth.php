<?php
// auth.php - session & authentication logic
// Ensure consistent session cookie params and start session safely
if (session_status() !== PHP_SESSION_ACTIVE) {
    // use a fixed session name to avoid collisions
    if (!headers_sent()) {
        session_name('meyda_session');
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    // Set cookie params (array form supported since PHP 7.3)
    $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    if (!headers_sent()) {
        session_set_cookie_params($cookieParams);
    }
    session_start();
}
require_once __DIR__ . '/config.php';

// Session Timeout Logic (5 minutes = 300 seconds)
define('SESSION_TIMEOUT', 300);

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    // Session expired
    logout('login.php?timeout=1');
}
$_SESSION['last_activity'] = time();

function isLoggedIn()
{
    return !empty($_SESSION['user_type']); // 'customer' or 'staff'
}

function isCustomer()
{
    return ($_SESSION['user_type'] ?? null) === 'customer';
}

function isStaff()
{
    return ($_SESSION['user_type'] ?? null) === 'staff';
}

function isAdmin()
{
    return (isStaff() && ($_SESSION['staff_role'] ?? null) === 'admin');
}

function customerLogin($email, $password)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id_pelanggan, nama, password_hash, alamat FROM pelanggan WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $customer = $stmt->fetch();

    if ($customer) {
        $hash = $customer['password_hash'] ?? null;
        if (!empty($hash)) {
            if (!password_verify($password, $hash))
                return false;
        }

        // Login success: attach cart to this customer's account in-session
        $_SESSION['user_type'] = 'customer';
        $_SESSION['customer_id'] = $customer['id_pelanggan'];
        $_SESSION['customer_name'] = $customer['nama'];
        $_SESSION['customer_email'] = $email;
        $_SESSION['customer_address'] = $customer['alamat'];

        // Ensure carts mapping exists
        if (!isset($_SESSION['carts']) || !is_array($_SESSION['carts']))
            $_SESSION['carts'] = [];

        $custId = $customer['id_pelanggan'];

        // Merge Logic: if guest cart exists, merge it into the customer's saved cart
        if (!empty($_SESSION['cart'])) {
            $stmtM = $pdo->prepare("INSERT INTO keranjang (id_pelanggan, id_produk, qty) VALUES (:id_pelanggan, :id_produk, :qty) ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)");
            foreach ($_SESSION['cart'] as $pid => $qty) {
                $stmtM->execute([
                    ':id_pelanggan' => $custId,
                    ':id_produk' => $pid,
                    ':qty' => $qty
                ]);
            }
        }

        // Fetch items from DB to populate session cart
        $stmtF = $pdo->prepare("SELECT id_produk, qty FROM keranjang WHERE id_pelanggan = :id");
        $stmtF->execute([':id' => $custId]);
        $dbCart = $stmtF->fetchAll(PDO::FETCH_KEY_PAIR);

        $_SESSION['cart'] = $dbCart;
        $_SESSION['cart_owner'] = $custId;

        return true;
    }
    return false;
}

function staffLogin($username, $password)
{
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id_user, username, nama_lengkap, role, password_hash FROM `user` WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $staff = $stmt->fetch();

    if ($staff) {
        $hash = $staff['password_hash'] ?? null;
        if (empty($hash)) {
            // No password set for this account
            return false;
        }
        if (!password_verify($password, $hash)) {
            return false;
        }
        $_SESSION['user_type'] = 'staff';
        $_SESSION['staff_id'] = $staff['id_user'];
        $_SESSION['staff_name'] = $staff['nama_lengkap'];
        $_SESSION['staff_role'] = $staff['role'];

        // Update last_login
        $upd = $pdo->prepare("UPDATE `user` SET last_login = NOW() WHERE id_user = :id");
        $upd->execute([':id' => $staff['id_user']]);

        return true;
    }
    return false;
}

function logout($redirect = 'index.php')
{
    // Clear the specific session keys
    unset($_SESSION['user_type']);
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_name']);
    unset($_SESSION['customer_email']);
    unset($_SESSION['staff_id']);
    unset($_SESSION['staff_name']);
    unset($_SESSION['staff_role']);
    unset($_SESSION['cart_owner']);
    unset($_SESSION['cart']);

    // Regenerate session id to avoid session fixation
    if (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    }

    // Destroy session completely
    $_SESSION = array();

    // Delete the session cookie if it exists
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Finally, destroy the session
    session_destroy();

    // Redirect to target page
    header('Location: ' . $redirect);
    exit;
}

// Handle logout only when auth.php is requested directly with action=logout
if (basename($_SERVER['SCRIPT_NAME']) === 'auth.php' && ($_GET['action'] ?? null) === 'logout') {
    logout();
}

function requireLogin($type = 'any')
{
    if (!isLoggedIn()) {
        header('Location: login?redirect=' . urlencode(str_replace('.php', '', $_SERVER['REQUEST_URI'])));
        exit;
    }
    if ($type === 'customer' && !isCustomer()) {
        http_response_code(403);
        die('Access denied. Customer only.');
    }
    if ($type === 'staff' && !isStaff()) {
        http_response_code(403);
        die('Access denied. Staff only.');
    }
    if ($type === 'admin' && !isAdmin()) {
        http_response_code(403);
        die('Access denied. Admin only.');
    }
}

/**
 * Generate a CSRF token
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token
 */
function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>