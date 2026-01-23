<?php
require_once __DIR__ . '/config.php';

echo "<h1>Database Migration: Email Activation</h1>";

try {
    $pdo = getPDO();

    // Check if columns already exist
    $stmt = $pdo->query("DESCRIBE pelanggan");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('is_active', $columns)) {
        echo "<p>Adding 'is_active' column...</p>";
        $pdo->exec("ALTER TABLE pelanggan ADD COLUMN is_active TINYINT(1) DEFAULT 0");
        // Mark existing users as active
        $pdo->exec("UPDATE pelanggan SET is_active = 1");
    } else {
        echo "<p>'is_active' column already exists.</p>";
    }

    if (!in_array('activation_token', $columns)) {
        echo "<p>Adding 'activation_token' column...</p>";
        $pdo->exec("ALTER TABLE pelanggan ADD COLUMN activation_token VARCHAR(255) DEFAULT NULL");
    } else {
        echo "<p>'activation_token' column already exists.</p>";
    }

    echo "<p style='color: green;'>Migration successful!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Migration failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>