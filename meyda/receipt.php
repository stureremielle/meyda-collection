<?php
require_once __DIR__ . '/auth.php';
requireLogin('customer');

$pdo = getPDO();
$customerId = $_SESSION['customer_id'];
$idTransaksi = (int)($_GET['id'] ?? 0);

if ($idTransaksi <= 0) {
    die("Invalid Order ID.");
}

// Fetch transaction header and verify ownership
$stmt = $pdo->prepare("
    SELECT t.*, p.nama, p.email, p.alamat, p.telepon
    FROM transaksi t
    JOIN pelanggan p ON t.id_pelanggan = p.id_pelanggan
    WHERE t.id_transaksi = :id AND t.id_pelanggan = :customerId
");
$stmt->execute([':id' => $idTransaksi, ':customerId' => $customerId]);
$trans = $stmt->fetch();

if (!$trans) {
    die("Order not found or access denied.");
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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Receipt #<?php echo $idTransaksi; ?> - MeyDa Collection</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background: #0f1115; /* Dark background similar to the site */
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            min-height: 100vh;
        }

        .receipt-card {
            background: #1a1d23;
            width: 100%;
            max-width: 800px;
            padding: 60px;
            border-radius: 32px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 40px 100px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
        }

        .receipt-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ff6d00, #ffb300);
        }

        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 60px;
        }

        .brand-section h1 {
            font-family: 'Garamond', serif;
            font-size: 36px;
            color: #fff;
            margin: 0;
            letter-spacing: 1px;
        }

        .brand-section p {
            color: var(--muted);
            margin: 8px 0 0;
            font-size: 14px;
        }

        .order-meta {
            text-align: right;
        }

        .order-meta h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--accent);
            margin: 0;
        }

        .order-meta p {
            color: var(--muted);
            margin: 8px 0 0;
            font-size: 14px;
        }

        .billing-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
            padding: 32px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .bill-group h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--muted);
            margin-bottom: 16px;
        }

        .bill-group p {
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
            color: #fff;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .receipt-table th {
            text-align: left;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .receipt-table td {
            padding: 24px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 15px;
        }

        .item-name {
            font-weight: 600;
            color: #fff;
        }

        .item-price {
            color: var(--muted);
        }

        .receipt-footer {
            display: flex;
            justify-content: flex-end;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 32px;
        }

        .total-box {
            text-align: right;
            min-width: 250px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .total-label {
            color: var(--muted);
        }

        .total-value {
            font-weight: 600;
        }

        .grand-total {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 28px;
            font-weight: 700;
            color: var(--accent);
        }

        .print-actions {
            margin-top: 60px;
            display: flex;
            justify-content: center;
            gap: 16px;
        }

        .btn-print {
            background: var(--accent);
            color: white;
            padding: 14px 32px;
            border-radius: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            background: #e55d00;
            box-shadow: 0 10px 20px rgba(255, 109, 0, 0.3);
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            padding: 14px 32px;
            border-radius: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-card {
                box-shadow: none;
                border: none;
                max-width: 100%;
                padding: 0;
                background: white;
            }
            .receipt-card::before, .print-actions, .btn-back {
                display: none !important;
            }
            .billing-grid {
                background: none;
                border: 1px solid #eee;
            }
            .brand-section h1, .bill-group p, .item-name, td {
                color: black;
            }
            .grand-total {
                color: #ff6d00 !important;
            }
            .receipt-table th {
                color: #666;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div class="receipt-header">
            <div class="brand-section">
                <h1>MeyDa Collection</h1>
                <p>Elegance in Every Stitch</p>
            </div>
            <div class="order-meta">
                <h2>Invoice #<?php echo $idTransaksi; ?></h2>
                <p><?php echo date('F d, Y', strtotime($trans['tanggal'])); ?></p>
            </div>
        </div>

        <div class="billing-grid">
            <div class="bill-group">
                <h3>Billed To</h3>
                <p><strong><?php echo h($trans['nama']); ?></strong></p>
                <p><?php echo h($trans['email']); ?></p>
                <p><?php echo h($trans['telepon']); ?></p>
            </div>
            <div class="bill-group">
                <h3>Shipping Address</h3>
                <p><?php echo nl2br(h($trans['alamat'])); ?></p>
            </div>
        </div>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details as $d): ?>
                <tr>
                    <td>
                        <div class="item-name"><?php echo h($d['nama_produk']); ?></div>
                    </td>
                    <td style="text-align: center;"><?php echo (int)$d['qty']; ?></td>
                    <td style="text-align: right;" class="item-price">Rp <?php echo number_format($d['harga_satuan'], 0, ',', '.'); ?></td>
                    <td style="text-align: right; font-weight: 600;">Rp <?php echo number_format($d['subtotal'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="receipt-footer">
            <div class="total-box">
                <div class="total-row">
                    <span class="total-label">Subtotal</span>
                    <span class="total-value">Rp <?php echo number_format($trans['total'], 0, ',', '.'); ?></span>
                </div>
                <div class="total-row">
                    <span class="total-label">Shipping</span>
                    <span class="total-value">Rp 0</span>
                </div>
                <div class="grand-total">
                    Rp <?php echo number_format($trans['total'], 0, ',', '.'); ?>
                </div>
            </div>
        </div>

        <div class="print-actions">
            <a href="account.php" class="btn-back">Back to Account</a>
            <button onclick="window.print()" class="btn-print">Print Receipt</button>
        </div>
    </div>
</body>
</html>
