<?php
require_once __DIR__ . '/auth.php';

// Helper function to send email via SMTP (AlwaysData or Gmail)
function sendResetEmail($toEmail, $resetLink) {
    if (!defined('SMTP_HOST') || SMTP_HOST === 'smtp.yourdomain.com') {
        return ['success' => false, 'error' => 'SMTP not configured in config.php.'];
    }

    // Since we don't have PHPMailer installed via composer, we'll use AlwaysData's native mail()
    // or a simple SMTP implementation. AlwaysData's mail() is usually the easiest.
    
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

    // Try sending via mail() first, which uses AlwaysData's server
    if (mail($toEmail, $subject, $message, implode("\r\n", $headers))) {
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'The server failed to send the email. Please check your hosting mail settings.'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        header('Location: forgot_password.php?error=' . urlencode('Email is required.'));
        exit;
    }

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
            $msg = "A password reset link has been sent to your email.";
            
            // Re-use the forgot_password page style for success message
            ?>
            <!doctype html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width,initial-scale=1">
                <title>Email Sent - MeyDa Collection</title>
                <link rel="stylesheet" href="<?php echo asset('styles.css'); ?>">
            </head>
            <body class="auth-page">
                <main class="auth-center">
                    <div class="login-container">
                        <div class="login-card">
                            <div class="login-card-title">
                                <h2>Check Your Email</h2>
                                <p style="margin-top: 8px; color: var(--muted); font-size: 14px;">We've sent a reset link to <strong><?php echo h($email); ?></strong></p>
                            </div>
                            <div class="alert alert-success">
                                <?php echo $msg; ?>
                            </div>
                            <p style="margin-top:24px; text-align:center; font-size:14px; color:var(--muted);">
                                Didn't receive it? <a href="forgot_password" class="link">Try again</a>
                            </p>
                            <div style="margin-top: 16px; text-align: center;">
                                <a href="login" class="btn-primary">Back to Login</a>
                            </div>
                        </div>
                    </div>
                </main>
            </body>
            </html>
            <?php
            exit;
        } else {
            // Handle error (e.g. log it, show generic error)
            $errData = json_decode($result['error'], true);
            $errMessage = $errData['message'] ?? 'Failed to send email.';
            
            if (strpos($errMessage, 'resend.dev') !== false) {
                $errMessage = "Resend Trial Error: You can only send to your own email address when using the onboarding@resend.dev domain. Please verify your own domain in Resend.com dashboard to send to anyone!";
            }

            error_log("Resend Error: " . $result['error']);
            header('Location: forgot_password.php?error=' . urlencode($errMessage));
            exit;
        }
    } else {
        // User not found - spouting error as requested
        header('Location: forgot_password.php?error=' . urlencode('No account found with that email address.'));
        exit;
    }
} else {
    header('Location: forgot_password');
    exit;
}
?>
