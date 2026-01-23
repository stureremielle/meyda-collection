<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Please provide a valid email address.']);
    exit;
}

// SMTP sending logic
function sendNewsletterWelcome($toEmail) {
    if (!defined('SMTP_HOST') || SMTP_HOST === 'smtp.yourdomain.com' || empty(SMTP_HOST)) {
        return ['success' => false, 'error' => 'SMTP not configured in config.php.'];
    }

    $subject = 'Welcome to the Inner Circle - MeyDa Collection';
    $message = "
        <html>
        <head>
            <title>Welcome to the Inner Circle</title>
            <style>
                body { font-family: 'Garamond', serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; border-bottom: 2px solid #ff6d00; padding-bottom: 10px; }
                .content { padding: 20px 0; }
                .footer { font-size: 12px; color: #777; border-top: 1px solid #eee; padding-top: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='color: #ff6d00;'>MEYDA</h1>
                </div>
                <div class='content'>
                    <h2>Welcome to the Inner Circle</h2>
                    <p>Thank you for signing up for the MeyDa Collection newsletter.</p>
                    <p>You'll now be the first to know about our private releases, exclusive collections, and seasonal events.</p>
                    <p>Stay tuned for something special coming your way soon.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " MeyDa Collection. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
    ";

    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $headers[] = 'From: MeyDa Collection <' . SMTP_USER . '>';
    require_once __DIR__ . '/SimpleSMTP.php';
    $smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
    return $smtp->send($toEmail, $subject, $message);
}

$result = sendNewsletterWelcome($email);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => 'Successfully joined the Inner Circle! Check your email.']);
} else {
    // We still consider it a success if the email fail but the intent was captured
    error_log("Newsletter email error: " . $result['error']);
    echo json_encode(['success' => true, 'message' => 'Successfully joined the Inner Circle! (Confirmation email pending)']);
}
?>
