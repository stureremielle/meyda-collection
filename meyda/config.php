<?php
// config.php - edit these values for your host
define("DB_HOST", "mysql-meyda.alwaysdata.net"); // Database host address (force TCP/IP connection)
define("DB_NAME", "meyda_collection");
define("DB_USER", "meyda"); // set to the DB user you create on your server
define("DB_PASS", "kraccbacc"); // Change this to a strong password
define("DEFAULT_USER_ID", 1); // id_user used for transactions (admin). Ensure it exists.

// Generate a secure key if not already set
if (!defined("MEYDA_ADMIN_KEY")) {
    $admin_key = getenv("MEYDA_ADMIN_KEY") ?: bin2hex(random_bytes(32));
    putenv("MEYDA_ADMIN_KEY=$admin_key");
    define("MEYDA_ADMIN_KEY", $admin_key);
}

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Note: avoid using PDO::MYSQL_ATTR_INIT_COMMAND to prevent deprecation on PHP 8.5+
];

function getPDO()
{
    static $pdo = null;
    global $pdoOptions;
    if ($pdo === null) {
        // Direct approach: use defined constants as-is
        $host = DB_HOST;
        $port = null;

        // Extract port from host if present
        $host_parts = explode(":", $host, 2);
        if (count($host_parts) === 2 && is_numeric($host_parts[1])) {
            $host = $host_parts[0];
            $port = (int) $host_parts[1];
        }

        // Build DSN with optional port
        $dsn =
            "mysql:host=" . $host . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        if ($port) {
            $dsn .= ";port=" . $port;
        }

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
    }
    return $pdo;
}
