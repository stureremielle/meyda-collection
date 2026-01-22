<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = getPDO();
    $sql = "ALTER TABLE pelanggan 
            ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL, 
            ADD COLUMN IF NOT EXISTS reset_expires_at DATETIME NULL";
    
    $pdo->exec($sql);
    echo "Successfully updated 'pelanggan' table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Columns already exist.";
    } else {
        echo "Error updating table: " . $e->getMessage();
    }
}
?>
