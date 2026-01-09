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

function isLoggedIn() {
    return !empty($_SESSION['user_type']); // 'customer' or 'staff'
}

function isCustomer() {
    return ($_SESSION['user_type'] ?? null) === 'customer';
}

function isStaff() {
    return ($_SESSION['user_type'] ?? null) === 'staff';
}

function isAdmin() {
    return (isStaff() && ($_SESSION['staff_role'] ?? null) === 'admin');
}

function customerLogin($email, $password) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id_pelanggan, nama, password_hash FROM pelanggan WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $customer = $stmt->fetch();

    if ($customer) {
        $hash = $customer['password_hash'] ?? null;
        if (!empty($hash)) {
            if (!password_verify($password, $hash)) return false;
        }

        // Login success: attach cart to this customer's account in-session
        $_SESSION['user_type'] = 'customer';
        $_SESSION['customer_id'] = $customer['id_pelanggan'];
        $_SESSION['customer_name'] = $customer['nama'];
        $_SESSION['customer_email'] = $email;

        // Ensure carts mapping exists
        if (!isset($_SESSION['carts']) || !is_array($_SESSION['carts'])) $_SESSION['carts'] = [];

        $custId = $customer['id_pelanggan'];
        // If there's a transient cart (guest) in session, save it under this customer
        if (!empty($_SESSION['cart'])) {
            $_SESSION['carts'][$custId] = $_SESSION['cart'];
        }

        // Load saved cart for this customer (if any)
        $_SESSION['cart'] = $_SESSION['carts'][$custId] ?? [];
        $_SESSION['cart_owner'] = $custId;

        return true;
    }
    return false;
}

function staffLogin($username, $password) {
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

function logout() {
    // Remove only authentication-related keys and clear transient cart
    $cartOwner = $_SESSION['cart_owner'] ?? null;

    // Save customer's cart into persistent in-session storage if present
    if ($cartOwner && isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        if (!isset($_SESSION['carts']) || !is_array($_SESSION['carts'])) $_SESSION['carts'] = [];
        $_SESSION['carts'][$cartOwner] = $_SESSION['cart'];
    }

    // Remove all authentication-related keys
    unset($_SESSION['user_type']);
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_name']);
    unset($_SESSION['customer_email']);
    unset($_SESSION['staff_id']);
    unset($_SESSION['staff_name']);
    unset($_SESSION['staff_role']);
    unset($_SESSION['cart_owner']);

    // Clear transient cart so guest has empty cart
    $_SESSION['cart'] = [];

    // Regenerate session id to avoid session fixation
    if (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    }

    // Destroy all session data
    $_SESSION = array();

    // Delete the session cookie if it exists
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finally, destroy the session
    session_destroy();
    
    // Redirect to home page
    header('Location: index.php');
    exit;
}

// Handle logout only when auth.php is requested directly with action=logout
if (basename($_SERVER['SCRIPT_NAME']) === 'auth.php' && ($_GET['action'] ?? null) === 'logout') {
    logout();
}

function requireLogin($type = 'any') {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if ($type === 'customer' && !isCustomer()) {
        http_response_code(403);
        die('Akses hanya untuk customer.');
    }
    if ($type === 'staff' && !isStaff()) {
        http_response_code(403);
        die('Akses hanya untuk staff.');
    }
    if ($type === 'admin' && !isAdmin()) {
        http_response_code(403);
        die('Akses hanya untuk admin.');
    }
}

/**
 * Generate a CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
