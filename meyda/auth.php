<?php
// auth.php - session & authentication logic
session_start();
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

    if ($customer && !empty($customer['password_hash'])) {
        if (password_verify($password, $customer['password_hash'])) {
            $_SESSION['user_type'] = 'customer';
            $_SESSION['customer_id'] = $customer['id_pelanggan'];
            $_SESSION['customer_name'] = $customer['nama'];
            $_SESSION['customer_email'] = $email;
            return true;
        }
        return false;
    }
    // If no password is set for customer (old data), allow login by email only
    if ($customer && empty($customer['password_hash'])) {
        $_SESSION['user_type'] = 'customer';
        $_SESSION['customer_id'] = $customer['id_pelanggan'];
        $_SESSION['customer_name'] = $customer['nama'];
        $_SESSION['customer_email'] = $email;
        return true;
    }
    return false;
}

function staffLogin($username, $password) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT id_user, username, nama_lengkap, role FROM `user` WHERE username = :username");
    $stmt->execute([':username' => $username]);
    $staff = $stmt->fetch();
    
    if ($staff && password_verify($password, $staff['password_hash'])) {
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
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle logout action if triggered via GET
if ($_GET['action'] ?? null === 'logout') {
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
?>
