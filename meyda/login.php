<?php
require_once __DIR__ . '/auth.php';

if (isset($_GET['logout'])) {
  logout('index');
}

$error = null;
$success = null;
if (isset($_GET['timeout'])) {
  $success = "Your session has expired due to inactivity. Please login again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = trim($_POST['password'] ?? '');
  if (empty($email) || empty($password)) {
    $error = 'Email and password are required.';
  } else {
    if (customerLogin($email, $password)) {
      $redirect = $_GET['redirect'] ?? 'index';
      header('Location: ' . $redirect);
      exit;
    } else {
      $error = 'Invalid email or password. Please try again or register if you don\'t have an account.';
    }
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - MeyDa Collection</title>
  <link rel="stylesheet" href="<?php echo asset('styles.css'); ?>">
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
    <div class="login-container">
      <div class="login-card">
        <div class="login-card-title">
          <h2>Welcome Back</h2>
          <p style="margin-top: 8px; color: var(--muted); font-size: 14px;">Enter your details to access your account
          </p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
          <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>

        <form method="post" class="login-form">
          <input type="hidden" name="action" value="login">
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required placeholder="name@example.com">
          </div>
          <div class="form-group">
            <label>Password</label>
            <div class="password-field-wrapper">
              <input type="password" name="password" id="password" required placeholder="••••••••">
              <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>
          <div style="text-align: right; margin-bottom: 24px;">
            <a href="forgot_password" class="link" style="font-size: 14px;">Forgot Password?</a>
          </div>
          <button type="submit" class="btn-primary">Login</button>
        </form>
        <p style="margin-top:24px; text-align:center; font-size:14px; color:var(--muted);">
          Don't have an account? <a href="register" class="link">Register here</a>
        </p>
      </div>
    </div>
  </main>

  <script>
    function togglePassword(id, btn) {
      const input = document.getElementById(id);
      const icon = btn.querySelector('svg');
      if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
      } else {
        input.type = 'password';
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
      }
    }
  </script>
</body>

</html>