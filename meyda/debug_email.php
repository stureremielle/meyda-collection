<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/SimpleSMTP.php';

echo "<h1>SMTP Socket Debug Test</h1>";
echo "<p>Testing direct socket connection to: " . SMTP_HOST . "</p>";

$to = SMTP_USER; // Send to self as a test
$subject = "SMTP Socket Debug Test - " . date('Y-m-d H:i:s');
$message = "<h1>It Works!</h1><p>This email was sent via a direct SMTP socket connection. If you see this, the new SimpleSMTP class is successfully bypassing the unreliable mail() function.</p>";

$smtp = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
$result = $smtp->send($to, $subject, $message);

if ($result['success']) {
    echo "<p style='color: green;'>SUCCESS: SMTP sent successfully! Check your inbox (and spam folder) for: <b>$to</b></p>";
} else {
    echo "<p style='color: red;'>FAILURE: SMTP failed. Error: " . h($result['error']) . "</p>";
}

echo "<hr>";
echo "<h3>Configuration</h3>";
echo "Host: " . SMTP_HOST . "<br>";
echo "Port: " . SMTP_PORT . "<br>";
echo "User: " . SMTP_USER . "<br>";
?>
