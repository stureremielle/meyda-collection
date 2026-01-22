<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain');
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "\n";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "asset('styles.css'): " . asset('styles.css') . "\n";
?>
