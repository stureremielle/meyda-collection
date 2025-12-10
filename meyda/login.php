<?php
require_once __DIR__ . '/auth.php';

$error = null;
$mode = $_GET['mode'] ?? 'customer'; // 'customer' or 'staff'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'customer') {
      $email = trim($_POST['email'] ?? '');
      $password = trim($_POST['password'] ?? '');
      if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi.';
      } else {
        if (customerLogin($email, $password)) {
          $redirect = $_GET['redirect'] ?? 'index.php';
          header('Location: ' . $redirect);
          exit;
        } else {
          $error = 'Email atau password salah. Jika Anda belum mendaftar, silakan daftar terlebih dahulu.';
        }
      }
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if (empty($username) || empty($password)) {
            $error = 'Username dan password harus diisi.';
        } else {
            if (staffLogin($username, $password)) {
                header('Location: admin/dashboard.php');
                exit;
            } else {
                $error = 'Username atau password salah.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - MeyDa Collection</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <h1 class="brand">MeyDa Collection</h1>
      <nav class="nav">
        <a href="index.php">Home</a>
      </nav>
    </div>
  </header>

  <main class="container auth-center">
    <div class="login-container">
      <div class="login-card">
        <div class="login-tabs">
          <a href="login.php?mode=customer<?php echo isset($_GET['redirect']) ? '&redirect=' . urlencode($_GET['redirect']) : ''; ?>" class="<?php echo $mode === 'customer' ? 'active' : ''; ?>">Customer</a>
          <a href="login.php?mode=staff<?php echo isset($_GET['redirect']) ? '&redirect=' . urlencode($_GET['redirect']) : ''; ?>" class="<?php echo $mode === 'staff' ? 'active' : ''; ?>">Staff</a>
        </div>

        <div class="login-form">
        <?php if (!empty($error)): ?>
          <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
          <?php if ($mode === 'customer'): ?>
            <div class="form-group">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
              <button type="submit" class="btn-primary">Login</button>
            </div>
            <div class="register-link">
              Belum punya akun? <a href="register.php">Daftar di sini</a>
            </div>
          <?php else: ?>
            <div class="form-group">
              <label for="username">Username</label>
              <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
              <button type="submit" class="btn-primary">Login</button>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-left"><small>&copy; MeyDa Collection</small></div>
    </div>
  </footer>
</body>
</html>
