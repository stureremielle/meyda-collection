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
    .login-container { max-width: 420px; margin: 48px auto; padding: 0; border-radius: 12px; background: transparent; }
    .login-card { background: var(--md-sys-color-surface); border: 1px solid var(--md-sys-color-outline); border-radius: 12px; box-shadow: var(--elevation-1); overflow: hidden }
    .login-tabs { display: flex; gap: 8px; margin: 0; }
    .login-tabs a { flex: 1; padding: 12px; text-align: center; cursor: pointer; text-decoration: none; color: var(--muted); border-bottom: 2px solid transparent }
    .login-tabs a.active { color: var(--accent); border-bottom-color: var(--accent); font-weight:600 }
    .login-form { padding: 20px; }
    .form-group { margin-bottom: 14px; }
    .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color:var(--muted); }
    .form-group input { width: 100%; padding: 12px; border: 1px solid var(--md-sys-color-outline); border-radius: 10px; background: var(--md-sys-color-surface); }
    .form-group button { width: 100%; padding: 12px; background: var(--accent); color: var(--md-sys-color-on-primary); border: none; border-radius: 12px; cursor: pointer; font-weight:600 }
    .form-group button:hover { transform: translateY(-1px) }
    .error-msg { color: #8b1e1e; background: #fff4f4; padding: 10px; border-radius: 8px; margin-bottom: 12px; }
    .register-link { text-align: center; margin-top: 12px; }
    .register-link a { color: var(--accent); text-decoration: none; font-weight:600 }
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
