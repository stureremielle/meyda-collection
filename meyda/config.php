<?php
// config.php - edit these values for your host
define('DB_HOST', 'https://mysql-meyda.alwaysdata.net/');      // Host or URL provided by host (we sanitize below)
define('DB_NAME', 'meyda_collection');
define('DB_USER', 'meyda');     // set to the DB user you create on AlwaysData
define('DB_PASS', 'kraccbacc');
define('DEFAULT_USER_ID', 2);        // id_user used for transactions (kasir). Ensure it exists.

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Note: avoid using PDO::MYSQL_ATTR_INIT_COMMAND to prevent deprecation on PHP 8.5+
];

function getPDO() {
    static $pdo = null;
    global $pdoOptions;
    if ($pdo === null) {
        // Normalize DB_HOST: allow users to copy a URL like https://host/ and still work
        $raw = DB_HOST;
        $host = null;
        $port = null;

        // Try parse_url first (handles scheme://host:port/path)
        $parsed = @parse_url($raw);
        if ($parsed !== false && isset($parsed['host'])) {
            $host = $parsed['host'];
            if (!empty($parsed['port'])) $port = (int)$parsed['port'];
        } else {
            // Fallback: strip scheme and trailing slashes
            $host = preg_replace('#^https?://#i', '', $raw);
            $host = rtrim($host, '/');
            // If host contains a colon with port, split it
            if (strpos($host, ':') !== false) {
                [$h, $p] = explode(':', $host, 2);
                $host = $h;
                if (is_numeric($p)) $port = (int)$p;
            }
        }

        // Build DSN with optional port; charset in DSN avoids init command
        $dsn = 'mysql:host=' . $host . ($port ? ';port=' . $port : '') . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
    }
    return $pdo;
}
