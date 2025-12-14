<?php
require_once __DIR__ . '/auth.php';

$error = null;

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
    $error = 'Terlalu banyak percobaan login. Silakan coba lagi nanti.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi.';
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
                $error = 'Akses hanya untuk admin.';
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
            $error = 'Username atau password salah.';
            
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
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login - MeyDa Collection</title>
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
          <h2>Admin Login</h2>
        </div>

        <?php if (!empty($error)): ?>
          <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="login-form">
        <form method="post">
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