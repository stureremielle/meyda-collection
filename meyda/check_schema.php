<?php
require_once __DIR__ . '/config.php';
$pdo = getPDO();
$stmt = $pdo->query("DESCRIBE pelanggan");
$columns = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($columns, JSON_PRETTY_PRINT);
?>