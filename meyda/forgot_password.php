<?php
require_once __DIR__ . '/auth.php';

$error = null;
$success = null;

// Helper function to send email via SMTP (AlwaysData or Gmail)
function sendResetEmail($toEmail, $resetLink) {
    if (!defined('SMTP_HOST') || SMTP_HOST === 'smtp.yourdomain.com' || empty(SMTP_HOST)) {
        return ['success' => false, 'error' => 'SMTP not configured in config.php.'];
    }

    $subject = 'Reset Your Password - MeyDa Collection';
    $message = "
        <html>
        <head>
            <title>Reset Your Password</title>
        </head>
        <body>
            <h1>Reset Your Password</h1>
            <p>You requested a password reset for your MeyDa Collection account.</p>
            <p>Click the link below to set a new password. This link will expire in 1 hour.</p>
            <p><a href='{$resetLink}' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a></p>
            <p>If you did not request this, please ignore this email.</p>
            <p><small>Or copy this link: {$resetLink}</small></p>
        </body>
        </html>
    ";

    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $headers[] = 'From: MeyDa Collection <' . SMTP_USER . '>';
    $headers[] = 'Reply-To: ' . SMTP_USER;
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    if (mail($toEmail, $subject, $message, implode("\r\n", $headers))) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'The server failed to send the email. Please check your hosting mail settings.'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email is required.';
    } else {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT id_pelanggan, nama FROM pelanggan WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $upd = $pdo->prepare("UPDATE pelanggan SET reset_token = :token, reset_expires_at = :expires WHERE id_pelanggan = :id");
            $upd->execute([
                ':token' => $token,
                ':expires' => $expires,
                ':id' => $user['id_pelanggan']
            ]);

            $resetLink = asset("reset_password.php?token=$token");
            $result = sendResetEmail($email, $resetLink);

            if ($result['success']) {
                $success = "A password reset link has been sent to your email!";
            } else {
                $error = "Failed to send email: " . $result['error'];
            }
        } else {
            $error = 'No account found with that email address.';
        }
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

                <form method="post" class="login-form">
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
