<?php
require_once __DIR__ . '/config.php';
$pdo = getPDO();

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "Tables in database:\n";
foreach ($tables as $table) {
    echo "- $table\n";
    $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll();
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
}
?>
