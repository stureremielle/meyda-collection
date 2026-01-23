<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

function sendOrderReceiptEmail($idTransaksi) {
    if (!defined('SMTP_HOST') || SMTP_HOST === 'smtp.yourdomain.com' || empty(SMTP_HOST)) {
        return ['success' => false, 'error' => 'SMTP not configured in config.php.'];
    }

    $pdo = getPDO();

    // Fetch transaction header
    $stmt = $pdo->prepare("
        SELECT t.*, p.nama, p.email, p.alamat, p.telepon
        FROM transaksi t
        JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
        WHERE t.id_transaksi = :id
    ");
    $stmt->execute([':id' => $idTransaksi]);
    $trans = $stmt->fetch();

    if (!$trans) {
        return ['success' => false, 'error' => 'Order not found.'];
    }

    // Fetch transaction details
    $stmtDetail = $pdo->prepare("
        SELECT d.*, p.nama_produk
        FROM detail_transaksi d
        JOIN produk p ON d.id_produk = p.id_produk
        WHERE d.id_transaksi = :id
    ");
    $stmtDetail->execute([':id' => $idTransaksi]);
    $details = $stmtDetail->fetchAll();

    $subject = "Your Receipt for Order #$idTransaksi - MeyDa Collection";
    
    // Construct HTML Message - Styled similarly to receipt.php
    $rows = "";
    foreach ($details as $d) {
        $price = number_format($d['harga_satuan'], 0, ',', '.');
        $subtotal = number_format($d['subtotal'], 0, ',', '.');
        $rows .= "
        <tr>
            <td style='padding: 12px 0; border-bottom: 1px solid #eee;'>{$d['nama_produk']}</td>
            <td style='padding: 12px 0; border-bottom: 1px solid #eee; text-align: center;'>{$d['qty']}</td>
            <td style='padding: 12px 0; border-bottom: 1px solid #eee; text-align: right;'>Rp $price</td>
            <td style='padding: 12px 0; border-bottom: 1px solid #eee; text-align: right; font-weight: bold;'>Rp $subtotal</td>
        </tr>";
    }

    $total = number_format($trans['total'], 0, ',', '.');
    $date = date('F d, Y', strtotime($trans['tanggal']));

    $message = "
    <html>
    <head>
        <title>Order Receipt</title>
        <style>
            body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 40px; border: 1px solid #eee; border-radius: 8px; }
            .header { text-align: center; margin-bottom: 30px; }
            .brand { font-family: 'Garamond', serif; font-size: 32px; color: #ff6d00; margin: 0; }
            .meta { display: flex; justify-content: space-between; margin-bottom: 30px; font-size: 14px; color: #777; }
            .billing { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
            .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            .total-section { text-align: right; border-top: 2px solid #ff6d00; padding-top: 20px; }
            .grand-total { font-size: 24px; font-weight: bold; color: #ff6d00; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 class='brand'>MeyDa Collection</h1>
                <p>Thank you for your purchase!</p>
            </div>
            
            <div style='display: block; margin-bottom: 20px;'>
                <div style='float: left;'><strong>Invoice #$idTransaksi</strong></div>
                <div style='float: right;'>$date</div>
                <div style='clear: both;'></div>
            </div>

            <div class='billing'>
                <div style='margin-bottom: 10px;'><strong>Billed To:</strong></div>
                <div>{$trans['nama']}</div>
                <div>{$trans['email']}</div>
                <div style='margin-top: 10px;'><strong>Shipping Address:</strong></div>
                <div>" . nl2br($trans['alamat']) . "</div>
            </div>

            <table class='table'>
                <thead>
                    <tr>
                        <th style='text-align: left; padding: 10px 0; border-bottom: 2px solid #eee;'>Item</th>
                        <th style='text-align: center; padding: 10px 0; border-bottom: 2px solid #eee;'>Qty</th>
                        <th style='text-align: right; padding: 10px 0; border-bottom: 2px solid #eee;'>Price</th>
                        <th style='text-align: right; padding: 10px 0; border-bottom: 2px solid #eee;'>Total</th>
                    </tr>
                </thead>
                <tbody>
                    $rows
                </tbody>
            </table>

            <div class='total-section'>
                <div style='margin-bottom: 5px; color: #777;'>Total Amount Paid</div>
                <div class='grand-total'>Rp $total</div>
            </div>

            <div style='margin-top: 40px; text-align: center; font-size: 12px; color: #aaa;'>
                &copy; " . date('Y') . " MeyDa Collection. All rights reserved.
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
    $result = $smtp->send($trans['email'], $subject, $message);

    if ($result['success']) {
        error_log("Receipt email sent successfully to: " . $trans['email']);
        return ['success' => true];
    } else {
        error_log("Failed to send receipt email to: " . $trans['email'] . ". Error: " . $result['error']);
        return ['success' => false, 'error' => $result['error']];
    }
}
?>
