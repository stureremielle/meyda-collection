<?php
require_once __DIR__ . '/OrderMailer.php';
require_once __DIR__ . '/config.php';

echo "<h1>Order Email Verification Test</h1>";

// Try to find the latest transaction ID
$pdo = getPDO();
$stmt = $pdo->query("SELECT id_transaksi FROM transaksi ORDER BY id_transaksi DESC LIMIT 1");
$idTransaksi = $stmt->fetchColumn();

if (!$idTransaksi) {
    die("<p style='color: red;'>No transactions found in database. Please make a purchase first or ensure the database is seeded.</p>");
}

echo "<p>Testing order email for Transaction ID: <b>$idTransaksi</b></p>";
echo "<p>Sending to: <b>" . SMTP_USER . "</b> (for testing purposes)</p>";

$result = sendOrderReceiptEmail($idTransaksi);

if ($result['success']) {
    echo "<p style='color: green;'>SUCCESS: Receipt email sent successfully!</p>";
} else {
    echo "<p style='color: red;'>FAILURE: Failed to send receipt email. Error: " . htmlspecialchars($result['error']) . "</p>";
}
?>