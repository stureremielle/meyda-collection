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
  <style>
    .login-container { max-width: 400px; margin: 60px auto; padding: 20px; border: 1px solid #eef2f6; border-radius: 6px; background: #f8fafc; }
    .login-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
    .login-tabs a { flex: 1; padding: 10px; text-align: center; border: 1px solid #e6e9ee; cursor: pointer; text-decoration: none; color: #6b7280; border-radius: 4px 4px 0 0; }
    .login-tabs a.active { background: white; color: #1f6feb; border-bottom: 2px solid #1f6feb; }
    .login-form { background: white; padding: 20px; border-radius: 0 4px 4px 4px; border: 1px solid #eef2f6; border-top: none; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
    .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .form-group button { width: 100%; padding: 10px; background: #1f6feb; color: white; border: none; border-radius: 4px; cursor: pointer; }
    .form-group button:hover { opacity: 0.95; }
    .error-msg { color: #8b1e1e; background: #fff4f4; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    .register-link { text-align: center; margin-top: 15px; }
    .register-link a { color: #1f6feb; text-decoration: none; }
  </style>
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

  <main class="container">
    <div class="login-container">
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
              <button type="submit">Login</button>
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
              <button type="submit">Login</button>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container"><small>&copy; MeyDa Collection</small></div>
  </footer>
</body>
</html>
