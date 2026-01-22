<?php
require_once __DIR__ . '/auth.php';

$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // We'll handle the logic in request_reset_process.php or here
        // For simplicity, let's keep it here or redirect
        header('Location: request_reset_process.php?email=' . urlencode($email));
        exit;
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Forgot Password - MeyDa Collection</title>
    <link rel="stylesheet" href="<?php echo asset('styles.css'); ?>">
</head>

<body class="auth-page">
    <main class="auth-center">
        <a href="login" class="back-button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Back to Login
        </a>
        <div class="login-container">
            <div class="login-card">
                <div class="login-card-title">
                    <h2>Forgot Password</h2>
                    <p style="margin-top: 8px; color: var(--muted); font-size: 14px;">Enter your email to receive a password reset link</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo h($error); ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo h($success); ?></div>
                <?php endif; ?>

                <form method="post" class="login-form" action="request_reset_process.php">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required placeholder="name@example.com">
                    </div>
                    <button type="submit" class="btn-primary">Send Reset Link</button>
                </form>
                <p style="margin-top:24px; text-align:center; font-size:14px; color:var(--muted);">
                    Suddenly remembered? <a href="login" class="link">Login here</a>
                </p>
            </div>
        </div>
    </main>
</body>

</html>
