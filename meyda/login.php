<?php
require_once __DIR__ . '/auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        <div class="login-card-title">
          <h2>Login Customer</h2>
        </div>

        <?php if (!empty($error)): ?>
          <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="login-form">
        <form method="post">
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
        </form>
        </div>
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
