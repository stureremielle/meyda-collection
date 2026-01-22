<?php
require_once __DIR__ . '/auth.php';

if (isset($_GET['logout'])) {
    logout('index.php');
}

$error = null;
$success = null;
if (isset($_GET['timeout'])) {
    $success = "Admin session expired. Please re-authenticate.";
}

// Simple rate limiting: limit login attempts
$max_attempts = 5;
$lockout_time = 900; // 15 minutes

// Initialize login attempts tracking if not exists
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}

// Clean old attempts (older than lockout_time)
$now = time();
$_SESSION['login_attempts'] = array_filter(
    $_SESSION['login_attempts'],
    function($timestamp) use ($now, $lockout_time) {
        return ($now - $timestamp) < $lockout_time;
    }
);

// Check if IP is locked out
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$recent_attempts = array_filter(
    $_SESSION['login_attempts'],
    function($attempt) use ($client_ip) {
        return isset($attempt['ip']) && $attempt['ip'] === $client_ip;
    }
);

if (count($recent_attempts) >= $max_attempts) {
    $error = 'Too many login attempts. Please try again later.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        if (staffLogin($username, $password)) {
            // Additional check to ensure the user is an admin
            if (isAdmin()) {
                // Clear login attempts on successful login
                $_SESSION['login_attempts'] = array_filter(
                    $_SESSION['login_attempts'],
                    function($attempt) use ($client_ip) {
                        return $attempt['ip'] !== $client_ip;
                    }
                );
                
                header('Location: admin/dashboard.php');
                exit;
            } else {
                $error = 'Access restricted to administrators only.';
                // Clear the session since they're not an admin
                session_destroy();
                session_start();
                
                // Log failed attempt
                $_SESSION['login_attempts'][] = [
                    'ip' => $client_ip,
                    'time' => $now
                ];
            }
        } else {
            $error = 'Invalid username or password.';
            
            // Log failed attempt
            $_SESSION['login_attempts'][] = [
                'ip' => $client_ip,
                'time' => $now
            ];
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login - MeyDa Collection</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="auth-page">
  <main class="auth-center">
    <a href="index.php" class="back-button">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
      Back to Store
    </a>

    <div class="login-container">
      <div class="login-card">
        <div class="login-card-title">
          <div style="color: var(--accent); margin-bottom: 16px;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
          </div>
          <h2 style="font-family: 'Garamond', serif; font-size: 32px;">Admin Portal</h2>
          <p style="margin-top: 8px; color: var(--muted); font-size: 14px;">Please authenticate to access the dashboard</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-error"><?php echo h($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
          <div class="alert alert-success"><?php echo h($success); ?></div>
        <?php endif; ?>

        <form method="post" class="login-form">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required placeholder="Enter username">
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <div class="password-field-wrapper">
              <input type="password" id="password" name="password" required placeholder="••••••••">
              <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </div>
          <div style="margin-top: 32px;">
            <button type="submit" class="btn-primary" style="width: 100%;">Login to Dashboard</button>
          </div>
        </form>
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
